<?php

declare(strict_types=1);

use Cam\Config;
use Cam\TalkSessionRegistry;
use Cam\TalkToken;
use Cam\TalkWebSocket;
use GuzzleHttp\Psr7\ServerRequest;
use Ratchet\ConnectionInterface;
use React\EventLoop\Factory;

require_once dirname(__DIR__, 7) . '/srv/alina/vendor/autoload.php';
foreach (['Config.php', 'TalkToken.php', 'TalkSessionRegistry.php', 'TalkWebSocket.php'] as $file) {
    require_once dirname(__DIR__) . '/php/' . $file;
}

final class TalkTestConnection implements ConnectionInterface
{
    public array $messages = [];
    public bool $closed = false;
    public ServerRequest $httpRequest;

    public function __construct(string $token, string $origin = 'https://zero.test:50443')
    {
        $this->httpRequest = new ServerRequest(
            'GET',
            '/cam-talk?token=' . rawurlencode($token),
            ['Origin' => $origin, 'Host' => 'zero.test:50443'],
        );
    }

    public function send($data): ConnectionInterface
    {
        $this->messages[] = json_decode((string) $data, true, flags: JSON_THROW_ON_ERROR);
        return $this;
    }

    public function close(): void
    {
        $this->closed = true;
    }
}

function talkWebSocketFixture(): array
{
    $path = tempnam(sys_get_temp_dir(), 'cam-talk-ws-');
    if ($path === false) throw new RuntimeException('could not create talk websocket fixture');
    $config = [
        'default' => 'balcon',
        'cameras' => [
            'balcon' => [
                'name' => 'Balcon', 'host' => '192.0.2.21',
                'talkback' => ['speaker' => true],
                'dvrip' => ['port' => 34567, 'username' => 'admin', 'password' => 'secret'],
            ],
        ],
    ];
    file_put_contents($path, "<?php\nreturn " . var_export($config, true) . ";\n");
    $tokens = new TalkToken(str_repeat('w', 32), 30);
    $sessions = [];
    $handler = new TalkWebSocket(
        Config::load($path),
        $tokens,
        new TalkSessionRegistry(),
        static function () use (&$sessions): object {
            $session = new class {
                public bool $opened = false;
                public bool $closed = false;
                public function open(string $username, string $password): void { $this->opened = true; }
                public function send(string $frame): void {}
                public function drain(): int { return 0; }
                public function close(): void { $this->closed = true; }
            };
            $sessions[] = $session;
            return $session;
        },
        static fn (): object => new class {
            public bool $opened = false;
            public bool $closed = false;
            public function open(): void { $this->opened = true; }
            public function push(string $pcm): void {}
            public function drain(): array { return []; }
            public function close(): void { $this->closed = true; }
        },
        Factory::create(),
    );
    return [$handler, $tokens, $path, &$sessions];
}

test('talk websocket preserves binary frame metadata through the Ratchet adapter', function (): void {
    [$handler, , $path] = talkWebSocketFixture();
    try {
        assertSameValue(true, $handler instanceof \Ratchet\WebSocket\MessageComponentInterface);
    } finally {
        unlink($path);
    }
});

test('talk websocket activates one valid-token owner and reports a second as busy', function (): void {
    [$handler, $tokens, $path, $sessions] = talkWebSocketFixture();
    try {
        $token = $tokens->issue('balcon', time())['token'];
        $first = new TalkTestConnection($token);
        $handler->onOpen($first);
        assertSameValue(['ready', 'active'], array_column($first->messages, 'state'));
        assertSameValue(false, $first->closed);

        $second = new TalkTestConnection($token);
        $handler->onOpen($second);
        assertSameValue('busy', $second->messages[0]['state']);
        assertSameValue(true, $second->closed);

        $handler->onClose($first);
        $third = new TalkTestConnection($token);
        $handler->onOpen($third);
        assertSameValue('active', $third->messages[1]['state']);
        $handler->onClose($third);
    } finally {
        unlink($path);
    }
});

test('talk websocket accepts a valid camera token from any browser origin', function (): void {
    [$handler, $tokens, $path, $sessions] = talkWebSocketFixture();
    try {
        $connection = new TalkTestConnection(
            $tokens->issue('balcon', time())['token'],
            'https://attacker.test',
        );
        $handler->onOpen($connection);
        assertSameValue(['ready', 'active'], array_column($connection->messages, 'state'));
        assertSameValue(false, $connection->closed);
        $handler->onClose($connection);
    } finally {
        unlink($path);
    }
});
