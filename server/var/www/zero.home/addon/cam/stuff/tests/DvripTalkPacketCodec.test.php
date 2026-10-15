<?php

declare(strict_types=1);

use Cam\DvripException;
use Cam\DvripPacketCodec;
use Cam\DvripTalkPacketCodec;

require_once dirname(__DIR__) . '/php/DvripException.php';
require_once dirname(__DIR__) . '/php/DvripPacketCodec.php';
require_once dirname(__DIR__) . '/php/DvripTalkPacketCodec.php';

function talkHeader(string $packet): array
{
    $values = unpack(
        'Cmarker/Cversion/x2/Vsession/Vsequence/x2/vmessage/Vlength',
        substr($packet, 0, DvripPacketCodec::HEADER_LENGTH)
    );
    if (!is_array($values)) {
        throw new RuntimeException('could not decode talk packet header');
    }

    return $values;
}

function talkResponsePacket(
    int $session,
    int $sequence,
    int $message,
    int $ret = 100,
    string $name = 'OPTalk',
): array {
    $payload = json_encode([
        'Name' => $name,
        'Ret' => $ret,
        'SessionID' => sprintf('0x%08x', $session),
    ], JSON_THROW_ON_ERROR) . "\n\0";
    $header = pack('CCx2VVx2vV', 0xff, 1, $session, $sequence, $message, strlen($payload));

    return [$header, $payload];
}

test('talk claim matches the captured Balcon OPTalk format', function (): void {
    $codec = new DvripTalkPacketCodec();
    $packet = $codec->claim(0xc4, 7);
    $header = talkHeader($packet);
    $payload = (new DvripPacketCodec())->decodePayload(substr($packet, DvripPacketCodec::HEADER_LENGTH));

    assertSameValue(0xff, $header['marker']);
    assertSameValue(0xc4, $header['session']);
    assertSameValue(7, $header['sequence']);
    assertSameValue(0x059a, $header['message']);
    assertSameValue('OPTalk', $payload['Name']);
    assertSameValue('Claim', $payload['OPTalk']['Action']);
    assertSameValue(128, $payload['OPTalk']['AudioFormat']['BitRate']);
    assertSameValue('G711_ALAW', $payload['OPTalk']['AudioFormat']['EncodeType']);
    assertSameValue(8, $payload['OPTalk']['AudioFormat']['SampleBit']);
    assertSameValue(8000, $payload['OPTalk']['AudioFormat']['SampleRate']);
    assertSameValue(0, $payload['OPTalk']['Channel']);
});

test('talk control matches the captured Balcon Start and Stop format', function (): void {
    $codec = new DvripTalkPacketCodec();

    foreach (['Start', 'Stop'] as $action) {
        $packet = $codec->control(0xc4, 0, $action);
        $header = talkHeader($packet);
        $payload = (new DvripPacketCodec())->decodePayload(
            substr($packet, DvripPacketCodec::HEADER_LENGTH)
        );

        assertSameValue(0xc4, $header['session']);
        assertSameValue(0, $header['sequence']);
        assertSameValue(0x0596, $header['message']);
        assertSameValue('OPTalk', $payload['Name']);
        assertSameValue($action, $payload['OPTalk']['Action']);
        assertSameValue('G711_ALAW', $payload['OPTalk']['AudioFormat']['EncodeType']);
        assertSameValue(8000, $payload['OPTalk']['AudioFormat']['SampleRate']);
    }
});

test('talk audio frame uses the captured media prefix and 320 byte payload', function (): void {
    $codec = new DvripTalkPacketCodec();
    $alaw = str_repeat("\x55", 320);
    $packet = $codec->audio(0xc4, 9, $alaw);
    $header = talkHeader($packet);
    $payload = substr($packet, DvripPacketCodec::HEADER_LENGTH);

    assertSameValue(0x0598, $header['message']);
    assertSameValue(328, $header['length']);
    assertSameValue("\x00\x00\x01\xfa\x0e\x02\x40\x01", substr($payload, 0, 8));
    assertSameValue($alaw, substr($payload, 8));
});

test('talk audio frame rejects anything other than 320 A-law bytes', function (): void {
    $codec = new DvripTalkPacketCodec();
    foreach ([319, 321] as $length) {
        $error = assertThrows(
            fn (): string => $codec->audio(0xc4, 0, str_repeat("\x55", $length)),
            InvalidArgumentException::class
        );
        assertSameValue('Talk audio frames must contain exactly 320 G.711 A-law bytes.', $error->getMessage());
    }
});

test('talk response accepts the Balcon reply sequence one after the claim request', function (): void {
    $codec = new DvripTalkPacketCodec();
    [$header, $payload] = talkResponsePacket(0xc4, 8, 0x059b);

    $response = $codec->response($header, $payload, 0xc4, 7);

    assertSameValue(100, $response['Ret']);
    assertSameValue('OPTalk', $response['Name']);
});

test('talk response rejects mismatched identity and camera rejection', function (): void {
    $codec = new DvripTalkPacketCodec();
    foreach ([
        talkResponsePacket(0xc5, 7, 0x059b),
        talkResponsePacket(0xc4, 9, 0x059b),
        talkResponsePacket(0xc4, 7, 0x059c),
        talkResponsePacket(0xc4, 7, 0x059b, 101),
        talkResponsePacket(0xc4, 7, 0x059b, 100, 'Other'),
    ] as [$header, $payload]) {
        assertThrows(
            fn (): array => $codec->response($header, $payload, 0xc4, 7),
            DvripException::class
        );
    }
});

test('talk control response accepts captured Start and Stop sequence variants', function (): void {
    $codec = new DvripTalkPacketCodec();

    foreach ([0, 1] as $sequence) {
        [$header, $payload] = talkResponsePacket(0xc4, $sequence, 0x0597);
        $response = $codec->controlResponse($header, $payload, 0xc4, 0);
        assertSameValue(100, $response['Ret']);
    }
});

test('talk response rejects an oversized declared payload before decoding it', function (): void {
    $codec = new DvripTalkPacketCodec(64);
    [, $payload] = talkResponsePacket(0xc4, 7, 0x059b);
    $header = pack('CCx2VVx2vV', 0xff, 1, 0xc4, 7, 0x059b, 65);

    assertThrows(
        fn (): array => $codec->response($header, $payload, 0xc4, 7),
        DvripException::class
    );
});
