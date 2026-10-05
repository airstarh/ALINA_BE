<?php

declare(strict_types=1);

namespace Cam;

use JsonException;

final class DvripPacketCodec
{
    public const HEADER_LENGTH = 20;

    public function __construct(private int $maxPayloadBytes = 2_097_152)
    {
    }

    public function encode(int $session, int $sequence, int $messageId, array $payload): string
    {
        try {
            $json = json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException $exception) {
            throw new DvripException('protocol_json', 'Could not encode DVRIP JSON.', $exception);
        }

        $body = $json . "\n\0";
        if (strlen($body) > $this->maxPayloadBytes) {
            throw new DvripException('protocol_oversize', 'DVRIP payload is too large.');
        }

        return pack(
            'CCx2VVx2vV',
            0xff,
            0,
            $session,
            $sequence,
            $messageId,
            strlen($body)
        ) . $body;
    }

    public function decodeHeader(string $header): array
    {
        if (strlen($header) !== self::HEADER_LENGTH) {
            throw new DvripException('protocol_header', 'DVRIP header has an invalid length.');
        }

        $values = unpack(
            'Cmarker/Cversion/x2/Vsession/Vsequence/x2/vmessage/Vlength',
            $header
        );
        if (!is_array($values)
            || $values['marker'] !== 0xff
            || !in_array($values['version'], [0, 1], true)
        ) {
            throw new DvripException('protocol_header', 'DVRIP header is invalid.');
        }
        if ($values['length'] > $this->maxPayloadBytes) {
            throw new DvripException('protocol_oversize', 'DVRIP payload is too large.');
        }

        return $values;
    }

    public function decodePayload(string $payload): array
    {
        if (!str_ends_with($payload, "\n\0")) {
            throw new DvripException('protocol_payload', 'DVRIP payload terminator is missing.');
        }

        try {
            $decoded = json_decode(substr($payload, 0, -2), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new DvripException('protocol_json', 'DVRIP response is not valid JSON.', $exception);
        }

        if (!is_array($decoded)) {
            throw new DvripException('protocol_json', 'DVRIP response must be a JSON object.');
        }

        return $decoded;
    }
}
