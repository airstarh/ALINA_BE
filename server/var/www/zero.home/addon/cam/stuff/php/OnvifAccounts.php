<?php
declare(strict_types=1);
namespace Cam;

final class OnvifAccounts
{
    public function __construct(private string $url, private array $credentials) {}

    public function users(): array
    {
        $xml = $this->request('GetUsers');
        preg_match_all('/<(?:[\w.-]+:)?User\b[^>]*>(.*?)<\/(?:[\w.-]+:)?User>/s', $xml, $matches);
        $users = [];
        foreach ($matches[1] as $item) {
            $name = self::value($item, 'Username');
            $level = self::value($item, 'UserLevel');
            if ($name !== null && in_array($level, ['Administrator', 'Operator', 'User', 'Anonymous'], true)) {
                $users[] = ['username' => $name, 'level' => $level, 'editable' => $name !== 'admin'];
            }
        }
        return $users;
    }

    public function save(array $input): array
    {
        $operation = $input['operation'] ?? null;
        $name = $input['username'] ?? null;
        if (!in_array($operation, ['create', 'update'], true) || !is_string($name)
            || preg_match('/^[a-zA-Z0-9_.-]{1,32}$/D', $name) !== 1 || $name === 'admin'
            || !is_string($input['password'] ?? null) || strlen($input['password']) > 64
            || preg_match('/[\x00-\x1f\x7f]/', $input['password'])
            || !in_array($input['level'] ?? null, ['Administrator', 'Operator', 'User'], true)
            || array_diff(array_keys($input), ['service', 'operation', 'username', 'password', 'level']) !== []) {
            throw new \InvalidArgumentException('Invalid ONVIF account change. The reserved admin account is managed through DVRIP.');
        }
        $users = array_column($this->users(), null, 'username');
        if (($operation === 'create') === isset($users[$name])) {
            throw new \InvalidArgumentException($operation === 'create' ? 'ONVIF account already exists.' : 'ONVIF account no longer exists. Refresh the form.');
        }
        if ($operation === 'update' && $users[$name]['level'] !== $input['level']) {
            throw new \InvalidArgumentException('This ONVIF editor changes passwords only; existing roles are not editable.');
        }
        $xml = '<User><tt:Username>' . self::escape($name) . '</tt:Username><tt:Password>'
            . self::escape($input['password']) . '</tt:Password><tt:UserLevel>' . $input['level'] . '</tt:UserLevel></User>';
        $this->request($operation === 'create' ? 'CreateUsers' : 'SetUser', $xml);
        $updated = array_column($this->users(), null, 'username');
        if (($updated[$name]['level'] ?? null) !== $input['level']) {
            throw new \RuntimeException('ONVIF account readback did not match. Refresh to inspect the current camera state.');
        }
        return ['saved' => true, 'message' => 'Camera accepted the ONVIF account change; account name and role were read back. Passwords cannot be read back.',
            'users' => array_values($updated)];
    }

    private function request(string $operation, string $content = ''): string
    {
        $nonce = random_bytes(16);
        $created = gmdate('Y-m-d\TH:i:s\Z');
        $digest = base64_encode(sha1($nonce . $created . ($this->credentials['password'] ?? ''), true));
        $xml = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:tt="http://www.onvif.org/ver10/schema" '
            . 'xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" '
            . 'xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">'
            . '<s:Header><wsse:Security><wsse:UsernameToken><wsse:Username>' . self::escape($this->credentials['username'] ?? '')
            . '</wsse:Username><wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">'
            . $digest . '</wsse:Password><wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'
            . base64_encode($nonce) . '</wsse:Nonce><wsu:Created>' . $created . '</wsu:Created></wsse:UsernameToken></wsse:Security></s:Header>'
            . '<s:Body><' . $operation . ' xmlns="http://www.onvif.org/ver10/device/wsdl">' . $content . '</' . $operation . '></s:Body></s:Envelope>';
        $handle = curl_init($this->url);
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
            $reason = self::value($body, 'Text');
            throw new \InvalidArgumentException('ONVIF ' . $operation . ' failed.' . ($reason !== null ? ' ' . substr($reason, 0, 200) : ' Camera did not return a success response.'));
        }
        return $body;
    }

    private static function escape(string $value): string { return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
    private static function value(string $xml, string $name): ?string
    {
        return preg_match('/<(?:[\w.-]+:)?' . $name . '\b[^>]*>(.*?)<\/(?:[\w.-]+:)?' . $name . '>/s', $xml, $match)
            ? html_entity_decode(trim($match[1]), ENT_XML1 | ENT_QUOTES, 'UTF-8') : null;
    }
}
