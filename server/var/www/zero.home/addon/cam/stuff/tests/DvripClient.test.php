<?php

declare(strict_types=1);

use Cam\DvripClient;
use Cam\DvripException;
use Cam\DvripPacketCodec;
use Cam\DvripTransport;

require_once dirname(__DIR__) . '/php/DvripException.php';
require_once dirname(__DIR__) . '/php/DvripTransport.php';
require_once dirname(__DIR__) . '/php/DvripPacketCodec.php';
require_once dirname(__DIR__) . '/php/DvripReader.php';

function loadDvripClientClasses(): void
{
    $directory = dirname(__DIR__) . '/php';
    foreach (['DvripClient.php'] as $file) {
        $path = $directory . '/' . $file;
        if (!is_file($path)) {
            throw new RuntimeException("DVRIP client class {$file} does not exist");
        }
        require_once $path;
    }
}

final class FixtureDvripTransport implements DvripTransport
{
    public bool $connected = false;
    public bool $closed = false;
    public array $writes = [];
    private string $responses = '';
    public bool $failNextRead = false;

    public function queue(string $packet): void
    {
        $this->responses .= $packet;
    }

    public function connect(): void
    {
        $this->connected = true;
    }

    public function writeAll(string $bytes): void
    {
        $this->writes[] = $bytes;
    }

    public function readExact(int $length): string
    {
        if ($this->failNextRead) {
            $this->failNextRead = false;
            throw new DvripException('transport_timeout', 'fixture timeout');
        }
        if (strlen($this->responses) < $length) {
            throw new DvripException('transport_eof', 'fixture response exhausted');
        }
        $result = substr($this->responses, 0, $length);
        $this->responses = substr($this->responses, $length);
        return $result;
    }

    public function close(): void
    {
        $this->closed = true;
    }
}

function responsePacket(int $session, int $messageId, array $payload, int $sequence = 0): string
{
    return (new DvripPacketCodec())->encode($session, $sequence, $messageId, $payload);
}

function decodedPacket(string $packet): array
{
    $codec = new DvripPacketCodec();
    $header = $codec->decodeHeader(substr($packet, 0, DvripPacketCodec::HEADER_LENGTH));
    $payload = $codec->decodePayload(substr($packet, DvripPacketCodec::HEADER_LENGTH));
    return [$header, $payload];
}

function loggedInClient(FixtureDvripTransport $transport, int $session = 26): DvripClient
{
    $transport->queue(responsePacket($session, 1001, [
        'Ret' => 100,
        'SessionID' => sprintf('0x%08X', $session),
        'AliveInterval' => 20,
        'DeviceType ' => 'Fixture',
    ]));
    $client = new DvripClient($transport);
    $client->login('fixture-user', '');
    return $client;
}

test('dvrip_login_uses_sofia_hash_for_empty_password', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    loggedInClient($transport);
    [$header, $payload] = decodedPacket($transport->writes[0]);

    assertSameValue(1000, $header['message']);
    assertSameValue('tlJwpbo6', $payload['PassWord']);
    assertSameValue('fixture-user', $payload['UserName']);
    assertSameValue('MD5', $payload['EncryptType']);
    assertSameValue('DVRIP-Web', $payload['LoginType']);
});

test('dvrip_login_uses_known_sofia_hash_vector', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $transport->queue(responsePacket(26, 1001, [
        'Ret' => 100,
        'SessionID' => '0x0000001A',
        'AliveInterval' => 20,
    ]));
    $client = new DvripClient($transport);
    $client->login('fixture-user', 'fixture');
    [, $payload] = decodedPacket($transport->writes[0]);
    assertSameValue('FIfQxlXI', $payload['PassWord']);
});

test('dvrip_login_parses_session_for_generic_getter', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $client = loggedInClient($transport, 26);
    $transport->queue(responsePacket(26, 1043, [
        'Ret' => 100,
        'Name' => 'General',
        'SessionID' => '0x0000001A',
        'General' => ['Language' => 'English'],
    ], 1));

    assertSameValue(['Language' => 'English'], $client->getConfig('General'));
    [$header, $payload] = decodedPacket($transport->writes[1]);
    assertSameValue(1042, $header['message']);
    assertSameValue(26, $header['session']);
    assertSameValue('General', $payload['Name']);
    assertSameValue('0x0000001A', $payload['SessionID']);
});

test('dvrip_accepts_lowercase_hex_session_in_camera_payload', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $client = loggedInClient($transport, 173);
    $transport->queue(responsePacket(173, 1043, [
        'Ret' => 100,
        'Name' => 'NetWork.NetEmail',
        'SessionID' => '0x000000ad',
        'NetWork.NetEmail' => ['Enable' => false],
    ], 1));

    assertSameValue(['Enable' => false], $client->getConfig('NetWork.NetEmail'));
});

test('dvrip_system_getters_use_fixed_message_ids', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $client = loggedInClient($transport);
    $transport->queue(responsePacket(26, 1021, ['Ret' => 100, 'SystemInfo' => ['SoftWareVersion' => 'fixture']], 1));
    $transport->queue(responsePacket(26, 1361, ['Ret' => 100, 'SystemFunction' => ['NetServerFunction' => []]], 2));

    assertSameValue(['SoftWareVersion' => 'fixture'], $client->getSystemInfo());
    assertSameValue(['NetServerFunction' => []], $client->getSystemFunction());
    [$systemHeader] = decodedPacket($transport->writes[1]);
    [$functionHeader] = decodedPacket($transport->writes[2]);
    assertSameValue(1020, $systemHeader['message']);
    assertSameValue(1360, $functionHeader['message']);
});

test('dvrip_login_rejects_bad_credentials', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $transport->queue(responsePacket(0, 1001, ['Ret' => 106]));
    $client = new DvripClient($transport);
    $exception = assertThrows(
        static fn () => $client->login('fixture-user', 'wrong'),
        DvripException::class
    );
    assertSameValue('login_rejected', $exception->publicCode());
});

test('dvrip_maps_unsupported_and_missing_config_codes', function (): void {
    loadDvripClientClasses();
    foreach ([605 => 'device_unsupported', 607 => 'device_config_missing'] as $ret => $code) {
        $transport = new FixtureDvripTransport();
        $client = loggedInClient($transport);
        $transport->queue(responsePacket(26, 1043, ['Ret' => $ret, 'Name' => 'Unknown'], 1));
        $exception = assertThrows(
            static fn () => $client->getConfig('Unknown'),
            DvripException::class
        );
        assertSameValue($code, $exception->publicCode());
    }
});

test('dvrip_rejects_mismatched_command_response', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $client = loggedInClient($transport);
    $transport->queue(responsePacket(26, 1043, ['Ret' => 100, 'Other' => []], 1));
    $exception = assertThrows(
        static fn () => $client->getConfig('General'),
        DvripException::class
    );
    assertSameValue('protocol_command', $exception->publicCode());
});

test('dvrip_closes_transport_after_protocol_error', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $client = loggedInClient($transport);
    $transport->queue(responsePacket(26, 1043, ['Ret' => 100, 'Other' => []], 1));
    assertThrows(static fn () => $client->getConfig('General'), DvripException::class);
    assertTrueValue($transport->closed);
});

test('dvrip_rejects_mismatched_response_header_identity', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $client = loggedInClient($transport, 26);
    $transport->queue(responsePacket(999, 9999, [
        'Ret' => 100,
        'Name' => 'General',
        'SessionID' => '0x0000001A',
        'General' => [],
    ], 777));
    $exception = assertThrows(
        static fn () => $client->getConfig('General'),
        DvripException::class
    );
    assertSameValue('protocol_identity', $exception->publicCode());
});

test('dvrip_keeps_session_after_device_config_missing', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $client = loggedInClient($transport, 26);
    $transport->queue(responsePacket(26, 1043, ['Ret' => 607, 'Name' => 'NetWork'], 1));
    assertThrows(static fn () => $client->getConfig('NetWork'), DvripException::class);
    $transport->queue(responsePacket(26, 1043, [
        'Ret' => 100, 'Name' => 'Camera', 'Camera' => ['Mirror' => false],
    ], 2));
    assertSameValue(['Mirror' => false], $client->getConfig('Camera'));
});

test('dvrip_reauthenticates_after_transport_timeout', function (): void {
    loadDvripClientClasses();
    $transport = new FixtureDvripTransport();
    $client = loggedInClient($transport, 26);
    $transport->failNextRead = true;
    assertThrows(static fn () => $client->getConfig('NetWork'), DvripException::class);
    $transport->queue(responsePacket(27, 1001, [
        'Ret' => 100, 'SessionID' => '0x0000001B', 'AliveInterval' => 20,
    ], 2));
    $transport->queue(responsePacket(27, 1043, [
        'Ret' => 100, 'Name' => 'Camera', 'Camera' => ['Mirror' => false],
    ], 3));
    assertSameValue(['Mirror' => false], $client->getConfig('Camera'));
});
