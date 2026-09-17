<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
$server = new \alina\Services\Chat\ChatServer(new \React\EventLoop\StreamSelectLoop());
$publicUser = new ReflectionMethod($server, 'publicUser');
$uuid = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$guest = $publicUser->invoke($server, ['id' => 0, 'guestId' => $uuid, 'nickname' => 'Borg'], 123);
if (($guest['guestId'] ?? '') !== $uuid || $guest['name'] !== 'Borg (Guest aaaaaaaa)') throw new RuntimeException('Guest UUID/name missing');
$default = $publicUser->invoke($server, ['guestId' => $uuid, 'nickname' => 'Guest aaaaaaaa'], 456);
if ($default['name'] !== 'Guest aaaaaaaa') throw new RuntimeException('Default name duplicated');
$legacy = $publicUser->invoke($server, [], 123);
if ($legacy['name'] !== 'Guest 123') throw new RuntimeException('Legacy fallback broken');
$invalid = $publicUser->invoke($server, ['guestId' => '../bad'], 123);
if (!empty($invalid['guestId'])) throw new RuntimeException('Invalid UUID accepted');
echo "PASS guest UUID, nickname label, legacy fallback and invalid identity rejection\n";

class GuestIdentityClient implements \Ratchet\ConnectionInterface {
    public array $received = [];
    public function __construct(public int $resourceId) {}
    public function send($data) { $this->received[] = $data; return $this; }
    public function close() {}
}
$a = new GuestIdentityClient(1);
$b = new GuestIdentityClient(2);
foreach ([$a, $b] as $client) {
    $server->onOpen($client);
    $server->onMessage($client, json_encode(['version' => 2, 'type' => 'join', 'channel' => 'guest-test', 'CurrentUser' => ['guestId' => $uuid]]));
}
$presence = json_decode(end($b->received), true);
if (count($presence['participants']) !== 1 || $presence['selfId'] !== 'guest:' . $uuid) throw new RuntimeException('Guest tabs were not deduplicated');
$server->onClose($a);
$server->onMessage($b, json_encode(['version' => 2, 'type' => 'message', 'channel' => 'guest-test', 'msg' => 'hello', 'CurrentUser' => ['guestId' => $uuid, 'nickname' => 'New name']]));
$message = json_decode($b->received[count($b->received) - 2], true)['message'];
if ($message['participantId'] !== 'guest:' . $uuid || $message['CurrentUser']['name'] !== 'New name (Guest aaaaaaaa)') throw new RuntimeException('Message lost stable guest identity');
echo "PASS guest tabs share presence identity and renamed messages keep UUID\n";
