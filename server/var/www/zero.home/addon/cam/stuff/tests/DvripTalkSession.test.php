<?php

declare(strict_types=1);

use Cam\DvripDuplexTransport;
use Cam\DvripException;
use Cam\DvripTalkSession;

require_once dirname(__DIR__) . '/php/DvripException.php';
require_once dirname(__DIR__) . '/php/DvripTransport.php';
require_once dirname(__DIR__) . '/php/DvripDuplexTransport.php';
require_once dirname(__DIR__) . '/php/DvripPacketCodec.php';
require_once dirname(__DIR__) . '/php/DvripTalkPacketCodec.php';
require_once dirname(__DIR__) . '/php/DvripReader.php';
require_once dirname(__DIR__) . '/php/DvripClient.php';
require_once dirname(__DIR__) . '/php/DvripTalkSession.php';

final class FakeTalkTransport implements DvripDuplexTransport
{
    public bool $connected = false;
    public bool $blocking = true;
    public int $closeCalls = 0;
    public array $writes = [];
    public string $reads = '';
    public string $available = '';
    /** @var resource */
    private $resource;

    public function __construct()
    {
        $this->resource = fopen('php://temp', 'w+');
    }

    public function connect(): void
    {
        $this->connected = true;
    }

    public function writeAll(string $bytes): void
    {
        if (!$this->connected) {
            throw new DvripException('transport_closed', 'Camera connection is not open.');
        }
        $this->writes[] = $bytes;
    }

    public function readExact(int $length): string
    {
        if (strlen($this->reads) < $length) {
            throw new DvripException('transport_eof', 'Camera closed during read.');
        }
        $value = substr($this->reads, 0, $length);
        $this->reads = substr($this->reads, $length);
        return $value;
    }

    public function readAvailable(int $maximumBytes = 65536): string
    {
        $value = substr($this->available, 0, $maximumBytes);
        $this->available = substr($this->available, strlen($value));
        return $value;
    }

    public function setBlocking(bool $blocking): void
    {
        $this->blocking = $blocking;
    }

    public function stream()
    {
        return $this->resource;
    }

    public function close(): void
    {
        $this->connected = false;
        $this->closeCalls++;
    }
}

function talkJsonPacket(int $session, int $sequence, int $message, array $payload): string
{
    $json = json_encode($payload, JSON_THROW_ON_ERROR) . "\n\0";
    return pack('CCx2VVx2vV', 0xff, 1, $session, $sequence, $message, strlen($json)) . $json;
}

/** @return array{FakeTalkTransport, FakeTalkTransport} */
function successfulTalkTransports(): array
{
    $control = new FakeTalkTransport();
    $control->reads = talkJsonPacket(0xc4, 0, 1001, [
        'Ret' => 100,
        'SessionID' => '0x000000c4',
    ]) . talkJsonPacket(0xc4, 0, 0x0597, [
        'Name' => 'OPTalk',
        'Ret' => 100,
        'SessionID' => '0x000000c4',
    ]);
    $media = new FakeTalkTransport();
    $media->reads = talkJsonPacket(0xc4, 1, 0x059b, [
        'Name' => 'OPTalk',
        'Ret' => 100,
        'SessionID' => '0x000000c4',
    ]);
    return [$control, $media];
}

test('talk session uses separate control and media sockets and mirrors captured lifecycle', function (): void {
    [$control, $media] = successfulTalkTransports();
    $session = new DvripTalkSession($control, $media);

    $session->open('admin', 'admin');
    $session->send(str_repeat("\x55", 320));
    $session->close();
    $session->close();

    assertSameValue(true, $control->blocking);
    assertSameValue(false, $media->blocking);
    assertSameValue(3, count($control->writes));
    assertSameValue(2, count($media->writes));
    assertSameValue(1000, unpack('vmessage', substr($control->writes[0], 14, 2))['message']);
    assertSameValue(0x0596, unpack('vmessage', substr($control->writes[1], 14, 2))['message']);
    assertSameValue(0x0596, unpack('vmessage', substr($control->writes[2], 14, 2))['message']);
    assertSameValue(0x059a, unpack('vmessage', substr($media->writes[0], 14, 2))['message']);
    assertSameValue(0x0598, unpack('vmessage', substr($media->writes[1], 14, 2))['message']);
    assertSameValue(0, unpack('Vsequence', substr($media->writes[1], 8, 4))['sequence']);
    assertSameValue(1, $control->closeCalls);
    assertSameValue(1, $media->closeCalls);
});

test('talk session rejects failed DVRIP login and closes transport', function (): void {
    $transport = new FakeTalkTransport();
    $transport->reads = talkJsonPacket(0, 0, 1001, ['Ret' => 101, 'SessionID' => '0x00000000']);
    $session = new DvripTalkSession($transport, new FakeTalkTransport());

    $error = assertThrows(fn () => $session->open('admin', 'wrong'), DvripException::class);

    assertSameValue('login_rejected', $error->publicCode());
    assertSameValue(1, $transport->closeCalls);
});

test('talk session rejects failed claim and closes both transports', function (): void {
    $control = new FakeTalkTransport();
    $control->reads = talkJsonPacket(0xc4, 0, 1001, [
        'Ret' => 100,
        'SessionID' => '0x000000c4',
    ]);
    $media = new FakeTalkTransport();
    $media->reads = talkJsonPacket(0xc4, 1, 0x059b, [
        'Name' => 'OPTalk',
        'Ret' => 101,
        'SessionID' => '0x000000c4',
    ]);
    $session = new DvripTalkSession($control, $media);

    $error = assertThrows(fn () => $session->open('admin', 'admin'), DvripException::class);

    assertSameValue('talk_rejected', $error->publicCode());
    assertSameValue(1, $control->closeCalls);
    assertSameValue(1, $media->closeCalls);
});

test('talk session reports camera EOF during handshake', function (): void {
    $transport = new FakeTalkTransport();
    $session = new DvripTalkSession($transport, new FakeTalkTransport());

    $error = assertThrows(fn () => $session->open('admin', 'admin'), DvripException::class);

    assertSameValue('transport_eof', $error->publicCode());
    assertSameValue(1, $transport->closeCalls);
});

test('talk session drains complete incoming media packets and retains a partial packet', function (): void {
    [$control, $mediaTransport] = successfulTalkTransports();
    $session = new DvripTalkSession($control, $mediaTransport);
    $session->open('admin', 'admin');
    $media = str_repeat("\x33", 168);
    $packet = pack('CCx2VVx2vV', 0xff, 1, 0xc4, 2, 0x0599, strlen($media)) . $media;
    $mediaTransport->available = $packet . substr($packet, 0, 12);

    assertSameValue(1, $session->drain());
    assertSameValue(0, $session->drain());
    $session->close();
});

test('talk session rejects an excessive buffered camera packet', function (): void {
    [$control, $mediaTransport] = successfulTalkTransports();
    $session = new DvripTalkSession($control, $mediaTransport, maximumBufferedBytes: 128);
    $session->open('admin', 'admin');
    $mediaTransport->available = str_repeat("\x00", 129);

    $error = assertThrows(fn (): int => $session->drain(), DvripException::class);

    assertSameValue('protocol_oversize', $error->publicCode());
    $session->close();
});
