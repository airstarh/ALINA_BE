<?php

declare(strict_types=1);

namespace alina\Services\Chat;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SocketServer;
use Throwable;

class ChatServerRunner
{
    private string $pidFile;
    private int $port;
    private string $host;

    private function __construct(
        string $pidFile = '/tmp/chat-server.pid',
        string $host = '0.0.0.0',
        int $port = 8080
    ) {
        $this->pidFile = $pidFile;
        $this->host    = $host;
        $this->port    = $port;
    }

    /**
     * Entry point: run the WebSocket server with safety checks.
     */
    private function run(): never
    {
        if ($this->isAnotherInstanceRunning()) {
            exit(1);
        }

        if ($this->isPortInUse($this->host, $this->port)) {
            fwrite(STDERR, "Error: Port {$this->port} is already in use.\n");
            exit(1);
        }

        $this->writePidFile();

        try {
            // Создаём цикл событий
            $loop = Factory::create();

            $chatHandler = new ChatServer();
            $webSocket   = new WsServer($chatHandler);
            $httpServer  = new HttpServer($webSocket);

            // В ReactPHP v1: НЕ передаём $loop вторым аргументом в SocketServer
            $socket = new SocketServer("{$this->host}:{$this->port}");

            // Передаём $loop третьим аргументом в IoServer — это обязательно
            $server = new IoServer($httpServer, $socket, $loop);

            echo "WebSocket server listening on {$this->host}:{$this->port} (PID: " . getmypid() . ")\n";

            $server->run();
        }
        catch (Throwable $e) {
            $this->removePidFile();

            throw $e;
        }
    }

    private function isAnotherInstanceRunning(): bool
    {
        if (! file_exists($this->pidFile)) {
            return false;
        }

        $existingPid = (int) file_get_contents($this->pidFile);

        if ($existingPid <= 0) {
            $this->removePidFile();

            return false;
        }

        if (is_dir("/proc/{$existingPid}")) {
            echo "Another instance is already running (PID: {$existingPid}). Exiting.\n";

            return true;
        }

        $this->removePidFile();

        return false;
    }

    private static function isPortInUse(string $host, int $port, float $timeout = 1.0): bool
    {
        $sock = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if ($sock === false) {
            return false;
        }
        fclose($sock);

        return true;
    }

    private function writePidFile(): void
    {
        $dir = dirname($this->pidFile);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->pidFile, (string) getmypid());
        register_shutdown_function(fn () => $this->removePidFile());
    }

    private function removePidFile(): void
    {
        if (file_exists($this->pidFile)) {
            @unlink($this->pidFile);
        }
    }

    public static function go()
    {
        $runner = new static(
            pidFile: '/tmp/chat-server.pid',
            host: '0.0.0.0',
            port: 8080
        );

        $runner->run();
    }
}
