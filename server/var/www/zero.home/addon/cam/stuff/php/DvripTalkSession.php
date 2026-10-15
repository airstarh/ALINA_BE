<?php

declare(strict_types=1);

namespace Cam;

use RuntimeException;
use Throwable;

final class DvripTalkSession
{
    private DvripPacketCodec $jsonCodec;
    private DvripTalkPacketCodec $talkCodec;
    private int $session = 0;
    private bool $controlOpen = false;
    private bool $mediaOpen = false;
    private bool $controlStarted = false;
    private bool $active = false;
    private string $incoming = '';

    public function __construct(
        private DvripDuplexTransport $controlTransport,
        private DvripDuplexTransport $mediaTransport,
        ?DvripPacketCodec $jsonCodec = null,
        ?DvripTalkPacketCodec $talkCodec = null,
        private int $maximumBufferedBytes = 2_097_152,
    ) {
        $this->jsonCodec = $jsonCodec ?? new DvripPacketCodec();
        $this->talkCodec = $talkCodec ?? new DvripTalkPacketCodec();
    }

    public function open(string $username, string $password): void
    {
        if ($this->active || $this->controlOpen || $this->mediaOpen) {
            throw new RuntimeException('Talk session is already open.');
        }

        try {
            $this->controlTransport->connect();
            $this->controlOpen = true;
            $loginSequence = 0;
            $this->controlTransport->writeAll($this->jsonCodec->encode(0, $loginSequence, 1000, [
                'EncryptType' => 'MD5',
                'LoginType' => 'DVRIP-Web',
                'PassWord' => DvripClient::passwordHash($password),
                'UserName' => $username,
            ]));
            $login = $this->readJsonResponse($this->controlTransport, 1001, $loginSequence, false);
            if (!is_int($login['Ret'] ?? null) || !in_array($login['Ret'], [100, 150, 515], true)) {
                throw new DvripException('login_rejected', 'Camera rejected DVRIP login.');
            }
            $sessionId = $login['SessionID'] ?? null;
            if (!is_string($sessionId) || preg_match('/^0x[0-9a-fA-F]{1,8}$/D', $sessionId) !== 1) {
                throw new DvripException('protocol_session', 'DVRIP login session is invalid.');
            }
            $this->session = (int) hexdec(substr($sessionId, 2));

            $this->mediaTransport->connect();
            $this->mediaOpen = true;
            $claimSequence = 0;
            $this->mediaTransport->writeAll($this->talkCodec->claim($this->session, $claimSequence));
            $header = $this->mediaTransport->readExact(DvripPacketCodec::HEADER_LENGTH);
            $decoded = $this->jsonCodec->decodeHeader($header);
            $payload = $this->mediaTransport->readExact($decoded['length']);
            $this->talkCodec->response(
                $header,
                $payload,
                $this->session,
                $claimSequence
            );

            $controlSequence = 0;
            $this->controlTransport->writeAll(
                $this->talkCodec->control($this->session, $controlSequence, 'Start')
            );
            $controlHeader = $this->controlTransport->readExact(DvripPacketCodec::HEADER_LENGTH);
            $controlDecoded = $this->jsonCodec->decodeHeader($controlHeader);
            $controlPayload = $this->controlTransport->readExact($controlDecoded['length']);
            $this->talkCodec->controlResponse(
                $controlHeader,
                $controlPayload,
                $this->session,
                $controlSequence
            );
            $this->controlStarted = true;

            $this->mediaTransport->setBlocking(false);
            $this->active = true;
        } catch (Throwable $exception) {
            $this->close();
            throw $exception;
        }
    }

    public function send(string $alaw320): void
    {
        $this->ensureActive();
        $this->mediaTransport->writeAll(
            $this->talkCodec->audio($this->session, 0, $alaw320)
        );
    }

    public function drain(): int
    {
        $this->ensureActive();
        $this->incoming .= $this->mediaTransport->readAvailable();
        if (strlen($this->incoming) > $this->maximumBufferedBytes) {
            throw new DvripException('protocol_oversize', 'Camera talk input buffer is too large.');
        }

        $packets = 0;
        while (strlen($this->incoming) >= DvripPacketCodec::HEADER_LENGTH) {
            $headerBytes = substr($this->incoming, 0, DvripPacketCodec::HEADER_LENGTH);
            $header = $this->jsonCodec->decodeHeader($headerBytes);
            $packetLength = DvripPacketCodec::HEADER_LENGTH + $header['length'];
            if ($packetLength > $this->maximumBufferedBytes) {
                throw new DvripException('protocol_oversize', 'Camera talk packet is too large.');
            }
            if (strlen($this->incoming) < $packetLength) {
                break;
            }
            if ($header['session'] !== $this->session || $header['message'] !== 0x0599) {
                throw new DvripException('protocol_identity', 'Camera talk media packet does not match.');
            }
            $this->incoming = substr($this->incoming, $packetLength);
            $packets++;
        }

        return $packets;
    }

    public function stream()
    {
        $this->ensureActive();
        return $this->mediaTransport->stream();
    }

    public function close(): void
    {
        if ($this->controlStarted && $this->controlOpen) {
            try {
                $this->controlTransport->writeAll(
                    $this->talkCodec->control($this->session, 0, 'Stop')
                );
            } catch (Throwable) {
                // Closing both sockets is the fallback when the camera cannot acknowledge Stop.
            }
        }

        $this->active = false;
        $this->controlStarted = false;
        $this->incoming = '';
        if ($this->mediaOpen) {
            $this->mediaOpen = false;
            $this->mediaTransport->close();
        }
        if ($this->controlOpen) {
            $this->controlOpen = false;
            $this->controlTransport->close();
        }
    }

    private function readJsonResponse(
        DvripDuplexTransport $transport,
        int $expectedMessage,
        int $expectedSequence,
        bool $matchSession,
    ): array
    {
        $header = $this->jsonCodec->decodeHeader(
            $transport->readExact(DvripPacketCodec::HEADER_LENGTH)
        );
        if ($header['message'] !== $expectedMessage
            || $header['sequence'] !== $expectedSequence
            || ($matchSession && $header['session'] !== $this->session)
        ) {
            throw new DvripException('protocol_identity', 'DVRIP response identity does not match.');
        }

        return $this->jsonCodec->decodePayload(
            $transport->readExact($header['length'])
        );
    }

    private function ensureActive(): void
    {
        if (!$this->active) {
            throw new DvripException('talk_closed', 'Talk session is not active.');
        }
    }
}
