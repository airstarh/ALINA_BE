<?php

declare(strict_types=1);

namespace Cam;

require_once __DIR__ . '/DvripException.php';

use Closure;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class OnvifPtz
{
    private const MEDIA_NAMESPACE = 'http://www.onvif.org/ver10/media/wsdl';
    private const PTZ_NAMESPACE = 'http://www.onvif.org/ver20/ptz/wsdl';
    private const SPEEDS = ['low' => 0.2, 'medium' => 0.5, 'high' => 1.0];
    private const VECTORS = [
        'up' => [0.0, 1.0, 0.0], 'up-right' => [1.0, 1.0, 0.0],
        'right' => [1.0, 0.0, 0.0], 'down-right' => [1.0, -1.0, 0.0],
        'down' => [0.0, -1.0, 0.0], 'down-left' => [-1.0, -1.0, 0.0],
        'left' => [-1.0, 0.0, 0.0], 'up-left' => [-1.0, 1.0, 0.0],
        'zoom-in' => [0.0, 0.0, 1.0], 'zoom-out' => [0.0, 0.0, -1.0],
    ];

    private Closure $requester;
    private Closure $sleeper;

    public function __construct(
        private string $mediaUrl,
        private string $ptzUrl,
        private array $credentials,
        ?Closure $requester = null,
        ?Closure $sleeper = null,
    ) {
        $this->requester = $requester ?? fn (string $url, string $namespace, string $operation, string $content): string
            => $this->request($url, $namespace, $operation, $content);
        $this->sleeper = $sleeper ?? static fn (int $microseconds): mixed => usleep($microseconds);
    }

    public function pulse(string $direction, string $speed, int $durationMs): void
    {
        if (!isset(self::VECTORS[$direction]) || !isset(self::SPEEDS[$speed])
            || $durationMs < 80 || $durationMs > 400) {
            throw new InvalidArgumentException('Invalid PTZ movement pulse.');
        }

        $token = $this->profileToken();
        [$x, $y, $zoom] = self::VECTORS[$direction];
        $factor = self::SPEEDS[$speed];
        $velocity = $zoom === 0.0
            ? '<tt:PanTilt x="' . self::number($x * $factor) . '" y="' . self::number($y * $factor) . '"/>'
            : '<tt:Zoom x="' . self::number($zoom * $factor) . '"/>';
        $content = '<tptz:ProfileToken>' . self::escape($token) . '</tptz:ProfileToken>'
            . '<tptz:Velocity>' . $velocity . '</tptz:Velocity>'
            . '<tptz:Timeout>PT' . self::number($durationMs / 1000) . 'S</tptz:Timeout>';
        $movementError = null;
        try {
            ($this->requester)($this->ptzUrl, self::PTZ_NAMESPACE, 'ContinuousMove', $content);
            ($this->sleeper)($durationMs * 1000);
        } catch (Throwable $error) {
            $movementError = $error;
        } finally {
            try {
                ($this->requester)($this->ptzUrl, self::PTZ_NAMESPACE, 'Stop',
                    '<tptz:ProfileToken>' . self::escape($token) . '</tptz:ProfileToken>'
                    . '<tptz:PanTilt>true</tptz:PanTilt><tptz:Zoom>true</tptz:Zoom>');
            } catch (Throwable $stopError) {
                throw new DvripException(
                    'ptz_stop_failed',
                    'ONVIF Stop failed; reload PTZ controls before moving again.',
                    $stopError,
                );
            }
        }
        if ($movementError !== null) throw $movementError;
    }

    private function profileToken(): string
    {
        $xml = ($this->requester)($this->mediaUrl, self::MEDIA_NAMESPACE, 'GetProfiles', '');
        if (!preg_match('/<(?:[\w.-]+:)?Profiles\b[^>]*\btoken=["\x27]([^"\x27]+)["\x27]/', $xml, $match)) {
            throw new RuntimeException('ONVIF media profile is unavailable.');
        }
        return html_entity_decode($match[1], ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function request(string $url, string $namespace, string $operation, string $content): string
    {
        $nonce = random_bytes(16);
        $created = gmdate('Y-m-d\TH:i:s\Z');
        $digest = base64_encode(sha1($nonce . $created . ($this->credentials['password'] ?? ''), true));
        $prefix = $namespace === self::MEDIA_NAMESPACE ? 'trt' : 'tptz';
        $xml = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:tt="http://www.onvif.org/ver10/schema"'
            . ' xmlns:trt="' . self::MEDIA_NAMESPACE . '" xmlns:tptz="' . self::PTZ_NAMESPACE . '"'
            . ' xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"'
            . ' xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'
            . '<s:Header><wsse:Security><wsse:UsernameToken><wsse:Username>' . self::escape($this->credentials['username'] ?? '')
            . '</wsse:Username><wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'
            . $digest . '</wsse:Password><wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'
            . base64_encode($nonce) . '</wsse:Nonce><wsu:Created>' . $created . '</wsu:Created></wsse:UsernameToken></wsse:Security></s:Header>'
            . '<s:Body><' . $prefix . ':' . $operation . '>' . $content . '</' . $prefix . ':' . $operation . '></s:Body></s:Envelope>';
        $handle = curl_init($url);
        $body = '';
        curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $xml, CURLOPT_PROXY => '',
            CURLOPT_CONNECTTIMEOUT_MS => 1000, CURLOPT_TIMEOUT_MS => 4000, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => ['Content-Type: application/soap+xml; charset=utf-8'],
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > 131072) return 0;
                $body .= $chunk;
                return strlen($chunk);
            }]);
        $success = curl_exec($handle);
        curl_close($handle);
        if ($success === false || !preg_match('/<(?:[\w.-]+:)?' . $operation . 'Response\b/', $body)) {
            throw new RuntimeException('ONVIF ' . $operation . ' failed.');
        }
        return $body;
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

