<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/php/api.php';

use Cam\AccessManager;
use Cam\DvripReader;
use Cam\DvripClient;

final class AccessManagerFixture implements DvripReader
{
    public array $writes = [];
    public array $users;
    public array $groups;
    public array $authorities = ['Account', 'NetConfig', 'Monitor_01', 'Replay_01'];
    public array $correspondent = [];
    public array $network = ['NetCommon' => ['TCPPort' => 34567, 'HttpPort' => 80, 'SSLPort' => 8443], 'RTSP' => ['Server' => ['Port' => 554]]];
    public function __construct() {
        $this->groups = [
            'admin' => ['Name' => 'admin', 'Memo' => 'Admin', 'AuthorityList' => $this->getAuthorities()],
            'user' => ['Name' => 'user', 'Memo' => 'User', 'AuthorityList' => ['Monitor_01', 'Replay_01']],
        ];
        $this->users = ['admin' => ['Name' => 'admin', 'Group' => 'admin', 'Memo' => 'Admin', 'Reserved' => true,
            'Sharable' => true, 'AuthorityList' => $this->getAuthorities(), 'Password' => DvripClient::passwordHash('')]];
    }
    public function getUsers(): array { return array_values($this->users); }
    public function getGroups(): array { return array_values($this->groups); }
    public function getAuthorities(): array { return $this->authorities; }
    public function getConfig(string $command): mixed { return $command === 'OEMcfg.Correspondent' ? $this->correspondent : $this->network; }
    public function setConfig(string $command, mixed $value): bool { $this->writes[] = ['port', $value]; if ($command === 'OEMcfg.Correspondent') $this->correspondent = $value; else $this->network = $value; return true; }
    public function writeGroup(?string $original, array $group): void {
        $this->writes[] = ['group', $group['Name']]; if ($original !== null) unset($this->groups[$original]);
        $group['AuthorityList'] = array_reverse($group['AuthorityList']); $this->groups[$group['Name']] = $group;
    }
    public function deleteGroup(string $name): void { $this->writes[] = ['delete-group', $name]; unset($this->groups[$name]); }
    public function writeUser(?string $original, array $user, ?string $password = null): void {
        $this->writes[] = ['user', $user['Name']];
        $user['Password'] = $password !== null ? DvripClient::passwordHash($password) : ($this->users[$original]['Password'] ?? $user['Password'] ?? '');
        if ($original !== null) unset($this->users[$original]);
        $user['AuthorityList'] = array_reverse($user['AuthorityList']); $this->users[$user['Name']] = $user;
    }
    public function changePasswordFromHash(string $name, string $hash, string $password): void {
        if ($this->users[$name]['Password'] !== $hash) throw new RuntimeException('Old hash did not match');
        $this->writes[] = ['password', $name]; $this->users[$name]['Password'] = DvripClient::passwordHash($password);
    }
    public function setPasswordHashes(string $name, string $old, string $new): void { $this->users[$name]['Password'] = $new; }
    public function deleteUser(string $name): void { $this->writes[] = ['delete-user', $name]; unset($this->users[$name]); }
    public function getSystemInfo(): array { return []; }
    public function getInfo(string $command): mixed { return []; }
    public function getSystemFunction(): array { return []; }
    public function testMail(array $settings): bool { return true; }
    public function testFtp(array $settings): bool { return true; }
    public function reboot(): bool { return true; }
    public function close(): void {}
}

function accessManagerFixture(?Closure $verify = null): array {
    $reader = new AccessManagerFixture();
    $camera = ['host' => '192.0.2.1', 'dvrip' => ['username' => 'admin', 'password' => '', 'port' => 34567]];
    $manager = new AccessManager($reader, $camera, $verify ?? static fn () => null);
    $model = $manager->model(['points' => [['id' => 'dvrip', 'port' => 34567]], 'host' => $camera['host']]);
    return [$reader, $manager, $model];
}
function accessBatch(array $model): array { return ['revision' => $model['revision'], 'users' => [], 'groups' => [], 'services' => []]; }
function accessUserRow(array $u): array { return ['key' => 'users:' . $u['name'], 'originalName' => $u['name'], 'name' => $u['name'],
    'memo' => $u['memo'], 'group' => $u['group'], 'permissions' => $u['permissions'], 'multilogin' => $u['multilogin'], 'password' => null, 'setEmpty' => false, 'delete' => false]; }

test('access_manager_uses_native_users_and_authorities_without_exposing_password_hashes', function (): void {
    [$reader, $manager, $model] = accessManagerFixture();
    assertSameValue(1, count($model['users'])); assertSameValue('Unknown', $model['users'][0]['createdVia']);
    assertSameValue($reader->getAuthorities(), $model['permissions']);
    assertTrueValue(!str_contains(json_encode($model), $reader->users['admin']['Password']));
});
test('access_manager_preserves_native_permissions_omitted_from_advertised_catalogue', function (): void {
    [$reader, $manager] = accessManagerFixture();
    $reader->authorities = ['Account', 'NetConfig', 'Monitor_01'];
    $model = $manager->model(['points' => []]);
    assertSameValue(['Replay_01'], $model['retainedPermissions']);
    assertTrueValue(in_array('Replay_01', $model['permissions'], true));
    $input = accessBatch($model);
    $input['groups'][] = ['key' => 'groups:user', 'originalName' => 'user', 'name' => 'user', 'memo' => 'Changed', 'permissions' => ['Monitor_01', 'Replay_01'], 'delete' => false];
    assertTrueValue($manager->save($input, static fn () => null)['complete']);
    assertTrueValue(in_array('Replay_01', $reader->groups['user']['AuthorityList'], true));
});
test('access_manager_edits_captured_oem_onvif_port_without_changing_other_settings', function (): void {
    [$reader, $manager] = accessManagerFixture();
    $reader->correspondent = ['BrandInfo' => 'H264', 'FirmwareVersion' => 'V100', 'HardwareId' => '00001', 'Model' => 'NVT', 'Port' => 8899, 'RevSpace' => 'NVT', 'Scope' => null];
    $originalNetwork = $reader->network;
    $model = $manager->model(['points' => [['id' => 'onvif', 'port' => 8899]]]);
    assertSameValue(true, $model['points'][0]['portEditable']);
    $input = accessBatch($model); $input['services'][] = ['id' => 'onvif', 'port' => 9988];
    assertTrueValue($manager->save($input, static fn () => null)['complete']);
    assertSameValue(9988, $reader->correspondent['Port']);
    assertSameValue('NVT', $reader->correspondent['Model']);
    assertSameValue($originalNetwork, $reader->network);
});
test('access_batch_validates_all_rows_and_stale_revision_before_any_write', function (): void {
    [$reader, $manager, $model] = accessManagerFixture();
    $input = accessBatch($model);
    $input['groups'][] = ['key' => 'groups:user', 'originalName' => 'user', 'name' => 'user', 'memo' => 'Changed', 'permissions' => ['Monitor_01'], 'delete' => false];
    $u = accessUserRow($model['users'][0]); $u['permissions'][] = 'InventedPermission'; $input['users'][] = $u;
    try { $manager->save($input, static fn () => null); throw new RuntimeException('Expected validation rejection'); }
    catch (InvalidArgumentException) {}
    assertSameValue([], $reader->writes);
    $input = accessBatch($model); $input['revision'] = 'stale';
    try { $manager->save($input, static fn () => null); throw new RuntimeException('Expected stale rejection'); }
    catch (InvalidArgumentException) {}
    assertSameValue([], $reader->writes);
});
test('access_batch_creates_group_before_user_and_records_creation_api', function (): void {
    [$reader, $manager, $model] = accessManagerFixture(); $input = accessBatch($model); $persisted = [];
    $input['groups'][] = ['key' => 'new-group', 'originalName' => null, 'name' => 'watchers', 'memo' => 'Watching', 'permissions' => ['Monitor_01'], 'delete' => false];
    $input['users'][] = ['key' => 'new-user', 'originalName' => null, 'name' => 'viewer', 'memo' => 'Viewing', 'group' => 'watchers', 'permissions' => ['Monitor_01'], 'multilogin' => false, 'password' => 'secret', 'setEmpty' => false, 'delete' => false];
    $result = $manager->save($input, function ($state) use (&$persisted): void { $persisted[] = $state; });
    assertTrueValue($result['complete']); assertSameValue([['group', 'watchers'], ['user', 'viewer']], $reader->writes);
    assertSameValue('DVRIP', $persisted[0]['origins']['viewer']['api']);
    assertSameValue(false, $reader->users['viewer']['Sharable']);
});
test('access_batch_blank_password_preserves_hash_and_explicit_empty_changes_it', function (): void {
    [$reader, $manager, $model] = accessManagerFixture();
    $reader->users['viewer'] = ['Name' => 'viewer', 'Group' => 'user', 'Memo' => '', 'AuthorityList' => ['Monitor_01'], 'Sharable' => true, 'Reserved' => false, 'Password' => DvripClient::passwordHash('old')];
    $model = $manager->model(['points' => []]); $input = accessBatch($model); $row = accessUserRow($model['users'][1]);
    $row['memo'] = 'Changed'; $row['password'] = ''; $input['users'][] = $row;
    assertTrueValue($manager->save($input, static fn () => null)['complete']);
    assertSameValue(DvripClient::passwordHash('old'), $reader->users['viewer']['Password']);
    $model = $manager->model(['points' => []]); $input = accessBatch($model); $row = accessUserRow($model['users'][1]); $row['setEmpty'] = true; $input['users'][] = $row;
    assertTrueValue($manager->save($input, static fn () => null)['complete']);
    assertSameValue(DvripClient::passwordHash(''), $reader->users['viewer']['Password']);
});
test('access_batch_port_failure_restores_previous_configuration_and_reports_partial_success', function (): void {
    [$reader, $manager, $model] = accessManagerFixture(static function ($id, $port): void { if ($id === 'http' && $port === 18080) throw new RuntimeException('Listener unavailable'); });
    $input = accessBatch($model); $row = accessUserRow($model['users'][0]); $row['memo'] = 'Saved memo'; $input['users'][] = $row;
    $input['services'][] = ['id' => 'http', 'port' => 18080];
    $before = $reader->network; $result = $manager->save($input, static fn () => null);
    assertSameValue(false, $result['complete']); assertSameValue('Saved memo', $reader->users['admin']['Memo']);
    assertSameValue($before, $reader->network); assertSameValue(1, count($result['applied']));
    assertTrueValue(str_contains($result['message'], 'Original port restored'));
});
test('access_batch_removes_users_before_their_deleted_group_and_protects_reserved_account', function (): void {
    [$reader, $manager, $model] = accessManagerFixture();
    $reader->groups['temporary'] = ['Name' => 'temporary', 'Memo' => '', 'AuthorityList' => ['Monitor_01']];
    $reader->users['viewer'] = ['Name' => 'viewer', 'Group' => 'temporary', 'Memo' => '', 'AuthorityList' => ['Monitor_01'], 'Sharable' => true, 'Reserved' => false, 'Password' => DvripClient::passwordHash('old')];
    $model = $manager->model(['points' => []]); $input = accessBatch($model); $row = accessUserRow($model['users'][1]); $row['delete'] = true; $input['users'][] = $row;
    $input['groups'][] = ['key' => 'groups:temporary', 'originalName' => 'temporary', 'name' => 'temporary', 'memo' => '', 'permissions' => ['Monitor_01'], 'delete' => true];
    assertTrueValue($manager->save($input, static fn () => null)['complete']);
    assertSameValue([['delete-user', 'viewer'], ['delete-group', 'temporary']], $reader->writes);
    $model = $manager->model(['points' => []]); $input = accessBatch($model); $row = accessUserRow($model['users'][0]); $row['delete'] = true; $input['users'][] = $row;
    try { $manager->save($input, static fn () => null); throw new RuntimeException('Expected reserved rejection'); } catch (InvalidArgumentException) {}
    assertTrueValue(isset($reader->users['admin']));
});
