<?php

declare(strict_types=1);

namespace Cam;

use InvalidArgumentException;

final class DvripTalkPacketCodec
{
    public const CLAIM_MESSAGE = 0x059a;
    public const CLAIM_RESPONSE_MESSAGE = 0x059b;
    public const CONTROL_MESSAGE = 0x0596;
    public const CONTROL_RESPONSE_MESSAGE = 0x0597;
    public const AUDIO_MESSAGE = 0x0598;
    public const AUDIO_BYTES = 320;
    public const MEDIA_PREFIX = "\x00\x00\x01\xfa\x0e\x02\x40\x01";

    private DvripPacketCodec $jsonCodec;

    public function __construct(private int $maxPayloadBytes = 2_097_152)
    {
        $this->jsonCodec = new DvripPacketCodec($maxPayloadBytes);
    }

    public function claim(int $session, int $sequence): string
    {
        return $this->jsonCodec->encode($session, $sequence, self::CLAIM_MESSAGE, $this->payload(
            $session,
            'Claim'
        ));
    }

    public function control(int $session, int $sequence, string $action): string
    {
        if (!in_array($action, ['Start', 'Stop'], true)) {
            throw new InvalidArgumentException('Talk control action must be Start or Stop.');
        }

        return $this->jsonCodec->encode(
            $session,
            $sequence,
            self::CONTROL_MESSAGE,
            $this->payload($session, $action)
        );
    }

    private function payload(int $session, string $action): array
    {
        return [
            'Name' => 'OPTalk',
            'OPTalk' => [
                'Action' => $action,
                'AudioFormat' => [
                    'BitRate' => 128,
                    'EncodeType' => 'G711_ALAW',
                    'SampleBit' => 8,
                    'SampleRate' => 8000,
                ],
                'Channel' => 0,
            ],
            'SessionID' => sprintf('0x%08x', $session),
        ];
    }

    public function audio(int $session, int $sequence, string $alaw): string
    {
        if (strlen($alaw) !== self::AUDIO_BYTES) {
            throw new InvalidArgumentException(
                'Talk audio frames must contain exactly 320 G.711 A-law bytes.'
            );
        }

        $payload = self::MEDIA_PREFIX . $alaw;

        return pack(
            'CCx2VVx2vV',
            0xff,
            0,
            $session,
            $sequence,
            self::AUDIO_MESSAGE,
            strlen($payload)
        ) . $payload;
    }

    public function response(
        string $header,
        string $payload,
        int $expectedSession,
        int $expectedSequence,
    ): array {
        $decodedHeader = $this->jsonCodec->decodeHeader($header);
        if ($decodedHeader['session'] !== $expectedSession
            || $decodedHeader['sequence'] !== $expectedSequence + 1
            || $decodedHeader['message'] !== self::CLAIM_RESPONSE_MESSAGE
            || $decodedHeader['length'] !== strlen($payload)
        ) {
            throw new DvripException('protocol_identity', 'DVRIP talk response identity does not match.');
        }

        $response = $this->jsonCodec->decodePayload($payload);
        if (($response['Name'] ?? null) !== 'OPTalk') {
            throw new DvripException('protocol_identity', 'DVRIP talk response name does not match.');
        }
        if (($response['Ret'] ?? null) !== 100) {
            throw new DvripException('talk_rejected', 'Camera rejected the talk channel.');
        }

        return $response;
    }

    public function controlResponse(
        string $header,
        string $payload,
        int $expectedSession,
        int $expectedSequence,
    ): array {
        $decodedHeader = $this->jsonCodec->decodeHeader($header);
        if ($decodedHeader['session'] !== $expectedSession
            || !in_array($decodedHeader['sequence'], [$expectedSequence, $expectedSequence + 1], true)
            || $decodedHeader['message'] !== self::CONTROL_RESPONSE_MESSAGE
            || $decodedHeader['length'] !== strlen($payload)
        ) {
            throw new DvripException('protocol_identity', 'DVRIP talk control response identity does not match.');
        }

        $response = $this->jsonCodec->decodePayload($payload);
        if (($response['Name'] ?? null) !== 'OPTalk') {
            throw new DvripException('protocol_identity', 'DVRIP talk control response name does not match.');
        }
        if (($response['Ret'] ?? null) !== 100) {
            throw new DvripException('talk_rejected', 'Camera rejected the talk control action.');
        }

        return $response;
    }
}
