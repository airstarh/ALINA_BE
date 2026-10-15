<?php

declare(strict_types=1);

use Cam\DvripException;
use Cam\DvripPacketCodec;
use Cam\StreamDvripTransport;

final class FragmentedFixtureStream
{
    public mixed $context;
    private static array $fixtures = [];
    private string $data = '';
    private int $position = 0;
    private int $chunkSize = 1;
    private int $delayMicroseconds = 0;

    public static function enqueue(string $data, int $chunkSize, int $delayMicroseconds = 0): void
    {
        self::$fixtures[] = [$data, $chunkSize, $delayMicroseconds];
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        [$this->data, $this->chunkSize, $this->delayMicroseconds] = array_shift(self::$fixtures);
        return true;
    }

    public function stream_read(int $count): string
    {
        if ($this->delayMicroseconds > 0) {
            usleep($this->delayMicroseconds);
        }
        $chunk = substr($this->data, $this->position, min($count, $this->chunkSize));
        $this->position += strlen($chunk);
        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->data);
    }

    public function stream_stat(): array
    {
        return [];
    }

    public function stream_set_option(int $option, int $arg1, mixed $arg2): bool
    {
        return true;
    }
}

function loadDvripFramingClasses(): void
{
    $directory = dirname(__DIR__) . '/php';
    foreach (['DvripException.php', 'DvripTransport.php', 'DvripDuplexTransport.php', 'StreamDvripTransport.php', 'DvripPacketCodec.php'] as $file) {
        $path = $directory . '/' . $file;
        if (!is_file($path)) {
            throw new RuntimeException("DVRIP framing class {$file} does not exist");
        }
        require_once $path;
    }
}

function socketTransportPair(): array
{
    $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($pair === false) {
        throw new RuntimeException('could not create socket pair');
    }

    $transport = new StreamDvripTransport(
        'fixture.invalid',
        34567,
        0.2,
        static fn () => $pair[0]
    );
    $transport->connect();

    return [$transport, $pair[1]];
}

function fragmentedTransport(string $data, int $chunkSize, float $timeout = 0.2, int $delayMicroseconds = 0): StreamDvripTransport
{
    if (!in_array('camfixture', stream_get_wrappers(), true)) {
        stream_wrapper_register('camfixture', FragmentedFixtureStream::class);
    }
    FragmentedFixtureStream::enqueue($data, $chunkSize, $delayMicroseconds);
    $transport = new StreamDvripTransport(
        'fixture.invalid',
        34567,
        $timeout,
        static fn () => fopen('camfixture://data', 'r')
    );
    $transport->connect();
    return $transport;
}

test('dvrip_encodes_exact_little_endian_header_and_payload', function (): void {
    loadDvripFramingClasses();
    $codec = new DvripPacketCodec();
    $json = '{"Name":"SystemInfo","SessionID":"0x01020304"}' . "\n\0";
    $expectedHeader = hex2bin('ff00000004030201070000000000fc03')
        . pack('V', strlen($json));

    $packet = $codec->encode(
        0x01020304,
        7,
        1020,
        ['Name' => 'SystemInfo', 'SessionID' => '0x01020304']
    );

    assertSameValue($expectedHeader . $json, $packet);
});

test('stream_transport_reads_fragmented_payload_completely', function (): void {
    loadDvripFramingClasses();
    $transport = fragmentedTransport('abcdefghij', 3);

    try {
        assertSameValue('abcdefghij', $transport->readExact(10));
    } finally {
        $transport->close();
    }
});

test('stream_transport_rejects_eof_during_header', function (): void {
    loadDvripFramingClasses();
    $transport = fragmentedTransport('short', 2);

    try {
        $exception = assertThrows(
            static fn () => $transport->readExact(20),
            DvripException::class
        );
        assertSameValue('transport_eof', $exception->publicCode());
    } finally {
        $transport->close();
    }
});

test('stream_transport_rejects_eof_during_body', function (): void {
    loadDvripFramingClasses();
    $transport = fragmentedTransport('1234567', 3);

    try {
        $exception = assertThrows(
            static fn () => $transport->readExact(12),
            DvripException::class
        );
        assertSameValue('transport_eof', $exception->publicCode());
    } finally {
        $transport->close();
    }
});

test('dvrip_rejects_invalid_header_marker', function (): void {
    loadDvripFramingClasses();
    $codec = new DvripPacketCodec();
    $header = hex2bin('0000000000000000000000000000fc0302000000');
    $exception = assertThrows(
        static fn () => $codec->decodeHeader($header),
        DvripException::class
    );
    assertSameValue('protocol_header', $exception->publicCode());
});

test('dvrip_accepts_camera_response_header_version_one', function (): void {
    loadDvripFramingClasses();
    $codec = new DvripPacketCodec();
    $header = hex2bin('ff01000002000000000000000000e90380000000');
    $decoded = $codec->decodeHeader($header);
    assertSameValue(1, $decoded['version']);
    assertSameValue(2, $decoded['session']);
    assertSameValue(1001, $decoded['message']);
    assertSameValue(128, $decoded['length']);
});

test('dvrip_rejects_payload_over_limit', function (): void {
    loadDvripFramingClasses();
    $codec = new DvripPacketCodec(2 * 1024 * 1024);
    $header = hex2bin('ff00000000000000000000000000fc03')
        . pack('V', 2 * 1024 * 1024 + 1);
    $exception = assertThrows(
        static fn () => $codec->decodeHeader($header),
        DvripException::class
    );
    assertSameValue('protocol_oversize', $exception->publicCode());
});

test('dvrip_rejects_malformed_json', function (): void {
    loadDvripFramingClasses();
    $codec = new DvripPacketCodec();
    $exception = assertThrows(
        static fn () => $codec->decodePayload("{broken}\n\0"),
        DvripException::class
    );
    assertSameValue('protocol_json', $exception->publicCode());
});

test('dvrip_accepts_camera_control_byte_enums_inside_json_strings', function (): void {
    loadDvripFramingClasses();
    $codec = new DvripPacketCodec();
    $payload = '{"Name":"NetWork.Wifi","NetWork.Wifi":{"Auth":"'
        . "\x01"
        . '","EncrypType":"'
        . "\x06"
        . '"},"Ret":100}' . "\n\0";

    $decoded = $codec->decodePayload($payload);

    assertSameValue("\x01", $decoded['NetWork.Wifi']['Auth']);
    assertSameValue("\x06", $decoded['NetWork.Wifi']['EncrypType']);
});

test('stream_transport_maps_timeout_to_stable_code', function (): void {
    loadDvripFramingClasses();
    [$transport, $peer] = socketTransportPair();

    try {
        $exception = assertThrows(
            static fn () => $transport->readExact(1),
            DvripException::class
        );
        assertSameValue('transport_timeout', $exception->publicCode());
    } finally {
        fclose($peer);
        $transport->close();
    }
});

test('stream_transport_enforces_total_read_deadline_across_fragments', function (): void {
    loadDvripFramingClasses();
    $transport = fragmentedTransport('abcdefghij', 1, 0.1, 60_000);
    try {
        $exception = assertThrows(
            static fn () => $transport->readExact(10),
            DvripException::class
        );
        assertSameValue('transport_timeout', $exception->publicCode());
    } finally {
        $transport->close();
    }
});
