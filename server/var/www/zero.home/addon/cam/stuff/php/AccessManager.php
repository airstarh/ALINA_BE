<?php
declare(strict_types=1);
namespace Cam;

use InvalidArgumentException;
use Throwable;

/** Native account manager. Password hashes never leave this class's snapshots. */
final class AccessManager
{
    public function __construct(private DvripReader $reader, private array $camera,
        private ?\Closure $verify = null) {}

    public function settings(): array { return $this->camera; }

    private function snapshot(): array
    {
        foreach (['getUsers', 'getGroups', 'getAuthorities'] as $method) {
            if (!method_exists($this->reader, $method)) throw new InvalidArgumentException('This camera connection does not expose account management.');
        }
        $snapshot = ['users' => $this->reader->getUsers(), 'groups' => $this->reader->getGroups(),
            'permissions' => $this->reader->getAuthorities(), 'network' => $this->reader->getConfig('NetWork'),
            'correspondent' => CameraAccess::correspondent($this->reader)];
        if ($snapshot['permissions'] === []) throw new InvalidArgumentException('The camera did not provide its permission catalogue. Account editing is unavailable.');
        $existing = [];
        foreach (array_merge($snapshot['users'], $snapshot['groups']) as $entry) $existing = array_merge($existing, $entry['AuthorityList'] ?? []);
        $snapshot['retainedPermissions'] = array_values(array_unique(array_diff($existing, $snapshot['permissions'])));
        $snapshot['permissions'] = array_values(array_unique(array_merge($snapshot['permissions'], $existing)));
        return $snapshot;
    }

    private static function paths(array $network, array $correspondent = []): array
    {
        $paths = ['dvrip' => ['NetCommon', 'TCPPort'], 'http' => ['NetCommon', 'HttpPort'],
            'https' => ['NetCommon', 'SSLPort'], 'rtsp' => ['RTSP', 'Server', 'Port']];
        foreach ([['NetCommon', 'OnvifPort'], ['NetCommon', 'ONVIFPort'], ['Onvif', 'Port']] as $path) {
            if (is_int(self::at($network, $path))) { $paths['onvif'] = $path; break; }
        }
        $paths = array_filter($paths, fn ($path) => is_int(self::at($network, $path)));
        if (is_int($correspondent['Port'] ?? null)) $paths['onvif'] = ['Port'];
        return $paths;
    }

    private static function at(array $value, array $path): mixed
    {
        foreach ($path as $key) { if (!is_array($value) || !array_key_exists($key, $value)) return null; $value = $value[$key]; }
        return $value;
    }

    private static function put(array &$value, array $path, mixed $new): void
    {
        $target = &$value;
        foreach ($path as $key) $target = &$target[$key];
        $target = $new;
    }

    private static function samePermissions(array $a, array $b): bool { sort($a); sort($b); return $a === $b; }

    private static function revision(array $snapshot): string
    {
        $ports = [];
        foreach (self::paths($snapshot['network'], $snapshot['correspondent']) as $id => $path) {
            $ports[$id] = self::at($id === 'onvif' && $path === ['Port'] ? $snapshot['correspondent'] : $snapshot['network'], $path);
        }
        return hash('sha256', json_encode([$snapshot['users'], $snapshot['groups'], $snapshot['permissions'], $ports], JSON_THROW_ON_ERROR));
    }

    public function model(array $inventory): array
    {
        // Keep the services-only fallback free of legacy credential editors too.
        foreach ($inventory['points'] as &$point) unset($point['password'], $point['username'], $point['accountManager'], $point['credentialNote']);
        unset($point, $inventory['accounts'], $inventory['groups'], $inventory['onvifUsers']);
        try { $snapshot = $this->snapshot(); }
        catch (InvalidArgumentException | DvripException $error) {
            if ($error instanceof DvripException && !in_array($error->publicCode(), ['device_unsupported', 'device_config_missing', 'device_error'], true)) throw $error;
            return $inventory + ['users' => [], 'groupRows' => [], 'permissions' => [], 'managementError' => $error->getMessage()];
        }
        $current = $this->camera['dvrip']['username'];
        $users = [];
        foreach ($snapshot['users'] as $u) {
            $users[] = ['name' => $u['Name'], 'group' => $u['Group'], 'memo' => $u['Memo'] ?? '',
                'memoEditable' => array_key_exists('Memo', $u), 'multilogin' => (bool) ($u['Sharable'] ?? false),
                'permissions' => $u['AuthorityList'] ?? [], 'reserved' => (bool) ($u['Reserved'] ?? false),
                'active' => $u['Name'] === $current,
                'createdVia' => $this->camera['accessOrigins'][$u['Name']]['api'] ?? 'Unknown'];
        }
        $groups = array_map(fn ($g) => ['name' => $g['Name'], 'memo' => $g['Memo'] ?? '',
            'memoEditable' => array_key_exists('Memo', $g), 'permissions' => $g['AuthorityList'] ?? [],
            'active' => in_array($current, array_column(array_filter($snapshot['users'], fn ($u) => $u['Group'] === $g['Name']), 'Name'), true)], $snapshot['groups']);
        $paths = self::paths($snapshot['network'], $snapshot['correspondent']);
        foreach ($inventory['points'] as &$point) {
            $point['portEditable'] = isset($paths[$point['id']]);
            $point['portReason'] = $point['id'] === 'snapshot_http' ? 'Uses the HTTP service port.'
                : ($point['portEditable'] ? 'A change is kept only after its live listener is verified.' : 'No verified port-write API is available.');
            unset($point['password'], $point['username'], $point['accountManager'], $point['credentialNote']);
        }
        unset($point);
        $inventory['users'] = $users;
        $inventory['groupRows'] = $groups;
        $inventory['permissions'] = array_values(array_unique($snapshot['permissions']));
        $inventory['retainedPermissions'] = $snapshot['retainedPermissions'];
        $inventory['revision'] = self::revision($snapshot);
        $inventory['activeUsername'] = $current;
        $inventory['note'] = 'Manage the camera accounts, groups and incoming services. Blank password fields preserve existing passwords. Changes apply when you press Save changes.';
        unset($inventory['accounts'], $inventory['groups'], $inventory['onvifUsers']);
        return $inventory;
    }

    public function save(array $input, \Closure $persist): array
    {
        $snapshot = $this->snapshot();
        if (!is_string($input['revision'] ?? null) || !hash_equals(self::revision($snapshot), $input['revision'])) {
            throw new InvalidArgumentException('Camera settings changed since this page was loaded. Refresh before saving.');
        }
        $plan = $this->validate($input, $snapshot);
        $applied = [];
        $error = null;
        // Groups before members; removals after users; listener changes last.
        $ordered = array_merge(array_filter($plan['groups'], fn ($g) => !$g['delete']),
            array_filter($plan['users'], fn ($u) => $u['delete']),
            array_filter($plan['users'], fn ($u) => !$u['delete']),
            array_filter($plan['groups'], fn ($g) => $g['delete']), $plan['services']);
        foreach ($ordered as $change) {
            try {
                if ($change['kind'] === 'group') $this->group($change);
                elseif ($change['kind'] === 'user') $this->user($change, $persist);
                else $this->port($change, $persist);
                $applied[] = ['kind' => $change['kind'], 'key' => $change['key'], 'name' => $change['name'] ?? null];
            } catch (Throwable $failure) {
                $error = ($change['kind'] === 'service' ? $change['id'] : $change['name']) . ': ' . $failure->getMessage();
                break;
            }
        }
        return ['complete' => $error === null, 'applied' => $applied, 'error' => $error,
            'message' => $error === null ? 'All changed settings saved and checked.' : 'Some changes were not saved. ' . $error];
    }

    private function validate(array $input, array $snapshot): array
    {
        if (array_diff(array_keys($input), ['revision', 'users', 'groups', 'services']) !== []) throw new InvalidArgumentException('Unknown access change fields.');
        $plan = ['users' => [], 'groups' => [], 'services' => []];
        $existingUsers = array_column($snapshot['users'], null, 'Name');
        $existingGroups = array_column($snapshot['groups'], null, 'Name');
        $finalGroups = $existingGroups;
        $finalUsers = $existingUsers;
        $seen = [];
        foreach (['groups', 'users'] as $kind) {
            if (!is_array($input[$kind] ?? null) || !array_is_list($input[$kind]) || count($input[$kind]) > 100) throw new InvalidArgumentException('Invalid account change list.');
            foreach ($input[$kind] as $row) {
                if (!is_array($row) || !is_string($row['key'] ?? null) || strlen($row['key']) > 100
                    || !array_key_exists('originalName', $row) || (!is_string($row['originalName']) && $row['originalName'] !== null)
                    || !is_bool($row['delete'] ?? null)) throw new InvalidArgumentException('Invalid account row.');
                if (isset($seen[$kind][$row['key']])) throw new InvalidArgumentException('Duplicate account change.');
                $seen[$kind][$row['key']] = true;
                $original = $row['originalName'];
                if ($original !== null && isset($seen[$kind . '-original'][$original])) throw new InvalidArgumentException('Duplicate original account or group.');
                if ($original !== null) $seen[$kind . '-original'][$original] = true;
                $existing = $kind === 'users' ? $existingUsers : $existingGroups;
                if ($original !== null && !isset($existing[$original])) throw new InvalidArgumentException('Account or group no longer exists.');
                if (!is_string($row['name'] ?? null) || !preg_match('/^[a-zA-Z0-9_.-]{1,32}$/D', $row['name'])) throw new InvalidArgumentException('Names must contain 1–32 letters, digits, dots, hyphens or underscores.');
                if (!$row['delete']) {
                    if (isset($seen[$kind . '-name'][strtolower($row['name'])])) throw new InvalidArgumentException('Duplicate account or group names.');
                    $seen[$kind . '-name'][strtolower($row['name'])] = true;
                }
                if (!is_string($row['memo'] ?? null) || strlen($row['memo']) > 256) throw new InvalidArgumentException('Invalid memo.');
                if (!is_array($row['permissions'] ?? null) || !array_is_list($row['permissions'])
                    || count(array_filter($row['permissions'], 'is_string')) !== count($row['permissions'])
                    || array_diff($row['permissions'], $snapshot['permissions']) !== []
                    || count(array_unique($row['permissions'])) !== count($row['permissions'])) throw new InvalidArgumentException('A permission is not advertised by this camera.');
                if ($kind === 'users') {
                    if (!is_bool($row['multilogin'] ?? null) || !is_string($row['group'] ?? null) || !is_bool($row['setEmpty'] ?? null)
                        || !array_key_exists('password', $row) || ($row['password'] !== null && !is_string($row['password']))) throw new InvalidArgumentException('Invalid user fields.');
                    if (is_string($row['password']) && (strlen($row['password']) > 64 || preg_match('/[\x00-\x1f\x7f]/', $row['password']))) throw new InvalidArgumentException('Invalid password.');
                    if (!$row['setEmpty'] && $row['password'] === '') $row['password'] = null;
                    if ($row['setEmpty']) $row['password'] = '';
                    if ($original === null && !$row['delete'] && $row['password'] === null) throw new InvalidArgumentException('Set a password or choose Set empty for a new user.');
                    $old = $existingUsers[$original] ?? [];
                    if (($old['Reserved'] ?? false) || $original === $this->camera['dvrip']['username']) {
                        if ($row['delete'] || $row['name'] !== $original) throw new InvalidArgumentException('The reserved or connected account cannot be deleted or renamed.');
                    }
                    if ($original === $this->camera['dvrip']['username'] && array_diff(['Account', 'NetConfig'], $row['permissions']) !== []) throw new InvalidArgumentException('The connected account must retain Account and NetConfig access.');
                    if ($original !== null) unset($finalUsers[$original]);
                    if (!$row['delete']) $finalUsers[$row['name']] = ['Name' => $row['name'], 'Group' => $row['group'], 'AuthorityList' => $row['permissions']];
                } else {
                    $activeGroups = array_column(array_filter($snapshot['users'], fn ($u) => $u['Name'] === $this->camera['dvrip']['username']), 'Group');
                    if (in_array($original, $activeGroups, true) && ($row['delete'] || $row['name'] !== $original)) throw new InvalidArgumentException('The connected account group cannot be deleted or renamed.');
                    if ($original !== null) unset($finalGroups[$original]);
                    if (!$row['delete']) $finalGroups[$row['name']] = ['Name' => $row['name'], 'AuthorityList' => $row['permissions']];
                }
                if (!$row['delete'] && $row['name'] !== $original && isset($existing[$row['name']])) throw new InvalidArgumentException('That account or group name already exists.');
                $row['kind'] = $kind === 'users' ? 'user' : 'group';
                $plan[$kind][] = $row;
            }
        }
        foreach ($plan['groups'] as $g) if (!$g['delete'] && $g['originalName'] !== null && $g['name'] !== $g['originalName']) {
            foreach ($finalUsers as &$u) if ($u['Group'] === $g['originalName']) $u['Group'] = $g['name'];
            unset($u);
            foreach ($plan['users'] as &$userRow) if ($userRow['group'] === $g['originalName']) $userRow['group'] = $g['name'];
            unset($userRow);
        }
        foreach ($finalUsers as $u) {
            if (!isset($finalGroups[$u['Group']])) throw new InvalidArgumentException('A user still references a removed or unknown group.');
            if (array_diff($u['AuthorityList'], $finalGroups[$u['Group']]['AuthorityList']) !== []) throw new InvalidArgumentException('User permissions must be within the selected group permissions.');
        }
        foreach ([$finalUsers, $finalGroups] as $entries) {
            $names = array_map('strtolower', array_keys($entries));
            if (count($names) !== count(array_unique($names))) throw new InvalidArgumentException('Duplicate account or group names.');
        }
        if (!is_array($input['services'] ?? null) || !array_is_list($input['services'])) throw new InvalidArgumentException('Invalid service change list.');
        $paths = self::paths($snapshot['network'], $snapshot['correspondent']);
        $finalPorts = array_map(fn ($p) => self::at($p === ['Port'] ? $snapshot['correspondent'] : $snapshot['network'], $p), $paths);
        foreach ($input['services'] as $row) {
            if (!is_array($row) || !is_string($row['id'] ?? null) || !isset($paths[$row['id']]) || !is_int($row['port'] ?? null)
                || $row['port'] < 1 || $row['port'] > 65535 || isset($seen['services'][$row['id']])) throw new InvalidArgumentException('Invalid or unsupported service port.');
            $seen['services'][$row['id']] = true;
            $finalPorts[$row['id']] = $row['port'];
            $plan['services'][] = $row + ['kind' => 'service', 'key' => $row['id'], 'path' => $paths[$row['id']],
                'command' => $row['id'] === 'onvif' && $paths[$row['id']] === ['Port'] ? 'OEMcfg.Correspondent' : 'NetWork'];
        }
        if (count($finalPorts) !== count(array_unique($finalPorts))) throw new InvalidArgumentException('Services must use distinct listening ports.');
        usort($plan['services'], fn ($a, $b) => ($a['id'] === 'dvrip') <=> ($b['id'] === 'dvrip'));
        return $plan;
    }

    private function group(array $row): void
    {
        $groups = array_column($this->reader->getGroups(), null, 'Name');
        if ($row['delete']) {
            $this->reader->deleteGroup($row['originalName']);
            if (in_array($row['originalName'], array_column($this->reader->getGroups(), 'Name'), true)) throw new InvalidArgumentException('Group deletion did not take effect.');
            return;
        }
        $old = $groups[$row['originalName']] ?? null;
        $new = array_replace($old ?? [], ['Name' => $row['name'], 'Memo' => $row['memo'], 'AuthorityList' => $row['permissions']]);
        if ($old !== null && !array_key_exists('Memo', $old)) unset($new['Memo']);
        if ($old === null && !array_filter($groups, fn ($g) => array_key_exists('Memo', $g))) unset($new['Memo']);
        $members = array_filter($this->reader->getUsers(), fn ($u) => $old !== null && $u['Group'] === $old['Name']);
        try {
            $this->reader->writeGroup($row['originalName'], $new);
            if ($old !== null && $old['Name'] !== $new['Name']) foreach ($members as $u) {
                $u['Group'] = $new['Name']; $this->reader->writeUser($u['Name'], $u);
            }
            $saved = array_column($this->reader->getGroups(), null, 'Name')[$new['Name']] ?? [];
            foreach (['Name', 'Memo', 'AuthorityList'] as $key) {
                if (!array_key_exists($key, $new)) continue;
                $matches = $key === 'AuthorityList' ? self::samePermissions($saved[$key] ?? [], $new[$key]) : ($saved[$key] ?? null) === $new[$key];
                if (!$matches) throw new InvalidArgumentException('Group readback did not match.');
            }
        } catch (Throwable $e) {
            if ($old === null) {
                if (in_array($new['Name'], array_column($this->reader->getGroups(), 'Name'), true)) $this->reader->deleteGroup($new['Name']);
            }
            else {
                $current = array_column($this->reader->getGroups(), null, 'Name');
                $this->reader->writeGroup(isset($current[$new['Name']]) ? $new['Name'] : $old['Name'], $old);
                foreach ($members as $u) $this->reader->writeUser($u['Name'], $u);
            }
            throw $e;
        }
    }

    private function user(array $row, \Closure $persist): void
    {
        $users = array_column($this->reader->getUsers(), null, 'Name');
        $old = $users[$row['originalName']] ?? null;
        $origins = $this->camera['accessOrigins'] ?? [];
        if ($row['delete']) {
            try {
                $this->reader->deleteUser($row['originalName']);
                if (in_array($row['originalName'], array_column($this->reader->getUsers(), 'Name'), true)) throw new InvalidArgumentException('User deletion did not take effect.');
                unset($origins[$row['originalName']]); $persist(['origins' => $origins]); $this->camera['accessOrigins'] = $origins;
            } catch (Throwable $e) {
                if (!in_array($old['Name'], array_column($this->reader->getUsers(), 'Name'), true)) $this->reader->writeUser(null, $old);
                throw $e;
            }
            return;
        }
        $new = array_replace($old ?? [], ['Name' => $row['name'], 'Memo' => $row['memo'], 'Group' => $row['group'],
            'AuthorityList' => $row['permissions'], 'Sharable' => $row['multilogin'], 'Reserved' => $old['Reserved'] ?? false]);
        if ($old !== null && !array_key_exists('Memo', $old)) unset($new['Memo']);
        if ($old === null && !array_filter($users, fn ($u) => array_key_exists('Memo', $u))) unset($new['Memo']);
        try {
            $this->reader->writeUser($row['originalName'], $new, $old === null ? $row['password'] : null);
            if ($old !== null && $row['password'] !== null) {
                if (!is_string($old['Password'] ?? null) || strlen($old['Password']) !== 8) throw new InvalidArgumentException('Camera does not provide the password hash required to change this password.');
                $this->reader->changePasswordFromHash($new['Name'], $old['Password'], $row['password']);
            }
            $saved = array_column($this->reader->getUsers(), null, 'Name')[$new['Name']] ?? [];
            foreach (['Name', 'Memo', 'Group', 'AuthorityList', 'Sharable'] as $key) {
                if (!array_key_exists($key, $new)) continue;
                $matches = $key === 'AuthorityList' ? self::samePermissions($saved[$key] ?? [], $new[$key]) : ($saved[$key] ?? null) === $new[$key];
                if (!$matches) throw new InvalidArgumentException('User readback did not match.');
            }
            if ($row['password'] !== null && ($saved['Password'] ?? null) !== DvripClient::passwordHash($row['password'])) throw new InvalidArgumentException('Password hash readback did not match.');
            if ($old !== null && $row['password'] === null && ($saved['Password'] ?? null) !== $old['Password']) throw new InvalidArgumentException('An unchanged password was not preserved.');
            $updates = [];
            if ($new['Name'] === $this->camera['dvrip']['username'] && $row['password'] !== null) {
                $this->checkLogin($this->camera['dvrip']['port'], $row['password'], $new['Sharable']);
                $updates['password'] = $row['password'];
            }
            if ($old === null) $origins[$new['Name']] = ['api' => 'DVRIP', 'createdAt' => gmdate('c')];
            elseif ($old['Name'] !== $new['Name']) {
                if (isset($origins[$old['Name']])) $origins[$new['Name']] = $origins[$old['Name']];
                unset($origins[$old['Name']]);
            }
            $persist($updates + ['origins' => $origins]);
            $this->camera['accessOrigins'] = $origins;
            if (isset($updates['password'])) $this->camera['dvrip']['password'] = $updates['password'];
        } catch (Throwable $e) {
            if ($old === null) {
                if (in_array($new['Name'], array_column($this->reader->getUsers(), 'Name'), true)) $this->reader->deleteUser($new['Name']);
            }
            else {
                $current = array_column($this->reader->getUsers(), null, 'Name');
                if (isset($current[$new['Name']]['Password']) && $current[$new['Name']]['Password'] !== $old['Password']) {
                    $this->reader->setPasswordHashes($new['Name'], $current[$new['Name']]['Password'], $old['Password']);
                }
                $this->reader->writeUser(isset($current[$new['Name']]) ? $new['Name'] : $old['Name'], $old);
            }
            throw $e;
        }
    }

    private function checkLogin(int $port, string $password, bool $multilogin = true): void
    {
        if ($this->verify !== null) { ($this->verify)('dvrip', $port, $password); return; }
        if (!$multilogin) return; // Firmware may refuse a second concurrent login; native hash readback is used instead.
        $client = new DvripClient(new StreamDvripTransport($this->camera['host'], $port, 2));
        try { $client->login($this->camera['dvrip']['username'], $password); } finally { $client->close(); }
    }

    private function port(array $row, \Closure $persist): void
    {
        $old = $this->reader->getConfig($row['command']);
        $before = self::at($old, $row['path']);
        if ($before === $row['port']) return;
        // Refuse collisions with another currently open listener before touching configuration.
        if ($this->verify === null) {
            $socket = @stream_socket_client('tcp://' . $this->camera['host'] . ':' . $row['port'], $errno, $message, .3);
            if ($socket !== false) { fclose($socket); throw new InvalidArgumentException('The requested port already has a listening service.'); }
        }
        $new = $old; self::put($new, $row['path'], $row['port']);
        try {
            $this->reader->setConfig($row['command'], $new);
            if (self::at($this->reader->getConfig($row['command']), $row['path']) !== $row['port']) throw new InvalidArgumentException('Port configuration readback did not match.');
            if ($this->verify !== null) ($this->verify)($row['id'], $row['port'], $this->camera['dvrip']['password']);
            elseif ($row['id'] === 'dvrip') $this->checkLogin($row['port'], $this->camera['dvrip']['password']);
            else {
                $roles = CameraAccess::ports($this->camera, $new); $roles[$row['id']] = $row['port'];
                $observed = (new AccessProbe())->discover($this->camera['host'], [$row['port']], $roles);
                $okay = match ($row['id']) {
                    'http', 'https' => isset($observed['http'][$row['port']]),
                    'rtsp' => $observed['rtsp'] ?? false,
                    'onvif' => isset($observed['onvif']['url']) && (int) (parse_url($observed['onvif']['url'], PHP_URL_PORT) ?: 80) === $row['port'],
                };
                if (!$okay) throw new InvalidArgumentException('The listener did not move to the requested port; the firmware may require a restart.');
            }
            if ($row['id'] === 'dvrip') { $persist(['port' => $row['port']]); $this->camera['dvrip']['port'] = $row['port']; }
            else {
                $ports = $this->camera['access']['ports'] ?? []; $ports[$row['id']] = $row['port'];
                $persist(['ports' => $ports]); $this->camera['access']['ports'] = $ports;
            }
        } catch (Throwable $e) {
            $latest = $this->reader->getConfig($row['command']); self::put($latest, $row['path'], $before);
            $this->reader->setConfig($row['command'], $latest);
            if (self::at($this->reader->getConfig($row['command']), $row['path']) !== $before) throw new InvalidArgumentException('Port change failed and rollback could not be confirmed. Refresh to inspect camera state.');
            if ($this->verify !== null) ($this->verify)($row['id'], $before, $this->camera['dvrip']['password']);
            else {
                $host = str_contains($this->camera['host'], ':') ? '[' . trim($this->camera['host'], '[]') . ']' : $this->camera['host'];
                $socket = @stream_socket_client('tcp://' . $host . ':' . $before, $errno, $message, .5);
                if ($socket === false) throw new InvalidArgumentException('Original port setting restored, but its listener is not reachable.');
                fclose($socket);
            }
            throw new InvalidArgumentException($e->getMessage() . ' Original port restored.', 0, $e);
        }
    }
}
