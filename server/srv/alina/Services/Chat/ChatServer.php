<?php

declare(strict_types=1);

namespace alina\Services\Chat;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use RuntimeException;
use SplObjectStorage;
use Throwable;

class ChatServer implements MessageComponentInterface
{
    private const HISTORY_PAGE_SIZE = 200;
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
        $this->clients->offsetSet($conn, [
            'origin' => $this->connectionOrigin($conn),
            'roomKey' => null,
            'chatId' => null,
            'channel' => null,
            'version' => 1,
            'user' => [],
            'participantId' => 'guest:' . $conn->resourceId,
            'typingUntil' => 0,
            'admission' => null,
            'directory' => null,
        ]);
        // The legacy client expects a plain-text handshake.
        $this->send($conn, 'Connection established.');
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if (! $this->clients->offsetExists($from)) {
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

        if (! in_array($type, ['join', 'history_before', 'message', 'reply_message', 'edit_message', 'delete_message', 'discuss_message', 'react_message', 'reaction_list', 'delete_chat', 'typing', 'approve', 'rename', 'rename_guest', 'set_anonymous', 'set_confirmation', 'kick', 'list_rooms', 'mentions_directory', 'mentions_search'], true)) {
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

        if ($type === 'delete_chat') {
            try { $this->deleteChat($from, $data); }
            catch (Throwable $error) {
                error_log('Chat storage failed: ' . $error->getMessage());
                $this->error($from, 'Unable to delete chat.');
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

            if ($type === 'history_before') {
                $this->historyBefore($from, $data);
                return;
            }

            if ($type === 'rename') {
                $this->rename($from, $data);

                return;
            }

            if ($type === 'rename_guest') {
                $this->renameGuest($from, $data);

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

            if ($type === 'edit_message') {
                $this->editMessage($from, $data);

                return;
            }

            if ($type === 'reply_message') {
                $this->replyMessage($from, $data);

                return;
            }

            if ($type === 'delete_message') {
                $this->deleteMessage($from, $data);

                return;
            }

            if ($type === 'discuss_message') {
                $this->discussMessage($from, $data);
                return;
            }

            if ($type === 'react_message') {
                $this->reactMessage($from, $data);
                return;
            }

            if ($type === 'reaction_list') {
                $this->reactionList($from, $data);
                return;
            }

            if ($type === 'typing') {
                $this->typing($from, ($data['typing'] ?? false) === true);

                return;
            }

            if ($type === 'mentions_directory' || $type === 'mentions_search') {
                $this->mentionCandidates($from, $data, $type === 'mentions_search');
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

        $chat = $this->storage->openChat(
            $origin,
            $channel,
            $this->privateInitialName($channel, $data['chatName'] ?? null),
            $this->isPrivateChannel($channel),
        );
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

        $previousUser = $state['user'];
        $state['roomKey'] = $roomKey;
        $state['chatId'] = $chat['id'];
        $state['channel'] = $channel;
        $state['version'] = $version;
        $state['user'] = $user;
        $state['participantId'] = $participantId;
        $state['typingUntil'] = 0;
        $alreadyAdmitted = $state['admission'] === 'admitted';
        $this->clients[$client] = $state;
        if (
            $alreadyAdmitted
            && str_starts_with($participantId, 'guest:')
            && ($previousUser['name'] ?? '') !== $user['name']
        ) {
            $nickname = trim($user['nickname']);
            $guestId = substr($participantId, 6);
            $this->storage->renameGuest($chat['id'], $guestId, $user['name']);
            $this->applyLiveGuestRename($roomKey, $guestId, $nickname, $user['name']);
            $this->broadcastGuestRename($roomKey, $channel, $guestId, $nickname, $user['name']);
        }
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
            $this->pending[$roomKey]->offsetSet($client);
            $this->send($client, [
                'version' => 2,
                'type' => 'join_pending',
                'channel' => $channel,
                'participantId' => $participantId,
            ]);
            $this->broadcastJoinRequest($roomKey, $participantId, $user);

            return;
        }

        $history = $this->storage->history($chat['id'], self::HISTORY_PAGE_SIZE + 1);
        $this->admit($client, $history, $sendJoinEvents ? 'joined' : null, ! $alreadyAdmitted);
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

        $state['directory'] = ['senderType' => $senderType, 'senderId' => $senderId, 'pagination' => $pagination];
        $this->clients[$client] = $state;
        $this->sendRoomDirectory($client);
    }

    private function sendRoomDirectory(ConnectionInterface $client): void
    {
        $state = $this->clients[$client];
        $directory = $state['directory'];
        if (! is_array($directory) || $state['origin'] === null) return;
        $rooms = $this->storage->listRooms($state['origin'], $directory['senderType'], $directory['senderId'], $directory['pagination']);

        if ($directory['senderType'] === 'guest') {
            $rooms['others'] = [
                'rows' => [],
                'total' => 0,
                'limit' => $directory['pagination']['others']['limit'],
                'offset' => $directory['pagination']['others']['offset'],
            ];
        }

        $this->send($client, [
            'version' => 2,
            'type' => 'rooms',
            'mine' => $rooms['mine'],
            'others' => $rooms['others'],
        ]);
    }

    private function refreshRoomDirectories(string $origin): void
    {
        foreach ($this->clients as $client) {
            $state = $this->clients[$client];
            if ($state['origin'] !== $origin || ! is_array($state['directory'])) continue;
            try { $this->sendRoomDirectory($client); }
            catch (Throwable $error) { error_log('Chat room directory refresh failed: ' . $error->getMessage()); }
        }
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

    private function mentionCandidates(ConnectionInterface $client, array $data, bool $search): void
    {
        $state = $this->clients[$client];
        $query = $search ? trim((string) ($data['query'] ?? '')) : '';
        if ($search && mb_strlen($query) < ChatMentions::SEARCH_MINIMUM) {
            $this->error($client, 'Enter at least 3 characters to search mentions.');
            return;
        }
        [$senderType, $senderId] = explode(':', $state['participantId'], 2);
        $candidates = $this->storage->mentionCandidates(
            $state['origin'], $senderType, $senderId, $query,
            $search ? ChatMentions::SEARCH_LIMIT : ChatMentions::DIRECTORY_LIMIT,
        );
        $this->send($client, ['version' => 2, 'type' => $search ? 'mentions_search' : 'mentions_directory', 'channel' => $state['channel'], 'query' => $query, 'candidates' => $candidates]);
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
            $history = $this->storage->history($pendingState['chatId'], self::HISTORY_PAGE_SIZE + 1);
            $this->admit($pendingClient, $history, 'join_approved', true);
        }
    }

    private function admit(ConnectionInterface $client, array $history, ?string $eventType, bool $recordEntry): void
    {
        $state = $this->clients[$client];
        $roomKey = $state['roomKey'];

        if (isset($this->pending[$roomKey])) {
            $this->pending[$roomKey]->offsetUnset($client);
        }

        $this->channels[$roomKey]->offsetSet($client);
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

        if ($recordEntry) {
            $this->storePresenceMessage($state, 'enter');
        }

        $this->broadcastOriginPresence($state['origin']);
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
        $this->refreshRoomDirectories($state['origin']);
    }

    private function renameGuest(ConnectionInterface $client, array $data): void
    {
        $state = $this->clients[$client];
        $guestId = $data['guestId'] ?? '';
        $nickname = trim(is_string($data['name'] ?? null) ? $data['name'] : '');

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', (string) $guestId)) {
            $this->error($client, 'A valid guest identity is required.');
            return;
        }
        if ($nickname === '' || mb_strlen($nickname) > 25) {
            $this->error($client, 'A guest nickname of 1–25 characters is required.');
            return;
        }

        $guestId = strtolower((string) $guestId);
        $name = $this->guestDisplayName($guestId, $nickname);
        $this->storage->renameGuest($state['chatId'], $guestId, $name);
        $this->applyLiveGuestRename($state['roomKey'], $guestId, $nickname, $name);
        $this->broadcastGuestRename($state['roomKey'], $state['channel'], $guestId, $nickname, $name);
        $this->broadcastPresence($state['roomKey']);
    }

    private function broadcastGuestRename(string $roomKey, string $channel, string $guestId, string $nickname, string $name): void
    {
        $this->broadcast($roomKey, fn () => [
            'version' => 2,
            'type' => 'guest_renamed',
            'channel' => $channel,
            'participantId' => 'guest:' . $guestId,
            'guestId' => $guestId,
            'nickname' => $nickname,
            'name' => $name,
        ]);
    }

    private function applyLiveGuestRename(string $roomKey, string $guestId, string $nickname, string $name): void
    {
        foreach ([$this->channels[$roomKey] ?? null, $this->pending[$roomKey] ?? null] as $clients) {
            if (! $clients instanceof SplObjectStorage) continue;
            foreach ($clients as $participant) {
                $participantState = $this->clients[$participant];
                if ($participantState['participantId'] !== 'guest:' . $guestId) continue;
                $participantState['user']['nickname'] = $nickname;
                $participantState['user']['name'] = $name;
                $this->clients[$participant] = $participantState;
            }
        }
    }

    private function guestDisplayName(string $guestId, string $nickname): string
    {
        $base = 'Guest ' . substr($guestId, 0, 8);
        return $nickname !== $base ? $nickname . ' (' . $base . ')' : $base;
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
            $storedParticipant = null;
            foreach ($this->storage->participantSnapshots($state['chatId']) as $participant) {
                if ($participant['senderType'] . ':' . $participant['senderId'] === $participantId) {
                    $storedParticipant = $participant;
                    break;
                }
            }
            if ($storedParticipant === null) {
                $this->error($client, 'That participant does not belong to this chat.');
                return;
            }
            $targetName = $storedParticipant['senderName'] ?: $participantId;
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
            $targetState = $this->clients[$target];
            $targetState['admission'] = 'kicked';
            $this->clients[$target] = $targetState;
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

    private function editMessage(ConnectionInterface $client, array $data): void
    {
        $messageId = $this->positiveMessageId($data['messageId'] ?? null);

        if ($messageId === null) {
            $this->error($client, 'A valid positive message ID is required.');

            return;
        }

        $text = $data['text'] ?? null;

        if (! is_string($text) || trim($text) === '' || strlen($text) > 16000) {
            $this->error($client, 'Enter a message of at most 16000 bytes.');

            return;
        }

        $text = trim($text);

        if (ChatReply::hasCanonicalPrefix($text)) {
            $this->error($client, 'Reserved reply markup is not allowed.');

            return;
        }

        $state = $this->clients[$client];
        $stored = $this->storage->editMessage($state['chatId'], $messageId, $text);
        $this->broadcast($state['roomKey'], function (ConnectionInterface $recipient) use ($stored, $state, $messageId): array {
            $message = $this->protocolMessage($stored, $state['channel']);
            $message['reactions'] = $this->reactionSummaryFor($recipient, $messageId);
            return ['version' => 2, 'type' => 'message_edited', 'channel' => $state['channel'], 'message' => $message];
        });
    }

    private function deleteMessage(ConnectionInterface $client, array $data): void
    {
        $messageId = $this->positiveMessageId($data['messageId'] ?? null);

        if ($messageId === null) {
            $this->error($client, 'A valid positive message ID is required.');

            return;
        }

        $state = $this->clients[$client];

        if (! $this->storage->deleteMessage($state['chatId'], $messageId)) {
            throw new RuntimeException('Message not found.');
        }

        $this->broadcast($state['roomKey'], fn (): array => [
            'version' => 2,
            'type' => 'message_deleted',
            'channel' => $state['channel'],
            'messageId' => (string) $messageId,
        ]);
        $this->notifyDiscussionParent($state['chatId']);
    }

    private function deleteChat(ConnectionInterface $client, array $data): void
    {
        $channel = $data['channel'] ?? '';
        if (!is_string($channel) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $channel)) {
            $this->error($client, 'A valid channel is required.');
            return;
        }
        $origin = $this->clients[$client]['origin'];
        if ($origin === null) {
            $this->error($client, 'A valid HTTP Origin header is required.');
            return;
        }
        $result = $this->storage->deleteChat($origin, $channel);
        $deleted = array_fill_keys($result['channels'], true);
        $targets = [];
        foreach ($this->clients as $connection) {
            $state = $this->clients[$connection];
            if ($state['origin'] === $origin && isset($deleted[$state['channel'] ?? ''])) $targets[] = $connection;
        }
        $event = ['version' => 2, 'type' => 'chat_deleted', 'channel' => $channel, 'deletedChannels' => array_values($result['channels'])];
        if (($result['restored'] ?? null) !== null) {
            $restored = $result['restored'];
            $this->broadcast($this->roomKey($restored['origin'], $restored['channel']), fn (): array => [
                'version' => 2, 'type' => 'discussion_removed', 'channel' => $restored['channel'],
                'sourceMessageId' => (string) $restored['sourceMessageId'],
            ]);
        }
        if (!in_array($client, $targets, true)) $this->send($client, $event);
        foreach ($targets as $target) {
            $this->send($target, $event);
            $target->close();
        }
    }

    private function discussMessage(ConnectionInterface $client, array $data): void
    {
        $messageId = $this->positiveMessageId($data['messageId'] ?? null);
        if ($messageId === null) {
            $this->error($client, 'A valid positive message ID is required.');
            return;
        }
        $state = $this->clients[$client];
        $result = $this->storage->createDiscussion($state['chatId'], $messageId);
        $discussion = [
            'channel' => $result['chat']['channel'],
            'responseCount' => $result['responseCount'],
        ];
        $this->broadcast($state['roomKey'], fn (): array => [
            'version' => 2,
            'type' => 'discussion_ready',
            'channel' => $state['channel'],
            'sourceMessageId' => (string) $messageId,
            'discussion' => $discussion,
        ]);
        $this->refreshRoomDirectories($state['origin']);
    }

    private function positiveMessageId(mixed $value): ?int
    {
        if (! (is_int($value) || is_string($value)) || ! ctype_digit((string) $value)) {
            return null;
        }

        $messageId = (int) $value;

        return $messageId > 0 ? $messageId : null;
    }

    private function message(ConnectionInterface $client, array $data, mixed $text, int $version): void
    {
        if (! is_string($text) || trim($text) === '' || strlen($text) > 16000) {
            $this->error($client, 'Enter a message of at most 16000 bytes.');

            return;
        }

        $text = trim($text);

        if (ChatReply::hasCanonicalPrefix($text)) {
            $this->error($client, 'Reserved reply markup is not allowed.');

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
        $ordinary = [
            'senderType' => $senderType,
            'senderId' => $senderId,
            'senderName' => $state['user']['name'],
            'senderEmblem' => $state['user']['emblem'],
            'text' => $text,
            'createdAt' => $this->nowMilliseconds(),
            'clientMessageId' => $clientMessageId,
        ];

        $requested = ChatMentions::requested($data['mentions'] ?? null);
        if ($requested === null) {
            $this->error($client, 'Provide at most 22 valid mention targets.');
            return;
        }
        $targets = [];
        if ($requested !== []) {
            $available = $this->storage->mentionCandidates($state['origin'], $senderType, $senderId, '', 100000);
            $byIdentity = [];
            foreach ($available as $candidate) $byIdentity[$candidate['senderType'] . ':' . $candidate['senderId']] = $candidate;
            foreach ($requested as $target) {
                $key = $target['senderType'] . ':' . $target['senderId'];
                if ($key === $state['participantId']) {
                    $this->error($client, 'You cannot mention yourself.');
                    return;
                }
                if (! isset($byIdentity[$key])) {
                    $this->error($client, 'Mention target was not found in this origin.');
                    return;
                }
                $candidate = $byIdentity[$key];
                $targets[] = [
                    'senderType' => 'user_service', 'senderId' => $candidate['senderId'],
                    'senderName' => $candidate['senderName'], 'senderEmblem' => $candidate['senderEmblem'],
                    'text' => 'MENTION:' . rawurlencode((string) json_encode([
                        'target' => $candidate['senderName'],
                        'caller' => $state['user']['name'],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
                    'createdAt' => $this->nowMilliseconds(), 'clientMessageId' => $clientMessageId,
                ];
            }
        }

        $stored = $targets === []
            ? $this->storage->appendMessage($state['chatId'], $ordinary)
            : $this->storage->appendMessageGroup($state['chatId'], $ordinary, $targets);

        if (! $stored['inserted']) {
            return;
        }

        $storedMessages = $targets === [] ? [$stored['message']] : $stored['messages'];
        foreach ($storedMessages as $storedMessage) {
            $liveUser = $storedMessage['senderType'] === $senderType && $storedMessage['senderId'] === $senderId ? $state['user'] : null;
            $message = $this->protocolMessage($storedMessage, $state['channel'], $liveUser);
            $this->broadcast($state['roomKey'], fn ($recipient) => $this->clients[$recipient]['version'] === 2
                ? ['version' => 2, 'type' => 'message', 'channel' => $state['channel'], 'message' => $message]
                : $message);
        }
        $this->broadcastPresence($state['roomKey']);
        $this->notifyDiscussionParent($state['chatId']);
        $this->refreshRoomDirectories($state['origin']);
    }

    private function replyMessage(ConnectionInterface $client, array $data): void
    {
        $parentMessageId = $this->positiveMessageId($data['replyToMessageId'] ?? null);

        if ($parentMessageId === null) {
            $this->error($client, 'A valid positive reply message ID is required.');

            return;
        }

        $text = $data['msg'] ?? null;

        if (! is_string($text) || trim($text) === '' || strlen($text) > 16000) {
            $this->error($client, 'Enter a message of at most 16000 bytes.');

            return;
        }

        $clientMessageId = $data['clientMessageId'] ?? null;

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
        $stored = $this->storage->appendReply($state['chatId'], $parentMessageId, [
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
        $this->notifyDiscussionParent($state['chatId']);
    }

    private function notifyDiscussionParent(int $chatId): void
    {
        $parent = $this->storage->discussionParent($chatId);
        if ($parent === null) return;
        $roomKey = $this->roomKey($parent['origin'], $parent['channel']);
        $this->broadcast($roomKey, fn (): array => [
            'version' => 2,
            'type' => 'discussion_ready',
            'channel' => $parent['channel'],
            'sourceMessageId' => (string) $parent['sourceMessageId'],
            'discussion' => [
                'channel' => 'discussion_' . $parent['sourceMessageId'],
                'responseCount' => $parent['responseCount'],
            ],
        ]);
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
        $hasOlder = count($storedMessages) > self::HISTORY_PAGE_SIZE;
        if ($hasOlder) $storedMessages = array_slice($storedMessages, -self::HISTORY_PAGE_SIZE);
        $chatId = $this->clients[$client]['chatId'];
        $reactionSummaries = $this->reactionSummariesFor($client, array_column($storedMessages, 'id'));
        $messages = array_map(
            function (array $message) use ($channel, $chatId, $reactionSummaries): array {
                $wire = $this->protocolMessage($message, $channel);
                $wire['discussion'] = $this->storage->discussionForMessage($chatId, $message['id']);
                $wire['reactions'] = $reactionSummaries[$message['id']];
                return $wire;
            },
            $storedMessages,
        );

        $this->send($client, $this->clients[$client]['version'] === 2
            ? ['version' => 2, 'type' => 'history', 'channel' => $channel, 'messages' => $messages, 'hasOlder' => $hasOlder]
            : array_map(fn (array $message): string => json_encode($message, JSON_INVALID_UTF8_SUBSTITUTE), $messages));
    }

    private function historyBefore(ConnectionInterface $client, array $data): void
    {
        $beforeMessageId = $data['beforeMessageId'] ?? null;
        if (! is_string($beforeMessageId) && ! is_int($beforeMessageId)) {
            $this->error($client, 'A valid message ID is required.');
            return;
        }
        $beforeMessageId = (string) $beforeMessageId;
        if (! ctype_digit($beforeMessageId) || (int) $beforeMessageId < 1) {
            $this->error($client, 'A valid message ID is required.');
            return;
        }

        $state = $this->clients[$client];
        $storedMessages = $this->storage->history($state['chatId'], self::HISTORY_PAGE_SIZE + 1, (int) $beforeMessageId);
        $hasOlder = count($storedMessages) > self::HISTORY_PAGE_SIZE;
        if ($hasOlder) $storedMessages = array_slice($storedMessages, -self::HISTORY_PAGE_SIZE);
        $chatId = $state['chatId'];
        $reactionSummaries = $this->reactionSummariesFor($client, array_column($storedMessages, 'id'));
        $messages = array_map(function (array $message) use ($state, $chatId, $reactionSummaries): array {
            $wire = $this->protocolMessage($message, $state['channel']);
            $wire['discussion'] = $this->storage->discussionForMessage($chatId, $message['id']);
            $wire['reactions'] = $reactionSummaries[$message['id']];
            return $wire;
        }, $storedMessages);
        $this->send($client, [
            'version' => 2,
            'type' => 'history_older',
            'channel' => $state['channel'],
            'messages' => $messages,
            'hasOlder' => $hasOlder,
        ]);
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
            'senderType' => $stored['senderType'],
            'senderId' => $stored['senderId'],
            'reactions' => ['positiveCount' => 0, 'negativeCount' => 0, 'currentReaction' => 0],
        ];
    }

    private function reactMessage(ConnectionInterface $client, array $data): void
    {
        $messageId = $this->positiveMessageId($data['messageId'] ?? null);
        $reaction = $data['reaction'] ?? null;
        if ($messageId === null || ! is_int($reaction) || ! in_array($reaction, [-1, 1], true)) {
            $this->error($client, 'A valid message ID and reaction are required.');
            return;
        }
        $state = $this->clients[$client];
        [$senderType, $senderId] = explode(':', $state['participantId'], 2);
        $this->storage->toggleReaction($state['chatId'], $messageId, $senderType, $senderId, $reaction, $this->nowMilliseconds());
        $this->broadcast($state['roomKey'], fn (ConnectionInterface $recipient): array => [
            'version' => 2, 'type' => 'reaction_changed', 'channel' => $state['channel'],
            'messageId' => (string) $messageId, 'reactions' => $this->reactionSummaryFor($recipient, $messageId),
        ]);
    }

    private function reactionList(ConnectionInterface $client, array $data): void
    {
        $messageId = $this->positiveMessageId($data['messageId'] ?? null);
        if ($messageId === null) { $this->error($client, 'A valid message ID is required.'); return; }
        $state = $this->clients[$client];
        $people = $this->storage->reactionPeople($state['chatId'], $messageId);
        $this->send($client, ['version' => 2, 'type' => 'reaction_list', 'channel' => $state['channel'], 'messageId' => (string) $messageId] + $people);
    }

    private function reactionSummaryFor(ConnectionInterface $client, int $messageId): array
    {
        $state = $this->clients[$client];
        [$senderType, $senderId] = explode(':', $state['participantId'], 2);
        return $this->storage->reactionSummary($state['chatId'], $messageId, $senderType, $senderId);
    }

    private function reactionSummariesFor(ConnectionInterface $client, array $messageIds): array
    {
        $state = $this->clients[$client];
        [$senderType, $senderId] = explode(':', $state['participantId'], 2);
        return $this->storage->reactionSummaries($state['chatId'], $messageIds, $senderType, $senderId);
    }

    private function broadcastPresence(string $roomKey): void
    {
        if (! isset($this->channels[$roomKey], $this->rooms[$roomKey])) {
            return;
        }

        $people = [];
        $room = $this->rooms[$roomKey];
        foreach ($this->storage->participantSnapshots($room['chatId']) as $snapshot) {
            $id = $snapshot['senderType'] . ':' . $snapshot['senderId'];
            $people[$id] = [
                'id' => $id,
                'user' => $snapshot['senderType'] === 'user'
                    ? ['id' => $snapshot['senderId'], 'nickname' => $snapshot['senderName'], 'name' => $snapshot['senderName'], 'emblem' => $snapshot['senderEmblem']]
                    : ['id' => null, 'guestId' => $snapshot['senderId'], 'nickname' => $snapshot['senderName'], 'name' => $snapshot['senderName'], 'emblem' => $snapshot['senderEmblem']],
                'online' => false,
                'inChat' => false,
                'typing' => false,
            ];
        }

        foreach ($this->clients as $client) {
            $state = $this->clients[$client];
            if ($state['origin'] !== $room['origin'] || $state['admission'] !== 'admitted' || $state['participantId'] === null) continue;
            $id = $state['participantId'];
            $inChat = $state['roomKey'] === $roomKey;
            if (! isset($people[$id]) && ! $inChat) continue;
            if (! isset($people[$id])) {
                $people[$id] = [
                    'id' => $id,
                    'user' => $state['user'],
                    'online' => true,
                    'inChat' => true,
                    'typing' => false,
                ];
            }
            $people[$id]['user'] = $state['user'];
            $people[$id]['online'] = true;
            $people[$id]['inChat'] = $people[$id]['inChat'] || $inChat;
            $people[$id]['typing'] = $people[$id]['typing'] || ($inChat && $state['typingUntil'] > microtime(true));
        }

        $channel = $room['channel'];
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

    private function broadcastOriginPresence(string $origin): void
    {
        foreach ($this->rooms as $roomKey => $room) {
            if ($room['origin'] === $origin) {
                $this->broadcastPresence((string) $roomKey);
            }
        }
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
        $departingState = $state;
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
            $this->pending[$roomKey]->offsetUnset($client);
        }

        if (in_array($admission, ['admitted', 'kicked'], true) && isset($this->channels[$roomKey])) {
            $this->channels[$roomKey]->offsetUnset($client);
            if ($admission === 'admitted') $this->storePresenceMessage($departingState, 'leave', $roomKey);
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
        if (in_array($admission, ['admitted', 'kicked'], true)) {
            $this->broadcastOriginPresence($state['origin']);
        }
    }

    private function storePresenceMessage(array $state, string $action, ?string $roomKey = null): void
    {
        if ($state['chatId'] === null || $state['channel'] === null || $state['participantId'] === null || ! in_array($action, ['enter', 'leave'], true)) return;
        [, $senderId] = explode(':', $state['participantId'], 2);
        $createdAt = $this->nowMilliseconds();
        $stored = $this->storage->appendMessage($state['chatId'], [
            'senderType' => 'user_service',
            'senderId' => $senderId,
            'senderName' => $state['user']['name'],
            'senderEmblem' => $state['user']['emblem'],
            'text' => 'PRESENCE:' . rawurlencode((string) json_encode(['action' => $action, 'name' => $state['user']['name']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            'createdAt' => $createdAt,
            'clientMessageId' => 'presence-' . $this->instanceId . '-' . ++$this->messageSequence,
        ]);
        if (! $stored['inserted']) return;
        $message = $this->protocolMessage($stored['message'], $state['channel']);
        $targetRoom = $roomKey ?? $state['roomKey'];
        if ($targetRoom !== null) {
            $this->broadcast($targetRoom, fn ($recipient) => $this->clients[$recipient]['version'] === 2
                ? ['version' => 2, 'type' => 'message', 'channel' => $state['channel'], 'message' => $message]
                : $message);
        }
        $this->refreshRoomDirectories($state['origin']);
    }

    private function broadcast(string $roomKey, callable $payload): void
    {
        if (! isset($this->channels[$roomKey])) {
            return;
        }

        foreach (iterator_to_array($this->channels[$roomKey]) as $client) {
            if ($this->clients->offsetExists($client)) {
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

    private function privateInitialName(string $channel, mixed $name): ?string
    {
        if (! is_string($name)) {
            return null;
        }

        $name = trim($name);
        if (! $this->isPrivateChannel($channel) || ! str_starts_with($name, 'PRIVATE: ') || trim(substr($name, 9)) === '' || mb_strlen($name) > 200) {
            return null;
        }

        return $name;
    }

    private function isPrivateChannel(string $channel): bool
    {
        $guest = '(?:[0-9]+|[0-9a-f]{8}-[0-9a-f-]{27})';
        return preg_match('/^[1-9][0-9]*_[1-9][0-9]*$/', $channel) === 1
            || preg_match('/^[1-9][0-9]*_guest_' . $guest . '$/', $channel) === 1
            || preg_match('/^guest_' . $guest . '_guest_' . $guest . '$/', $channel) === 1;
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
        if ($this->clients->offsetExists($conn)) {
            $this->leave($conn);
            $this->clients->offsetUnset($conn);
        }
    }

    public function onError(ConnectionInterface $conn, Exception $e): void
    {
        error_log('Chat connection failed: ' . $e->getMessage());
        $this->onClose($conn);
        $conn->close();
    }
}
