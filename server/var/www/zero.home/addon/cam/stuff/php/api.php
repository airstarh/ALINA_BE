<?php

declare(strict_types=1);

namespace Cam;

use Closure;
use InvalidArgumentException;
use Throwable;

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/DvripException.php';
require_once __DIR__ . '/DvripTransport.php';
require_once __DIR__ . '/StreamDvripTransport.php';
require_once __DIR__ . '/DvripPacketCodec.php';
require_once __DIR__ . '/DvripReader.php';
require_once __DIR__ . '/DvripClient.php';
require_once __DIR__ . '/ExtendedEditorDefinitions.php';
require_once __DIR__ . '/NetworkServiceEditor.php';
require_once __DIR__ . '/CameraService.php';

final class Api
{
    private const HEADERS = [
        'Content-Type' => 'application/json; charset=utf-8',
        'Cache-Control' => 'no-store, max-age=0',
    ];

    public function __construct(
        private Config $config,
        private Closure $serviceFactory,
    ) {
    }

    public function handle(array $query, ?array $body = null, string $method = 'GET'): array
    {
        try {
            $action = $query['action'] ?? null;
            if (!is_string($action) || !in_array($action, [
                'cameras', 'status', 'sections', 'config', 'smtp', 'smtp-save', 'smtp-test', 'ftp-test',
                'editors', 'editor', 'editor-save', 'wifi-aps', 'reboot',
            ], true)) {
                return $this->error(400, 'invalid_action', 'Unknown API action.');
            }

            if ($action === 'cameras') {
                return $this->success([
                    'cameras' => $this->config->publicCameras(),
                    'default' => $this->config->defaultCameraId(),
                ]);
            }

            $cameraId = $query['camera'] ?? null;
            if (!is_string($cameraId) || $cameraId === '') {
                return $this->error(400, 'camera_required', 'Camera is required.');
            }

            try {
                $camera = $this->config->camera($cameraId);
            } catch (InvalidArgumentException) {
                return $this->error(404, 'camera_not_found', 'Camera is not configured.');
            }

            if ($action === 'sections') {
                return $this->success(CameraService::sectionList());
            }

            if (in_array($action, ['smtp-save', 'smtp-test', 'ftp-test', 'editor-save', 'reboot'], true)
                && strtoupper($method) !== 'POST'
            ) {
                return $this->error(405, 'method_not_allowed', 'Configuration changes require POST.');
            }

            $service = ($this->serviceFactory)($cameraId, $camera);
            if (!$service instanceof CameraService) {
                throw new DvripException('service_error', 'Camera service factory returned an invalid service.');
            }

            try {
                $data = match ($action) {
                    'status' => $service->status(),
                    'sections' => $service->sections(),
                    'config' => $this->handleConfig($service, $query),
                    'smtp' => $service->smtp(),
                    'smtp-save' => $service->updateSmtp($body ?? []),
                    'smtp-test' => $service->testSmtp(),
                    'ftp-test' => $service->testFtp(),
                    'editors' => $service->availableEditors(),
                    'editor' => $service->editor($this->editorId($query)),
                    'editor-save' => $service->updateEditor($this->editorId($query), $body ?? []),
                    'wifi-aps' => $service->wifiAccessPoints(),
                    'reboot' => $service->reboot(),
                };
                return $this->success($data);
            } finally {
                $service->close();
            }
        } catch (InvalidArgumentException $exception) {
            return $this->error(400, 'invalid_request', $exception->getMessage());
        } catch (DvripException $exception) {
            return $this->error(
                $this->statusForDvripError($exception->publicCode()),
                $exception->publicCode(),
                $this->publicMessage($exception->publicCode())
            );
        } catch (Throwable) {
            return $this->error(500, 'internal_error', 'Camera request failed.');
        }
    }

    private function handleConfig(CameraService $service, array $query): mixed
    {
        $section = $query['section'] ?? null;
        if (!is_string($section) || $section === '') {
            throw new InvalidArgumentException('Configuration section is required.');
        }
        return $section === 'all' ? $service->all() : $service->config($section);
    }

    private function editorId(array $query): string
    {
        $editor = $query['editor'] ?? null;
        if (!is_string($editor) || $editor === '') {
            throw new InvalidArgumentException('Editor is required.');
        }
        return $editor;
    }

    private function success(mixed $data): array
    {
        return [
            'status' => 200,
            'headers' => self::HEADERS,
            'body' => ['ok' => true, 'data' => $data, 'error' => null],
        ];
    }

    private function error(int $status, string $code, string $message): array
    {
        return [
            'status' => $status,
            'headers' => self::HEADERS,
            'body' => [
                'ok' => false,
                'data' => null,
                'error' => ['code' => $code, 'message' => $message],
            ],
        ];
    }

    private function statusForDvripError(string $code): int
    {
        return match ($code) {
            'transport_timeout' => 504,
            'login_rejected' => 502,
            'device_unsupported', 'device_config_missing' => 422,
            default => 502,
        };
    }

    private function publicMessage(string $code): string
    {
        return match ($code) {
            'transport_timeout' => 'Camera connection timed out.',
            'login_rejected' => 'Camera login was rejected.',
            'device_unsupported' => 'Camera does not support this operation.',
            'device_config_missing' => 'Camera configuration section does not exist.',
            'ftp_test_failed' => 'Camera reported the FTP test failed.',
            default => 'Camera communication failed.',
        };
    }
}

function resolveProductionConfigPath(?string $configPath = null): string
{
    if ($configPath !== null) {
        return $configPath;
    }

    return dirname(__DIR__, 2) . '/config.php';
}

function createProductionApi(?string $configPath = null): Api
{
    $configPath = resolveProductionConfigPath($configPath);
    $config = Config::load($configPath);

    return new Api(
        $config,
        static function (string $id, array $camera): CameraService {
            $dvrip = $camera['dvrip'];
            $transport = new StreamDvripTransport(
                $camera['host'],
                $dvrip['port'],
                (float) ($dvrip['timeout'] ?? 5.0)
            );
            $client = new DvripClient($transport);
            $client->login($dvrip['username'], $dvrip['password']);
            return new CameraService($client, $id, $camera['name']);
        }
    );
}

function withCameraRequestLock(
    string $cameraId,
    callable $callback,
    ?string $lockRoot = null,
): mixed {
    $lockRoot ??= '/var/tmp/php-upload';
    $lockPath = rtrim($lockRoot, DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . 'alina-cam-'
        . hash('sha256', $cameraId)
        . '.lock';
    $deadline = hrtime(true) + 30_000_000_000;

    while (!@mkdir($lockPath, 0700)) {
        if (hrtime(true) >= $deadline) {
            throw new DvripException('transport_timeout', 'Timed out waiting for camera request lock.');
        }
        usleep(25_000);
    }

    try {
        return $callback($lockPath);
    } finally {
        @rmdir($lockPath);
    }
}

function runProductionApi(
    array $query,
    ?string $configPath = null,
    ?array $body = null,
    string $method = 'GET',
): array
{
    try {
        $run = static fn (): array => createProductionApi($configPath)->handle($query, $body, $method);
        $action = $query['action'] ?? null;
        $cameraId = $query['camera'] ?? null;
        if (in_array($action, [
            'status', 'config', 'smtp', 'smtp-save', 'smtp-test', 'editors', 'editor', 'editor-save', 'reboot',
        ], true)
            && is_string($cameraId)
            && $cameraId !== ''
        ) {
            return withCameraRequestLock(
                $cameraId,
                static fn (): array => $run()
            );
        }
        return $run();
    } catch (Throwable) {
        return [
            'status' => 500,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Cache-Control' => 'no-store, max-age=0',
            ],
            'body' => [
                'ok' => false,
                'data' => null,
                'error' => [
                    'code' => 'config_error',
                    'message' => 'Camera configuration is unavailable.',
                ],
            ],
        ];
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $body = null;
    if ($method === 'POST') {
        try {
            $decoded = json_decode(file_get_contents('php://input'), true, flags: JSON_THROW_ON_ERROR);
            $body = is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            $body = null;
        }
    }
    $response = runProductionApi($_GET, null, $body, $method);
    http_response_code($response['status']);
    foreach ($response['headers'] as $name => $value) {
        header("{$name}: {$value}");
    }
    echo json_encode(
        $response['body'],
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}
