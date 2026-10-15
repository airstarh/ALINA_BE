<?php

declare(strict_types=1);

namespace Cam;

use Closure;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use React\EventLoop\LoopInterface;
use SplObjectStorage;
use Throwable;

final class TalkWebSocket implements MessageComponentInterface
{
    private const MAXIMUM_PCM_MESSAGE_BYTES = 65_536;
    private const IDLE_SECONDS = 60;

    /** @var SplObjectStorage<ConnectionInterface, array<string, mixed>> */
    private SplObjectStorage $clients;

    public function __construct(
        private Config $config,
        private TalkToken $tokens,
        private TalkSessionRegistry $registry,
        private Closure $sessionFactory,
        private Closure $converterFactory,
        private LoopInterface $loop,
    ) {
        $this->clients = new SplObjectStorage();
        $this->loop->addPeriodicTimer(0.02, fn () => $this->tick());
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $cameraId = null;
        try {
            if (!$this->sameOrigin($conn)) throw new \InvalidArgumentException('Camera talkback origin was rejected.');
            $query = [];
            parse_str($conn->httpRequest->getUri()->getQuery(), $query);
            $token = $query['token'] ?? null;
            if (!is_string($token)) throw new \InvalidArgumentException('Camera talkback token is required.');
            $cameraId = $this->tokens->verify($token, time());
            $camera = $this->config->camera($cameraId);
            if (($camera['talkback']['speaker'] ?? false) !== true) {
                throw new \InvalidArgumentException('This camera does not support talkback.');
            }
            if (!$this->registry->acquire($cameraId, $conn)) {
                $this->sendState($conn, 'busy', 'Another browser is already talking through this camera.');
                $conn->close();
                return;
            }

            $this->clients->attach($conn, [
                'camera' => $cameraId,
                'session' => null,
                'converter' => null,
                'lastInput' => microtime(true),
                'closing' => false,
            ]);
            $this->sendState($conn, 'ready');

            $session = ($this->sessionFactory)($cameraId, $camera);
            $session->open($camera['dvrip']['username'], $camera['dvrip']['password']);
            $state = $this->clients[$conn];
            $state['session'] = $session;
            $this->clients[$conn] = $state;
            $converter = ($this->converterFactory)();
            $converter->open();
            $state = $this->clients[$conn];
            $state['converter'] = $converter;
            $this->clients[$conn] = $state;
            $this->sendState($conn, 'active');
        } catch (Throwable $error) {
            if ($cameraId !== null && !$this->clients->contains($conn)) {
                $this->registry->release($cameraId, $conn);
            }
            $this->sendState($conn, 'error', $this->safeMessage($error));
            $this->cleanup($conn, false);
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        if (!$this->clients->contains($from)) return;
        try {
            if (!is_object($msg) || !method_exists($msg, 'isBinary') || !$msg->isBinary()) {
                throw new \InvalidArgumentException('Camera talkback accepts binary microphone audio only.');
            }
            $pcm = (string) $msg;
            if ($pcm === '' || strlen($pcm) > self::MAXIMUM_PCM_MESSAGE_BYTES || strlen($pcm) % 2 !== 0) {
                throw new \InvalidArgumentException('Camera talkback audio frame is invalid.');
            }
            $state = $this->clients[$from];
            $state['converter']->push($pcm);
            $state['lastInput'] = microtime(true);
            $this->clients[$from] = $state;
        } catch (Throwable $error) {
            $this->sendState($from, 'error', $this->safeMessage($error));
            $this->cleanup($from, false);
            $from->close();
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->cleanup($conn, false);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->sendState($conn, 'error', 'Camera talkback connection failed.');
        $this->cleanup($conn, false);
        $conn->close();
    }

    private function tick(): void
    {
        foreach ($this->clients as $connection) {
            try {
                $state = $this->clients[$connection];
                if (microtime(true) - $state['lastInput'] > self::IDLE_SECONDS) {
                    $this->sendState($connection, 'closed', 'Talkback closed after one minute without microphone audio.');
                    $this->cleanup($connection, false);
                    $connection->close();
                    continue;
                }
                if ($state['converter'] === null || $state['session'] === null) continue;
                foreach ($state['converter']->drain() as $frame) $state['session']->send($frame);
                $state['session']->drain();
            } catch (Throwable $error) {
                $this->sendState($connection, 'error', $this->safeMessage($error));
                $this->cleanup($connection, false);
                $connection->close();
            }
        }
    }

    private function cleanup(ConnectionInterface $conn, bool $notify): void
    {
        if (!$this->clients->contains($conn)) return;
        $state = $this->clients[$conn];
        $this->clients->detach($conn);
        try { $state['converter']?->close(); } catch (Throwable) {}
        try { $state['session']?->close(); } catch (Throwable) {}
        $this->registry->release($state['camera'], $conn);
        if ($notify) $this->sendState($conn, 'closed');
    }

    private function sameOrigin(ConnectionInterface $conn): bool
    {
        $origin = trim($conn->httpRequest->getHeaderLine('Origin'));
        $host = strtolower(trim($conn->httpRequest->getHeaderLine('Host')));
        if ($origin === '' || $host === '') return false;
        $parts = parse_url($origin);
        if (!is_array($parts) || !in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || !is_string($parts['host'] ?? null)) return false;
        $originHost = strtolower($parts['host']);
        if (isset($parts['port'])) $originHost .= ':' . $parts['port'];
        return hash_equals($host, $originHost);
    }

    private function sendState(ConnectionInterface $conn, string $state, string $message = ''): void
    {
        $conn->send(json_encode(['state' => $state, 'message' => $message], JSON_THROW_ON_ERROR));
    }

    private function safeMessage(Throwable $error): string
    {
        return $error instanceof \InvalidArgumentException
            ? $error->getMessage()
            : 'Camera talkback failed.';
    }
}
