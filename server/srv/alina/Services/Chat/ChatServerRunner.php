<?php

declare(strict_types=1);

namespace alina\Services\Chat;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Throwable;

class ChatServerRunner
{
    private string $pidFile;
    private int $port;
    private string $host;

    public function __construct(
        string $pidFile = '/var/run/chat-server.pid',
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
    public function run(): never
    {
        // 1. Check for existing instance via PID file
        if ($this->isAnotherInstanceRunning()) {
            exit(1);
        }

        // 2. Pre-check that the port is free (graceful failure instead of fatal error)
        if ($this->isPortInUse($this->host, $this->port)) {
            fwrite(STDERR, "Error: Port {$this->port} is already in use. Is another instance running?\n");
            exit(1);
        }

        // 3. Write PID and register cleanup
        $this->writePidFile();

        try {
            // 4. Start the server
            $chatHandler = new ChatServer();
            $webSocket   = new WsServer($chatHandler);
            $httpServer  = new HttpServer($webSocket);
            $socket      = new \React\Socket\SocketServer("{$this->host}:{$this->port}");
            $server      = new IoServer($httpServer, $socket);

            echo "WebSocket server listening on {$this->host}:{$this->port} (PID: " . getmypid() . ")\n";
            $server->run();
        }
        catch (Throwable $e) {
            // Ensure PID file is removed on unexpected exit
            $this->removePidFile();

            throw $e;
        }
    }

    /**
     * Check if another instance is already running using the PID file.
     */
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

        // Check if /proc/<pid> exists → process is alive
        if (is_dir("/proc/{$existingPid}")) {
            echo "Another instance is already running (PID: {$existingPid}). Exiting.\n";

            return true;
        }

        // Process is dead; clean up stale PID file
        $this->removePidFile();

        return false;
    }


    /**
     * Quick probe to see if something is listening on the given host/port.
     * This is a best-effort check; the real authority is the bind attempt.
     */
    public static function isPortInUse(string $host, int $port, float $timeout = 1.0): bool
    {
        $sock = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if ($sock === false) {
            return false;
        }
        fclose($sock);

        return true;
    }

    /**
     * Write the current process PID to the PID file.
     */
    private function writePidFile(): void
    {
        $dir = dirname($this->pidFile);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->pidFile, (string) getmypid());

        register_shutdown_function(fn () => $this->removePidFile());
    }

    /**
     * Remove the PID file if it exists.
     */
    private function removePidFile(): void
    {
        if (file_exists($this->pidFile)) {
            @unlink($this->pidFile);
        }
    }

    public static function go()
    {
        $runner = new static(
            pidFile: '/var/run/chat-server.pid', // works well in Debian-based containers
            host: '0.0.0.0',
            port: 8080
        );

        $runner->run();
    }
}
