<?php

declare(strict_types=1);

namespace Cam;

use Throwable;

final class DvripClient implements DvripReader
{
    private const SUCCESS_CODES = [100, 150, 515];

    private DvripPacketCodec $codec;
    private int $session = 0;
    private int $sequence = 0;
    private bool $loggedIn = false;
    private ?string $username = null;
    private ?string $password = null;

    public function __construct(
        private DvripTransport $transport,
        int $maxPayloadBytes = 2_097_152,
        private int $rebootHoldMicroseconds = 12_000_000,
    ) {
        $this->codec = new DvripPacketCodec($maxPayloadBytes);
    }

    public function login(string $username, string $password): array
    {
        $this->username = $username;
        $this->password = $password;
        try {
            $this->transport->connect();
            $response = $this->request(1000, [
                'EncryptType' => 'MD5',
                'LoginType' => 'DVRIP-Web',
                'PassWord' => self::sofiaHash($password),
                'UserName' => $username,
            ]);
            $this->assertSuccess($response, true);

            $sessionId = $response['SessionID'] ?? null;
            if (!is_string($sessionId) || preg_match('/^0x[0-9a-fA-F]{1,8}$/D', $sessionId) !== 1) {
                throw new DvripException('protocol_session', 'DVRIP login session is invalid.');
            }

            $this->session = (int) hexdec(substr($sessionId, 2));
            $this->loggedIn = true;
            return $response;
        } catch (Throwable $exception) {
            $this->close();
            throw $exception;
        }
    }

    public function getConfig(string $command): mixed
    {
        return $this->getNamed(1042, $command);
    }

    public function setConfig(string $command, mixed $value): bool
    {
        $this->ensureLoggedIn();

        try {
            $response = $this->request(1040, [
                'Name' => $command,
                'SessionID' => sprintf('0x%08X', $this->session),
                $command => $value,
            ]);
            $this->assertSuccess($response);
            if (isset($response['Name']) && $response['Name'] !== $command) {
                throw new DvripException('protocol_identity', 'DVRIP response name does not match.');
            }
            return true;
        } catch (Throwable $exception) {
            $this->close();
            throw $exception;
        }
    }

    public function getSystemInfo(): array
    {
        $value = $this->getNamed(1020, 'SystemInfo');
        if (!is_array($value)) {
            $this->close();
            throw new DvripException('protocol_command', 'SystemInfo response has an invalid shape.');
        }
        return $value;
    }

    public function getInfo(string $command): mixed
    {
        return $this->getNamed(1020, $command);
    }

    public function getSystemFunction(): array
    {
        $value = $this->getNamed(1360, 'SystemFunction');
        if (!is_array($value)) {
            $this->close();
            throw new DvripException('protocol_command', 'SystemFunction response has an invalid shape.');
        }
        return $value;
    }

    public function getEncodeCapability(): mixed
    {
        return $this->getNamed(1360, 'EncodeCapability');
    }

    public function testMail(array $settings): bool
    {
        $this->ensureLoggedIn();

        try {
            $response = $this->request(1636, [
                'Name' => 'OPMailTest',
                'SessionID' => sprintf('0x%08X', $this->session),
                'OPMailTest' => $settings,
            ]);
            $this->assertSuccess($response);
            if (isset($response['Name']) && $response['Name'] !== 'OPMailTest') {
                throw new DvripException('protocol_identity', 'DVRIP email-test response does not match.');
            }
            return true;
        } catch (Throwable $exception) {
            $this->close();
            throw $exception;
        }
    }

    public function reboot(): bool
    {
        $this->ensureLoggedIn();

        try {
            $response = $this->request(1450, [
                'Name' => 'OPMachine',
                'SessionID' => sprintf('0x%08X', $this->session),
                'OPMachine' => ['Action' => 'Reboot'],
            ]);
            $this->assertSuccess($response);
            if (isset($response['Name']) && $response['Name'] !== 'OPMachine') {
                throw new DvripException('protocol_identity', 'DVRIP reboot response does not match.');
            }
            if ($this->rebootHoldMicroseconds > 0) {
                usleep($this->rebootHoldMicroseconds);
            }
            return true;
        } catch (Throwable $exception) {
            $this->close();
            throw $exception;
        }
    }

    public function close(): void
    {
        $this->loggedIn = false;
        $this->transport->close();
    }

    private function getNamed(int $messageId, string $command): mixed
    {
        $this->ensureLoggedIn();

        try {
            $response = $this->request($messageId, [
                'Name' => $command,
                'SessionID' => sprintf('0x%08X', $this->session),
            ]);
            $this->assertSuccess($response);
            if (isset($response['Name']) && $response['Name'] !== $command) {
                throw new DvripException('protocol_identity', 'DVRIP response name does not match.');
            }
            if (isset($response['SessionID'])) {
                $responseSession = $response['SessionID'];
                if (!is_string($responseSession)
                    || preg_match('/^0x[0-9a-fA-F]{1,8}$/D', $responseSession) !== 1
                    || (int) hexdec(substr($responseSession, 2)) !== $this->session
                ) {
                    throw new DvripException('protocol_identity', 'DVRIP response session does not match.');
                }
            }
            if (!array_key_exists($command, $response)) {
                throw new DvripException('protocol_command', 'DVRIP response does not match the request.');
            }
            return $response[$command];
        } catch (Throwable $exception) {
            if (!$exception instanceof DvripException
                || !in_array($exception->publicCode(), [
                    'device_unsupported', 'device_config_missing', 'device_error',
                ], true)
            ) {
                $this->close();
            }
            throw $exception;
        }
    }

    private function request(int $messageId, array $payload): array
    {
        try {
            $requestSequence = $this->sequence;
            $packet = $this->codec->encode(
                $this->session,
                $requestSequence,
                $messageId,
                $payload
            );
            $this->sequence++;
            $this->transport->writeAll($packet);

            $header = $this->codec->decodeHeader(
                $this->transport->readExact(DvripPacketCodec::HEADER_LENGTH)
            );
            if ($header['sequence'] !== $requestSequence
                || $header['message'] !== $messageId + 1
                || ($messageId !== 1000 && $header['session'] !== $this->session)
            ) {
                throw new DvripException('protocol_identity', 'DVRIP response identity does not match.');
            }
            return $this->codec->decodePayload(
                $this->transport->readExact($header['length'])
            );
        } catch (Throwable $exception) {
            $this->close();
            throw $exception;
        }
    }

    private function ensureLoggedIn(): void
    {
        if ($this->loggedIn) {
            return;
        }
        if ($this->username === null || $this->password === null) {
            throw new DvripException('not_logged_in', 'DVRIP login is required.');
        }
        $this->login($this->username, $this->password);
    }

    private function assertSuccess(array $response, bool $login = false): void
    {
        $returnCode = $response['Ret'] ?? null;
        if (!is_int($returnCode)) {
            throw new DvripException('protocol_return', 'DVRIP response has no return code.');
        }
        if (in_array($returnCode, self::SUCCESS_CODES, true)) {
            return;
        }

        $publicCode = match ($returnCode) {
            106, 203, 204, 205, 206, 207 => 'login_rejected',
            605 => 'device_unsupported',
            607 => 'device_config_missing',
            default => $login ? 'login_rejected' : 'device_error',
        };
        throw new DvripException($publicCode, "Camera returned DVRIP code {$returnCode}.");
    }

    private static function sofiaHash(string $password): string
    {
        $digest = md5($password, true);
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $hash = '';
        for ($index = 0; $index < 16; $index += 2) {
            $hash .= $alphabet[(ord($digest[$index]) + ord($digest[$index + 1])) % 62];
        }
        return $hash;
    }
}
