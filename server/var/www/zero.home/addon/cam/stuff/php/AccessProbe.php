<?php

declare(strict_types=1);

namespace Cam;

/** Bounded, read-only probes, restricted to the configured camera host. */
final class AccessProbe
{
    public function discover(string $host, array $ports, array $roles = []): array
    {
        $result = ['tcp' => [], 'http' => [], 'onvif' => [], 'streams' => [], 'discovery' => false];
        $discovery = $this->discovery($host);
        $result['discovery'] = $discovery !== [];
        foreach ($discovery as $url) {
            $ports[] = (int) (parse_url($url, PHP_URL_PORT) ?: 80);
        }
        $ports = array_values(array_unique(array_filter($ports, static fn ($p) => is_int($p) && $p > 0 && $p <= 65535)));
        sort($ports);
        foreach ($ports as $port) {
            $socket = @stream_socket_client('tcp://' . $this->address($host) . ':' . $port, $errno, $error, .25);
            $result['tcp'][$port] = $socket !== false;
            if ($socket !== false) fclose($socket);
        }

        // Probe HTTP only on likely web/ONVIF ports; never send HTTP to the DVRIP listener.
        $webPorts = array_unique(array_merge([80, 443, 8000, 8080, 8443, 8899],
            array_intersect_key($roles, array_flip(['http', 'https', 'onvif'])),
            array_map(static fn ($url) => (int) (parse_url($url, PHP_URL_PORT) ?: 80), $discovery)));
        foreach ($webPorts as $port) {
            if (!($result['tcp'][$port] ?? false) || in_array($port, [21, 22, 23, $roles['rtsp'] ?? 554, $roles['dvrip'] ?? 34567], true)) continue;
            $scheme = in_array($port, [443, 8443, $roles['https'] ?? 443], true) ? 'https' : 'http';
            $base = $scheme . '://' . $this->address($host) . ':' . $port;
            $response = $this->request($base . '/');
            if ($response['status'] > 0) $result['http'][$port] = ['url' => $base . '/', 'status' => $response['status']];
            if ($port === ($roles['http'] ?? 80) && $response['status'] > 0) {
                $snapshotUrl = $base . '/webcapture.jpg?command=snap&channel=0';
                $snapshot = $this->request($snapshotUrl);
                if ($snapshot['status'] === 200 && str_starts_with($snapshot['body'], "\xff\xd8\xff")) {
                    $result['snapshot'] = $snapshotUrl;
                }
            }
            if ($result['onvif'] !== []) continue;
            $paths = [$base . '/onvif/device_service'];
            foreach ($discovery as $url) if ((parse_url($url, PHP_URL_PORT) ?: 80) === $port) $paths[] = $url;
            foreach (array_unique($paths) as $url) {
                $services = self::services($this->soap($url, 'http://www.onvif.org/ver10/device/wsdl',
                    'GetServices', '<IncludeCapability>false</IncludeCapability>'));
                $services = array_values(array_filter($services, fn ($s) => $this->sameCamera($s['url'], $host)));
                if ($services === []) continue;
                $result['onvif'] = ['url' => $url, 'services' => $services];
                foreach ($services as $service) {
                    if ($service['namespace'] !== 'http://www.onvif.org/ver10/media/wsdl') continue;
                    $profiles = $this->soap($service['url'], $service['namespace'], 'GetProfiles');
                    preg_match_all('/<(?:[\w.-]+:)?Profiles\b[^>]*\btoken=["\x27]([^"\x27]+)["\x27]/', $profiles, $matches);
                    foreach (array_slice(array_unique($matches[1]), 0, 8) as $token) {
                        $xml = $this->soap($service['url'], $service['namespace'], 'GetStreamUri',
                            '<StreamSetup><Stream xmlns="http://www.onvif.org/ver10/schema">RTP-Unicast</Stream>'
                            . '<Transport xmlns="http://www.onvif.org/ver10/schema"><Protocol>RTSP</Protocol></Transport></StreamSetup>'
                            . '<ProfileToken>' . htmlspecialchars(self::decode($token), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</ProfileToken>');
                        $uri = self::tag($xml, 'Uri');
                        if ($uri !== null && in_array(parse_url($uri, PHP_URL_SCHEME), ['rtsp', 'rtsps'], true)) {
                            $result['streams'][] = ['profile' => self::decode($token), 'url' => self::streamUrl($uri)];
                        }
                    }
                    break;
                }
                break;
            }
        }
        $rtspPort = $roles['rtsp'] ?? 554;
        if ($result['tcp'][$rtspPort] ?? false) {
            $socket = @stream_socket_client('tcp://' . $this->address($host) . ':' . $rtspPort, $errno, $error, .25);
            if ($socket !== false) {
                stream_set_timeout($socket, 0, 500000);
                @fwrite($socket, "OPTIONS rtsp://" . $this->address($host) . ':' . $rtspPort . "/ RTSP/1.0\r\nCSeq: 1\r\n\r\n");
                $reply = @fgets($socket, 1024);
                $result['rtsp'] = is_string($reply) && str_starts_with($reply, 'RTSP/');
                fclose($socket);
            }
        }
        return $result;
    }

    public static function services(string $xml): array
    {
        if (!preg_match('/<(?:[\w.-]+:)?GetServicesResponse\b/', $xml)) return [];
        preg_match_all('/<(?:[\w.-]+:)?Service\b[^>]*>(.*?)<\/(?:[\w.-]+:)?Service>/s', $xml, $matches);
        $services = [];
        foreach ($matches[1] as $item) {
            $url = self::tag($item, 'XAddr');
            $namespace = self::tag($item, 'Namespace');
            if ($url !== null && $namespace !== null) $services[] = ['namespace' => $namespace, 'url' => $url];
        }
        return $services;
    }

    public static function streamUrl(string $url): string
    {
        // Some XM firmwares put credentials in the path, not in normal URL userinfo.
        $url = preg_replace('~^(rtsps?://)[^/@]+@~i', '$1', $url);
        return preg_replace_callback('/(?<![a-z0-9])(user(?:name)?|password|passwd|pwd)=.*?(?=&|_(?:user(?:name)?|password|passwd|pwd|channel|stream|protocol)=|[?\/\s]|$)/i',
            static fn ($m) => $m[1] . '=' . (str_starts_with(strtolower($m[1]), 'user') ? '{login}' : '{password}'), $url);
    }

    private function soap(string $url, string $namespace, string $operation, string $content = ''): string
    {
        $body = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"><s:Body><' . $operation
            . ' xmlns="' . $namespace . '">' . $content . '</' . $operation . '></s:Body></s:Envelope>';
        return $this->request($url, $body)['body'];
    }

    private function request(string $url, ?string $body = null): array
    {
        $handle = curl_init($url);
        $received = '';
        curl_setopt_array($handle, [CURLOPT_CONNECTTIMEOUT_MS => 400, CURLOPT_TIMEOUT_MS => 1000,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROXY => '', CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            // Discovery sends no credentials; a self-signed certificate must not hide a TLS listener.
            CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$received): int {
                if (strlen($received) + strlen($chunk) > 131072) return 0;
                $received .= $chunk;
                return strlen($chunk);
            },
        ]);
        if ($body !== null) curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/soap+xml; charset=utf-8']]);
        curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        return ['status' => $status, 'body' => $received];
    }

    private function discovery(string $host): array
    {
        $socket = @stream_socket_client('udp://' . $this->address($host) . ':3702', $errno, $error, .25);
        if ($socket === false) return [];
        stream_set_timeout($socket, 0, 300000);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        $xml = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://schemas.xmlsoap.org/ws/2004/08/addressing"'
            . ' xmlns:d="http://schemas.xmlsoap.org/ws/2005/04/discovery" xmlns:dn="http://www.onvif.org/ver10/network/wsdl">'
            . '<s:Header><a:MessageID>urn:uuid:' . $uuid . '</a:MessageID>'
            . '<a:To>urn:schemas-xmlsoap-org:ws:2005:04:discovery</a:To>'
            . '<a:Action>http://schemas.xmlsoap.org/ws/2005/04/discovery/Probe</a:Action></s:Header>'
            . '<s:Body><d:Probe><d:Types>dn:NetworkVideoTransmitter</d:Types></d:Probe></s:Body></s:Envelope>';
        @fwrite($socket, $xml);
        $reply = @fread($socket, 65535);
        fclose($socket);
        $addresses = self::tag(is_string($reply) ? $reply : '', 'XAddrs');
        return array_slice(array_values(array_filter(preg_split('/\s+/', $addresses ?? '') ?: [], fn ($url) => $this->sameCamera($url, $host))), 0, 8);
    }

    private function sameCamera(string $url, string $host): bool
    {
        $parts = parse_url($url);
        if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])) return false;
        $target = trim($parts['host'] ?? '', '[]');
        return $target !== '' && ($target === trim($host, '[]') || $target === gethostbyname($host));
    }

    private function address(string $host): string
    {
        return str_contains($host, ':') ? '[' . trim($host, '[]') . ']' : $host;
    }

    private static function tag(string $xml, string $name): ?string
    {
        return preg_match('/<(?:[\w.-]+:)?' . $name . '\b[^>]*>(.*?)<\/(?:[\w.-]+:)?' . $name . '>/s', $xml, $match)
            ? self::decode(trim($match[1])) : null;
    }

    private static function decode(string $value): string
    {
        return html_entity_decode($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
