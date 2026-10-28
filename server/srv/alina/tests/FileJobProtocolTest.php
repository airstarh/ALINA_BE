<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Services/FileJob/FileJobStorage.php';
require_once __DIR__ . '/../Services/FileJob/FileJobServer.php';

use alina\Services\FileJob\FileJobServer;
use alina\Services\FileJob\FileJobStorage;
use GuzzleHttp\Psr7\Request;
use Ratchet\ConnectionInterface;

final class FileJobMemoryStorage implements FileJobStorage
{
    public array $snapshot = ['publicId'=>'job-1','type'=>'video_transcode','status'=>'queued','progress'=>0,'error'=>null,'createdAt'=>1,'startedAt'=>null,'finishedAt'=>null,'updatedAt'=>1];
    public function authorizedSnapshot(string $publicId, int $userId, string $token, int $now): ?array { return $publicId === 'job-1' && $userId === 17 && $token === 'valid-token-123' ? $this->snapshot : null; }
    public function snapshot(string $publicId): ?array { return $publicId === 'job-1' ? $this->snapshot : null; }
    public function wakeSecretMatches(string $publicId, string $secret): bool { return false; }
    public function claim(string $publicId, string $workerId, int $now, int $leaseSeconds): ?object { return null; }
    public function claimNext(string $workerId, int $now, int $leaseSeconds): ?object { return null; }
    public function updateProgress(int $jobId, string $workerId, int $progress, int $now, int $leaseSeconds): bool { return false; }
    public function complete(int $jobId, string $workerId, int $now): bool { return false; }
    public function retryOrFail(int $jobId, string $workerId, string $publicError, string $privateError, int $now, int $maxAttempts): string { return 'ignored'; }
}

final class FileJobTestClient implements ConnectionInterface
{
    public array $received = [];
    public bool $closed = false;
    public ?Request $httpRequest;
    public function __construct(public int $resourceId) { $this->httpRequest = new Request('GET', 'http://socket.local/ws-file'); }
    public function send($data) { $this->received[] = (string) $data; return $this; }
    public function close(): void { $this->closed = true; }
    public function last(string $type): ?array { foreach (array_reverse($this->received) as $raw) { $event=json_decode($raw,true); if (($event['type']??null)===$type) return $event; } return null; }
}

function fileJobProtocolExpect(bool $condition, string $message): void { if (! $condition) throw new RuntimeException($message); }
function fileJobProtocolSend(FileJobServer $server, FileJobTestClient $client, array|string $payload): void { $server->onMessage($client, is_array($payload) ? json_encode($payload) : $payload); }

$storage = new FileJobMemoryStorage();
$server = new FileJobServer($storage, static fn (): int => 500);
$owner = new FileJobTestClient(1);
$intruder = new FileJobTestClient(2);
$server->onOpen($owner);
$server->onOpen($intruder);

fileJobProtocolSend($server, $owner, '{broken');
fileJobProtocolExpect($owner->last('error')['message'] === 'Invalid file job payload.', 'malformed JSON must be rejected');
fileJobProtocolSend($server, $intruder, ['type'=>'subscribe','publicId'=>'job-1','uid'=>99,'token'=>'valid-token-123']);
fileJobProtocolExpect($intruder->last('error')['message'] === 'File job not found or access denied.', 'wrong owner must not subscribe');
fileJobProtocolSend($server, $owner, ['type'=>'subscribe','publicId'=>'job-1','uid'=>17,'token'=>'valid-token-123']);
fileJobProtocolExpect($owner->last('subscribed')['job']['publicId'] === 'job-1', 'owner must receive current snapshot');

$storage->snapshot['status'] = 'processing';
$storage->snapshot['progress'] = 37;
$server->publishJob('job-1');
fileJobProtocolExpect($owner->last('job')['job']['progress'] === 37, 'subscriber must receive publications');
fileJobProtocolExpect($intruder->last('job') === null, 'unauthorized client must receive no publication');

fileJobProtocolSend($server, $owner, ['type'=>'status','publicId'=>'job-1']);
fileJobProtocolExpect($owner->last('job')['job']['status'] === 'processing', 'status must replay current state');
$server->onClose($owner);
$storage->snapshot['progress'] = 50;
$before = count($owner->received);
$server->publishJob('job-1');
fileJobProtocolExpect(count($owner->received) === $before, 'closed client must be detached');

echo "PASS: file job WebSocket protocol\n";
