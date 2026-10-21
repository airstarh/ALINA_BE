<?php

declare(strict_types=1);

namespace Cam;

use Closure;
use DateTimeImmutable;
use RuntimeException;

final class CameraSnapshot
{
    private const MAX_BYTES = 4 * 1024 * 1024;

    private Closure $request;
    private Closure $clock;

    public function __construct(
        private Config $config,
        ?Closure $request = null,
        ?Closure $clock = null,
    ) {
        $this->request = $request ?? static function (string $url): array {
            $handle = curl_init($url);
            if ($handle === false) {
                throw new RuntimeException('Camera snapshot is unavailable.');
            }
            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_MAXFILESIZE => self::MAX_BYTES,
            ]);
            $body = curl_exec($handle);
            $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            curl_close($handle);

            return ['status' => $status, 'body' => $body];
        };
        $this->clock = $clock ?? static fn (): DateTimeImmutable => new DateTimeImmutable();
    }

    /** @return array{filename: string} */
    public function save(string $cameraId): array
    {
        $settings = $this->config->recording();
        if ($settings === null) {
            throw new RuntimeException('Camera recording storage is not configured.');
        }
        if (!is_dir($settings['storage']) || !is_writable($settings['storage'])) {
            throw new RuntimeException('Camera recording storage does not exist or is not writable.');
        }
        $jpeg = $this->capture($cameraId);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $now = ($this->clock)();
            $filename = $now->format('Y-m-d---H-i---Uu') . '---' . $cameraId . '.jpg';
            $path = $settings['storage'] . '/' . $filename;
            $output = @fopen($path, 'x+b');
            if ($output === false) {
                usleep(1);
                continue;
            }
            try {
                $offset = 0;
                while ($offset < strlen($jpeg)) {
                    $written = fwrite($output, substr($jpeg, $offset));
                    if ($written === false || $written === 0) {
                        throw new RuntimeException('Could not write the camera snapshot.');
                    }
                    $offset += $written;
                }
                fflush($output);
                fclose($output);
                $output = null;
                return ['filename' => $filename];
            } catch (\Throwable $error) {
                if (is_resource($output)) fclose($output);
                @unlink($path);
                throw $error;
            }
        }

        throw new RuntimeException('Could not create a unique camera snapshot file.');
    }

    public function capture(string $cameraId): string
    {
        $camera = $this->config->camera($cameraId);
        $host = $camera['host'];
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new RuntimeException('Snapshot requires an IPv4 camera host.');
        }
        $port = $camera['access']['ports']['http'] ?? 80;
        $authority = $host . ($port === 80 ? '' : ':' . $port);
        $url = 'http://' . $authority . '/webcapture.jpg?command=snap&channel=0';
        $response = ($this->request)($url);
        $jpeg = is_array($response) ? ($response['body'] ?? null) : null;
        $status = is_array($response) ? ($response['status'] ?? null) : null;
        $imageInfo = is_string($jpeg) ? @getimagesizefromstring($jpeg) : false;
        if ($status !== 200 || !is_string($jpeg) || strlen($jpeg) > self::MAX_BYTES
            || ($imageInfo[2] ?? null) !== IMAGETYPE_JPEG) {
            throw new RuntimeException('Camera did not return a JPEG snapshot.');
        }

        return $jpeg;
    }
}
