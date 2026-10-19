<?php

declare(strict_types=1);

namespace alina\Services\Chat;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use SplObjectStorage;
use Throwable;

class ChatServer implements MessageComponentInterface
{
    private SplObjectStorage $clients;
    private array $channels = [];
    private array $pending = [];
    private array $rooms = [];
    private array $roomCleanupTimers = [];
    private LoopInterface $loop;
    private int $messageSequence = 0;
    private string $instanceId;

    public function __construct(
        private readonly ChatStorage $storage,
        ?LoopInterface $loop = null,
    ) {
        $this->clients = new SplObjectStorage();
        $this->instanceId = bin2hex(random_bytes(8));
        $this->loop = $loop ?? Loop::get();
        $this->loop->addPeriodicTimer(2, fn () => $this->expireTyping());
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn, [
            'origin' => $this->connectionOrigin($conn),
            'roomKey' => null,
            'chatId' => null,
            'channel' => null,
            'version' => 1,
            'user' => [],
            'participantId' => 'guest:' . $conn->resourceId,
            'typingUntil' => 0,
            'admission' => null,
        ]);
        // The legacy client expects a plain-text handshake.
        $this->send($conn, 'Connection established.');
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if (! $this->clients->contains($from)) {
            return;
        }

        if (! is_string($msg) || strlen($msg) > 65536) {
            $this->error($from, 'Message is too large.');

            return;
        }

        try {
            $data = json_decode($msg, true, 32, JSON_THROW_ON_ERROR);
        }
        catch (Throwable) {
            $this->error($from, 'Invalid chat payload.');

            return;
        }

        if (! is_array($data) || array_is_list($data)) {
            $this->error($from, 'Invalid chat payload.');

            return;
        }

        $version = ($data['version'] ?? null) === 2 ? 2 : 1;
        $text = $data['msg'] ?? '';
        $type = $version === 2
            ? ($data['type'] ?? '')
            : (is_string($text) && str_starts_with($text, 'JOIN:') ? 'join' : 'message');

        if (! in_array($type, ['join', 'message', 'typing', 'approve', 'rename', 'set_anonymous', 'set_confirmation', 'kick', 'list_rooms'], true)) {
            $this->error($from, 'Unknown chat event.');

            return;
        }

        if ($type === 'list_rooms') {
            try {
                $this->listRooms($from, $data);
            }
            catch (Throwable $error) {
                error_log('Chat storage failed: ' . $error->getMessage());
                $this->error($from, 'Unable to store chat data.');
            }

            return;
        }

        $channel = $data['channel'] ?? '';

        if (! is_string($channel) || ! preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $channel)) {
            $this->error($from, 'A channel of 1–128 letters, numbers, underscores or hyphens is required.');

            return;
        }

        $state = $this->clients[$from];

        if ($type !== 'join' && $state['channel'] !== null && $state['channel'] !== $channel) {
            $this->error($from, 'The event channel does not match the joined chat.');

            return;
        }

        try {
            if ($type === 'join') {
                $this->join($from, $data, $channel, $version);

                return;
            }

            if ($state['channel'] === null) {
                if ($version === 1) {
                    $this->join($from, $data, $channel, $version, false);
                }
                else {
                    $this->error($from, 'Join the chat before sending events.');

                    return;
                }
            }

            $state = $this->clients[$from];

            if ($state['admission'] !== 'admitted') {
                $this->error($from, 'This chat connection is awaiting approval.');

                return;
            }

            if ($type === 'approve') {
                $this->approve($from, $data);

                return;
            }

            if ($type === 'rename') {
                $this->rename($from, $data);

                return;
            }

            if ($type === 'set_anonymous') {
                $this->setAnonymousAllowed($from, $data);

                return;
            }

            if ($type === 'set_confirmation') {
                $this->setConfirmationRequired($from, $data);

                return;
            }

            if ($type === 'kick') {
                $this->kick($from, $data);

                return;
            }

            if ($type === 'typing') {
                $this->typing($from, ($data['typing'] ?? false) === true);

                return;
            }

            $this->message($from, $data, $text, $version);
        }
        catch (Throwable $error) {
            error_log('Chat storage failed: ' . $error->getMessage());
            $this->error($from, 'Unable to store chat data.');
        }
    }

    private function join(
        ConnectionInterface $client,
        array $data,
        string $channel,
        int $version,
        bool $sendJoinEvents = true,
    ): void {
        $state = $this->clients[$client];
        $origin = $state['origin'];

        if ($origin === null) {
            $this->error($client, 'A valid HTTP Origin header is required.');

            return;
        }

        $chat = $this->storage->openChat($origin, $channel);
        $roomKey = $this->roomKey($origin, $channel);
        $user = $this->publicUser($data['CurrentUser'] ?? null, $client->resourceId);
        $participantId = $this->participantId($user, $client->resourceId);

        if (
            $state['roomKey'] === $roomKey
            && $state['admission'] !== null
            && $state['participantId'] !== $participantId
        ) {
            $this->error($client, 'The participant identity cannot change after joining this chat.');

            return;
        }

        if (! $chat['isAnionAllowed'] && str_starts_with($participantId, 'guest:')) {
            $this->error($client, 'Anonymous participants are not allowed in this chat.');

            return;
        }

        if ($state['roomKey'] !== null && $state['roomKey'] !== $roomKey) {
            $this->leave($client);
            $state = $this->clients[$client];
        }

        $this->channels[$roomKey] ??= new SplObjectStorage();
        $this->pending[$roomKey] ??= new SplObjectStorage();
        $this->rooms[$roomKey] = [
            'origin' => $origin,
            'channel' => $channel,
            'chatId' => $chat['id'],
            'chat' => $chat,
        ];

        if (isset($this->roomCleanupTimers[$roomKey])) {
            $this->loop->cancelTimer($this->roomCleanupTimers[$roomKey]);
            unset($this->roomCleanupTimers[$roomKey]);
        }

        $state['roomKey'] = $roomKey;
        $state['chatId'] = $chat['id'];
        $state['channel'] = $channel;
        $state['version'] = $version;
        $state['user'] = $user;
        $state['participantId'] = $participantId;
        $state['typingUntil'] = 0;
        $alreadyAdmitted = $state['admission'] === 'admitted';
        $this->clients[$client] = $state;
        [$senderType, $senderId] = explode(':', $participantId, 2);
        $hasAnyMessage = $this->storage->hasAnyMessage($chat['id']);
        $isFirstLiveParticipant = ! $hasAnyMessage && $this->channels[$roomKey]->count() === 0;
        $mayEnter = $alreadyAdmitted
            || ! $chat['isConfirmationRequired']
            || $isFirstLiveParticipant
            || $this->storage->hasParticipantMessage($chat['id'], $senderType, $senderId);

        if (! $mayEnter) {
            $state['admission'] = 'pending';
            $this->clients[$client] = $state;
            $this->pending[$roomKey]->attach($client);
            $this->send($client, [
                'version' => 2,
                'type' => 'join_pending',
                'channel' => $channel,
                'participantId' => $participantId,
            ]);
            $this->broadcastJoinRequest($roomKey, $participantId, $user);

            return;
        }

        $history = $this->storage->history($chat['id'], 50);
        $this->admit($client, $history, $sendJoinEvents ? 'joined' : null);
    }

    private function listRooms(ConnectionInterface $client, array $data): void
    {
        $state = $this->clients[$client];

        if ($state['origin'] === null) {
            $this->error($client, 'A valid HTTP Origin header is required.');

            return;
        }

        $user = $this->publicUser($data['CurrentUser'] ?? null, $client->resourceId);

        if ($user['id'] !== null) {
            $senderType = 'user';
            $senderId = $user['id'];
        }
        elseif (isset($user['guestId'])) {
            $senderType = 'guest';
            $senderId = $user['guestId'];
        }
        else {
            $this->error($client, 'A valid persistent user or guest identity is required.');

            return;
        }

        $pagination = $this->roomPagination($data['pagination'] ?? null);

        if ($pagination === null) {
            $this->error($client, 'Valid independent room pagination is required.');

            return;
        }

        $rooms = $this->storage->listRooms($state['origin'], $senderType, $senderId, $pagination);
        $this->send($client, [
            'version' => 2,
            'type' => 'rooms',
            'mine' => $rooms['mine'],
            'others' => $rooms['others'],
        ]);
    }

    private function roomPagination(mixed $pagination): ?array
    {
        if (! is_array($pagination) || array_is_list($pagination)) {
            return null;
        }

        $result = [];

        foreach (['mine', 'others'] as $name) {
            $page = $pagination[$name] ?? null;
            $limit = is_array($page) ? ($page['limit'] ?? null) : null;
            $offset = is_array($page) ? ($page['offset'] ?? null) : null;

            if (! is_int($limit) || $limit < 1 || ! is_int($offset) || $offset < 0) {
                return null;
            }

            $result[$name] = ['limit' => min(1000, $limit), 'offset' => $offset];
        }

        return $result;
    }

    private function approve(ConnectionInterface $client, array $data): void
    {
        $participantId = $data['participantId'] ?? null;

        if (
            ! is_string($participantId)
            || strlen($participantId) > 200
            || ! preg_match('/^(user|guest):.+$/D', $participantId)
        ) {
            $this->error($client, 'A valid participant ID is required.');

            return;
        }

        $state = $this->clients[$client];
        $roomKey = $state['roomKey'];
        $approved = [];

        foreach (iterator_to_array($this->pending[$roomKey] ?? new SplObjectStorage()) as $pendingClient) {
            $pendingState = $this->clients[$pendingClient];

            if ($pendingState['participantId'] === $participantId) {
                $approved[] = $pendingClient;
            }
        }

        if ($approved === []) {
            $this->error($client, 'That participant is no longer awaiting approval.');

            return;
        }

        foreach ($approved as $pendingClient) {
            $pendingState = $this->clients[$pendingClient];
            $history = $this->storage->history($pendingState['chatId'], 50);
            $this->admit($pendingClient, $history, 'join_approved');
        }
    }

    private function admit(ConnectionInterface $client, array $history, ?string $eventType): void
    {
        $state = $this->clients[$client];
        $roomKey = $state['roomKey'];

        if (isset($this->pending[$roomKey])) {
            $this->pending[$roomKey]->detach($client);
        }

        $this->channels[$roomKey]->attach($client);
        $state['admission'] = 'admitted';
        $this->clients[$client] = $state;

        if ($eventType !== null) {
            $this->sendHistory($client, $state['channel'], $history);
            $this->send($client, [
                'version' => 2,
                'type' => $eventType,
                'channel' => $state['channel'],
                'participantId' => $state['participantId'],
            ]);
            $this->send($client, $this->chatStateEvent($roomKey));
        }

        $this->broadcastPresence($roomKey);
        $this->sendPendingRequestsTo($client);
    }

    private function rename(ConnectionInterface $client, array $data): void
    {
        $name = $data['name'] ?? null;

        if (! is_string($name)) {
            $this->error($client, 'A chat name is required.');

            return;
        }

        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 200) {
            $this->error($client, 'Enter a chat name of at most 200 characters.');

            return;
        }

        $state = $this->clients[$client];
        $chat = $this->storage->renameChat($state['chatId'], $name, $this->nowMilliseconds());
        $this->rooms[$state['roomKey']]['chat'] = $chat;
        $this->broadcast($state['roomKey'], fn (): array => $this->chatStateEvent($state['roomKey']));
    }

    private function setAnonymousAllowed(ConnectionInterface $client, array $data): void
    {
        $allowed = $data['allowed'] ?? null;

        if (! is_bool($allowed)) {
            $this->error($client, 'Anonymous access must be true or false.');

            return;
        }

        $state = $this->clients[$client];
        $chat = $this->storage->setAnonymousAllowed($state['chatId'], $allowed, $this->nowMilliseconds());
        $this->rooms[$state['roomKey']]['chat'] = $chat;
        $this->broadcast($state['roomKey'], fn (): array => $this->chatStateEvent($state['roomKey']));
    }

    private function setConfirmationRequired(ConnectionInterface $client, array $data): void
    {
        $required = $data['required'] ?? null;

        if (! is_bool($required)) {
            $this->error($client, 'Confirmation requirement must be true or false.');

            return;
        }

        $state = $this->clients[$client];
        $chat = $this->storage->setConfirmationRequired($state['chatId'], $required, $this->nowMilliseconds());
        $this->rooms[$state['roomKey']]['chat'] = $chat;
        $this->broadcast($state['roomKey'], fn (): array => $this->chatStateEvent($state['roomKey']));
    }

    private function kick(ConnectionInterface $client, array $data): void
    {
        $participantId = $data['participantId'] ?? null;

        if (
            ! is_string($participantId)
            || strlen($participantId) > 200
            || ! preg_match('/^(user|guest):.+$/D', $participantId)
        ) {
            $this->error($client, 'A valid participant ID is required.');

            return;
        }

        $state = $this->clients[$client];
        $roomKey = $state['roomKey'];
        $targets = [];
        $targetName = $participantId;

        foreach (iterator_to_array($this->channels[$roomKey]) as $candidate) {
            $candidateState = $this->clients[$candidate];

            if ($candidateState['participantId'] === $participantId) {
                $targets[] = $candidate;
                $targetName = $candidateState['user']['name'] ?: $participantId;
            }
        }

        if ($targets === []) {
            $this->error($client, 'That participant is not connected to this chat.');

            return;
        }

        [$senderType, $senderId] = explode(':', $participantId, 2);
        $this->storage->deleteParticipantMessages($state['chatId'], $senderType, $senderId);
        $stored = $this->storage->appendMessage($state['chatId'], [
            'senderType' => 'bot',
            'senderId' => 'robocop',
            'senderName' => 'Robocop',
            'senderEmblem' => '',
            'text' => 'Robocop removed ' . $targetName . ' from the chat.',
            'createdAt' => $this->nowMilliseconds(),
            'clientMessageId' => 'robocop-kick-' . bin2hex(random_bytes(16)),
        ]);
        $message = $this->protocolMessage($stored['message'], $state['channel']);

        $this->broadcast($roomKey, fn (): array => [
            'version' => 2,
            'type' => 'participant_kicked',
            'channel' => $state['channel'],
            'participantId' => $participantId,
            'message' => $message,
        ]);

        foreach ($targets as $target) {
            $this->onClose($target);
            $target->close();
        }
    }

    private function chatStateEvent(string $roomKey): array
    {
        $chat = $this->rooms[$roomKey]['chat'];

        return [
            'version' => 2,
            'type' => 'chat_state',
            'channel' => $this->rooms[$roomKey]['channel'],
            'chat' => [
                'name' => $chat['name'],
                'isAnionAllowed' => $chat['isAnionAllowed'],
                'isConfirmationRequired' => $chat['isConfirmationRequired'],
            ],
        ];
    }

    private function broadcastJoinRequest(string $roomKey, string $participantId, array $user): void
    {
        $channel = $this->rooms[$roomKey]['channel'];
        $this->broadcast($roomKey, fn (): array => [
            'version' => 2,
            'type' => 'join_request',
            'channel' => $channel,
            'participant' => ['id' => $participantId, 'user' => $user],
        ]);
    }

    private function sendPendingRequestsTo(ConnectionInterface $client): void
    {
        $state = $this->clients[$client];
        $roomKey = $state['roomKey'];
        $sent = [];

        foreach ($this->pending[$roomKey] ?? new SplObjectStorage() as $pendingClient) {
            $pendingState = $this->clients[$pendingClient];
            $participantId = $pendingState['participantId'];

            if (isset($sent[$participantId])) {
                continue;
            }

            $sent[$participantId] = true;
            $this->send($client, [
                'version' => 2,
                'type' => 'join_request',
                'channel' => $state['channel'],
                'participant' => [
                    'id' => $participantId,
                    'user' => $pendingState['user'],
                ],
            ]);
        }
    }

    private function typing(ConnectionInterface $client, bool $typing): void
    {
        $state = $this->clients[$client];
        $state['typingUntil'] = $typing ? microtime(true) + 6 : 0;
        $this->clients[$client] = $state;
        $this->broadcastPresence($state['roomKey']);
    }

    private function message(ConnectionInterface $client, array $data, mixed $text, int $version): void
    {
        if (! is_string($text) || trim($text) === '' || strlen($text) > 16000) {
            $this->error($client, 'Enter a message of at most 16000 bytes.');

            return;
        }

        $clientMessageId = $data['clientMessageId'] ?? null;

        if ($version === 1) {
            $clientMessageId = 'legacy-' . $this->instanceId . '-' . ++$this->messageSequence;
        }

        if (
            ! is_string($clientMessageId)
            || $clientMessageId === ''
            || strlen($clientMessageId) > 64
            || ! preg_match('/^[A-Za-z0-9._:-]+$/D', $clientMessageId)
        ) {
            $this->error($client, 'A valid client message ID of at most 64 characters is required.');

            return;
        }

        $state = $this->clients[$client];
        $candidateUser = $this->publicUser($data['CurrentUser'] ?? null, $client->resourceId);

        if ($this->participantId($candidateUser, $client->resourceId) === $state['participantId']) {
            $state['user'] = $candidateUser;
            $this->clients[$client] = $state;
        }

        [$senderType, $senderId] = explode(':', $state['participantId'], 2);
        $stored = $this->storage->appendMessage($state['chatId'], [
            'senderType' => $senderType,
            'senderId' => $senderId,
            'senderName' => $state['user']['name'],
            'senderEmblem' => $state['user']['emblem'],
            'text' => trim($text),
            'createdAt' => $this->nowMilliseconds(),
            'clientMessageId' => $clientMessageId,
        ]);

        if (! $stored['inserted']) {
            return;
        }

        $message = $this->protocolMessage($stored['message'], $state['channel'], $state['user']);
        $this->broadcast($state['roomKey'], fn ($recipient) => $this->clients[$recipient]['version'] === 2
            ? ['version' => 2, 'type' => 'message', 'channel' => $state['channel'], 'message' => $message]
            : $message);
        $this->broadcastPresence($state['roomKey']);
    }

    private function publicUser($user, $connectionId): array
    {
        $user = is_array($user) ? $user : [];
        $id = $user['id'] ?? null;
        $id = (is_int($id) || is_string($id)) && ctype_digit((string) $id) && (int) $id > 0
            ? (string) $id
            : null;
        $fields = ['id' => $id];

        foreach (['firstname', 'lastname', 'nickname', 'emblem'] as $field) {
            $maximum = $field === 'emblem' ? 2048 : 160;
            $fields[$field] = is_string($user[$field] ?? null) ? substr($user[$field], 0, $maximum) : '';
        }

        $fields['name'] = trim($fields['nickname'])
            ?: (trim($fields['firstname'] . ' ' . $fields['lastname']) ?: 'Guest ' . $connectionId);
        $guestId = $user['guestId'] ?? null;

        if (
            $id === null
            && is_string($guestId)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $guestId)
        ) {
            $fields['guestId'] = strtolower($guestId);
            $base = 'Guest ' . substr($fields['guestId'], 0, 8);
            $nickname = trim($fields['nickname']);
            $fields['name'] = $nickname !== '' && $nickname !== $base ? $nickname . ' (' . $base . ')' : $base;
        }

        return $fields;
    }

    private function participantId(array $user, int|string $connectionId): string
    {
        return $user['id'] !== null
            ? 'user:' . $user['id']
            : 'guest:' . ($user['guestId'] ?? $connectionId);
    }

    private function sendHistory(ConnectionInterface $client, string $channel, array $storedMessages): void
    {
        $messages = array_map(
            fn (array $message): array => $this->protocolMessage($message, $channel),
            $storedMessages,
        );

        $this->send($client, $this->clients[$client]['version'] === 2
            ? ['version' => 2, 'type' => 'history', 'channel' => $channel, 'messages' => $messages]
            : array_map(fn (array $message): string => json_encode($message, JSON_INVALID_UTF8_SUBSTITUTE), $messages));
    }

    private function protocolMessage(array $stored, string $channel, ?array $liveUser = null): array
    {
        $participantId = $stored['senderType'] . ':' . $stored['senderId'];
        $user = $liveUser ?? [
            'id' => $stored['senderType'] === 'user' ? $stored['senderId'] : null,
            'firstname' => '',
            'lastname' => '',
            'nickname' => $stored['senderName'],
            'emblem' => $stored['senderEmblem'],
            'name' => $stored['senderName'],
        ];

        if ($stored['senderType'] === 'guest') {
            $user['guestId'] = $stored['senderId'];
        }

        return [
            'id' => (string) $stored['id'],
            'channel' => $channel,
            'msg' => $stored['text'],
            'CurrentUser' => $user,
            'participantId' => $participantId,
            'timestamp' => $stored['createdAt'],
            'clientMessageId' => $stored['clientMessageId'],
        ];
    }

    private function broadcastPresence(string $roomKey): void
    {
        if (! isset($this->channels[$roomKey])) {
            return;
        }

        $people = [];

        foreach ($this->channels[$roomKey] as $client) {
            $state = $this->clients[$client];
            $id = $state['participantId'];
            $typing = $state['typingUntil'] > microtime(true);

            if (! isset($people[$id])) {
                $people[$id] = [
                    'id' => $id,
                    'user' => $state['user'],
                    'status' => 'online',
                    'typing' => false,
                ];
            }

            $people[$id]['typing'] = $people[$id]['typing'] || $typing;
        }

        $channel = $this->rooms[$roomKey]['channel'];
        $this->broadcast($roomKey, fn ($client) => $this->clients[$client]['version'] === 2
            ? [
                'version' => 2,
                'type' => 'presence',
                'channel' => $channel,
                'selfId' => $this->clients[$client]['participantId'],
                'participants' => array_values($people),
            ]
            : null);
    }

    private function expireTyping(): void
    {
        $changed = [];

        foreach ($this->clients as $client) {
            $state = $this->clients[$client];

            if ($state['typingUntil'] > 0 && $state['typingUntil'] <= microtime(true)) {
                $state['typingUntil'] = 0;
                $this->clients[$client] = $state;

                if ($state['roomKey'] !== null && $state['admission'] === 'admitted') {
                    $changed[$state['roomKey']] = true;
                }
            }
        }

        foreach (array_keys($changed) as $roomKey) {
            $this->broadcastPresence((string) $roomKey);
        }
    }

    private function leave(ConnectionInterface $client): void
    {
        $state = $this->clients[$client];
        $roomKey = $state['roomKey'];
        $state['roomKey'] = null;
        $state['chatId'] = null;
        $state['channel'] = null;
        $state['typingUntil'] = 0;
        $admission = $state['admission'];
        $state['admission'] = null;
        $this->clients[$client] = $state;

        if ($roomKey === null) {
            return;
        }

        if ($admission === 'pending' && isset($this->pending[$roomKey])) {
            $this->pending[$roomKey]->detach($client);
        }

        if ($admission === 'admitted' && isset($this->channels[$roomKey])) {
            $this->channels[$roomKey]->detach($client);
        }

        $activeCount = isset($this->channels[$roomKey]) ? $this->channels[$roomKey]->count() : 0;
        $pendingCount = isset($this->pending[$roomKey]) ? $this->pending[$roomKey]->count() : 0;

        if ($activeCount === 0 && $pendingCount === 0) {
            $this->roomCleanupTimers[$roomKey] = $this->loop->addTimer(120, function () use ($roomKey): void {
                unset($this->roomCleanupTimers[$roomKey]);

                $activeCount = isset($this->channels[$roomKey]) ? $this->channels[$roomKey]->count() : 0;
                $pendingCount = isset($this->pending[$roomKey]) ? $this->pending[$roomKey]->count() : 0;

                if ($activeCount === 0 && $pendingCount === 0) {
                    unset($this->channels[$roomKey], $this->pending[$roomKey], $this->rooms[$roomKey]);
                }
            });
        }
        elseif ($admission === 'admitted') {
            $this->broadcastPresence($roomKey);
        }
    }

    private function broadcast(string $roomKey, callable $payload): void
    {
        if (! isset($this->channels[$roomKey])) {
            return;
        }

        foreach (iterator_to_array($this->channels[$roomKey]) as $client) {
            if ($this->clients->contains($client)) {
                $data = $payload($client);

                if ($data !== null) {
                    $this->send($client, $data);
                }
            }
        }
    }

    private function connectionOrigin(ConnectionInterface $client): ?string
    {
        $request = $client->httpRequest ?? null;

        if (! is_object($request) || ! method_exists($request, 'getHeaderLine')) {
            return null;
        }

        return $this->normalizeOrigin($request->getHeaderLine('Origin'));
    }

    private function normalizeOrigin(string $origin): ?string
    {
        $origin = trim($origin);
        $parts = parse_url($origin);

        if (
            ! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])
            || (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/')
        ) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? null;

        if ($port !== null && ($port < 1 || $port > 65535)) {
            return null;
        }

        $isDefaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
        $portPart = $port !== null && ! $isDefaultPort ? ':' . $port : '';

        return $scheme . '://' . $host . $portPart;
    }

    private function roomKey(string $origin, string $channel): string
    {
        return $origin . "\0" . $channel;
    }

    private function nowMilliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function send(ConnectionInterface $client, $data): void
    {
        try {
            $client->send(is_string($data)
                ? $data
                : json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
        }
        catch (Throwable $error) {
            error_log('Chat send failed: ' . $error->getMessage());
            $this->onClose($client);

            try {
                $client->close();
            }
            catch (Throwable) {
            }
        }
    }

    private function error(ConnectionInterface $client, string $message): void
    {
        $this->send($client, ['version' => 2, 'type' => 'error', 'error' => $message]);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        if ($this->clients->contains($conn)) {
            $this->leave($conn);
            $this->clients->detach($conn);
        }
    }

    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        error_log('Chat connection failed: ' . $e->getMessage());
        $this->onClose($conn);
        $conn->close();
    }
}
