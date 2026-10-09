# Camera access management

Updated 2026-10-09. ACCESS → All points manages accounts providing access to
the selected camera. Only inbound listeners are shown. Outbound FTP, SMTP,
NTP and vendor cloud destinations are excluded. URLs appear immediately under
each service heading; addresses and immutable account names are plain text,
not pretend editable inputs. Password inputs are visible text.

## Current page design (2026-10-09)

- One page contains collapsible USERS, GROUPS and SERVICES tables, with 150px
  spacing between sections. USERS starts open. One **Save changes** button at
  the bottom submits only changed rows; additions and deletions are staged.
- USERS is the single native camera account list, not duplicate DVRIP/ONVIF
  tables. Name, Group, Multilogin (`Sharable`), supported Memo and individual
  permissions are editable. New users initially inherit the selected group's
  permissions. Existing narrower permissions are retained.
- Password placeholder is `set new password`. An untouched/blank input preserves
  the existing password. **Set empty** explicitly clears it and disables the
  input. Stored Sofia hashes remain server-side and are never shown as passwords.
- Created via records the creation API only for users created by this app.
  Historical accounts show **Unknown**: the camera does not return provenance.
  This column does not describe which protocols an account can access.
- GROUPS edits name, supported Memo and permissions. Permission multiselects
  display five rows. The list combines the advertised authority catalogue with
  permissions already present on native users/groups. Door omits `Replay_01`
  from its advertised catalogue but includes it in the `user` group; it is retained.
- Reserved and connected accounts cannot be renamed/deleted. The connected
  account's group and Account/NetConfig permissions are protected against losing
  management access; this is an app safeguard, not an invented firmware flag.
- SERVICES shows inbound endpoints, full URLs with a clipboard button, and
  text port inputs only for exposed write paths. Snapshot HTTP shares HTTP's
  port. ONVIF8899 is detected but neither camera exposes a verified port-write
  setting, so its port remains plain text with an explanation.
- Before writing, the complete batch is validated against fresh account/group
  state and its revision. Saves are sequential, not a camera-side transaction.
  On partial failure, completed rows are reported and pending edits remain in
  the form. Failed listener verification restores the original port setting.

Native HTTP18080, RTSP18554 and DVRIP34569 test changes were accepted/stored
on Balcon but did not move the active listeners without a restart. Originals
were restored and checked. The app does not reboot automatically or report
stored-only changes as successful port changes.

Live batch tests covered temporary group/user creation, Memo, Multilogin,
password change with fresh login, group/user rename and member migration,
deletion, and failed RTSP-port rollback. Temporary accounts/groups were removed;
original real credentials and ports were retained. No motor movement occurred.

## Verified listeners

| Camera | Verified camera access |
| --- | --- |
| Door, 192.168.1.115 | DVRIP TCP34567, HTTP TCP80, HTTP JPEG snapshots, RTSP TCP554, ONVIF TCP8899 |
| Balcon, 192.168.1.121 | DVRIP TCP34567, HTTP TCP80, HTTP JPEG snapshots, RTSP TCP554, ONVIF TCP8899 |

Door also accepts TCP8000 connections; its protocol is unidentified. It has no
credential editor until its account API is identified. Configured SSL8443 is
not accepting connections on either camera and is excluded from the form.
WS-Discovery UDP3702 answers discovery, and network settings report vendor
UDP34568. These have no verified user/account access API and are not presented
as credential-managed services. Discovery is bounded, not an exhaustive scan.

## Account changes that actually work

- DVRIP Users1472 and Groups1474 enumerate camera accounts/groups. ModifyPassword1488
  changes the real stored account password, using Sofia hashes. A separate fresh
  login verifies a change to the app's account before success is reported.
- Adding a camera user with User1482 produces an account that immediately works
  for RTSP on **both** cameras. DelUser1486 removes the temporary test account.
  Thus these RTSP usernames belong to the camera account database managed via
  DVRIP. The shared USERS table provides those account controls rather than
  duplicating them on RTSP cards. It does not write an unrelated RTSP template.
- Existing camera accounts can change passwords using the old Sofia hash returned
  to the backend by Users1472. No old plaintext input is required. The app does
  not claim it can recover plaintext passwords. New users inherit a group
  actually returned by the camera.
- ONVIF GetUsers provides a different **view of the same camera accounts on
  Balcon**, not a proven separate account database. A direct cross-check created
  a temporary ONVIF user and found that exact username in DVRIP Users1472;
  deleting it through ONVIF also removed it from DVRIP. Earlier RTSP rejection
  was wrongly treated as evidence of separate stores and is not such evidence.
  CreateUsers and SetUser successfully created and updated a nonreserved test
  account. Passwords are not disclosed by GetUsers.
- ONVIF roles map to native per-user properties on Balcon: a temporary User
  account had Group=admin, Memo="user's account", AuthorityList=[Monitor_01];
  an Operator had Group=admin, Memo="Operator 's account", and authorities
  GeneralConfig, EncodeConfig, NetConfig, VideoConfig. Both were nonreserved
  and sharable. Thus native group membership alone does not define effective
  user permissions, and ONVIF roles are not independent CMS groups. Both test
  accounts were removed and native-list cleanup verified. Door's GetUsers
  currently returns an empty ONVIF view; do not assume the same projection.
- Balcon SetUser for reserved `admin` returns `Can not update reserved user`.
  This does not prohibit native ModifyPassword for admin. The unified table
  uses native account operations, not ONVIF SetUser. Existing role edits were
  accepted by SetUser but ignored by the firmware; native permissions are the
  editable representation instead of a misleading ONVIF-role selector.

## Critical firmware findings

The original assertion that RTSP's password was proven to be `admin` was wrong.
The user's mpv test proved that this credential string worked, not that it was
the stored password. Further authenticated RTSP DESCRIBE tests returned200 for
arbitrary passwords with a valid username; unknown usernames returned401.
Even after changing the actual Balcon camera password and verifying a fresh
DVRIP login, RTSP still accepted both the new and a wrong password. The form
states this limitation: managing an actual account does not fix the camera's
defective RTSP password enforcement.

`NetWork.RTSP.Server.UserName/Password` edits were stored and read back, but
the temporary account in these fields failed RTSP with401 while the existing
account still worked. These template fields are not presented as working
credential controls on this page. Originals were restored and checked.

Shared RTSP account routing is recorded per camera in private `config.php`
as `access.account_services => ['rtsp']`, based on the creation tests above.
It is not assumed for a newly added camera. Service-specific investigation
notes use `access.notes`. Configured plaintext credentials from another service
are never inferred as that service's actual stored password.

## Persistence and validation

- Access saves are POST-only, serialized by the existing per-camera request lock.
- Stale account/group/port revisions are rejected before any camera write.
- After changing the app's primary account, its connection credentials are
  atomically persisted in a private `.access-<camera>-<config-hash>.php` file
  beside `config.php`. Config loads this override on the next request. The file
  is ignored by Git, created with private permissions, and returns no content
  if executed over HTTP. No static config source or comments are rewritten.
- If verification or persistence fails, the still-authenticated original
  camera session attempts to restore the prior password.
- Private state also stores verified listener overrides and app-recorded account
  creation provenance. A successful Save reloads the model; a partial Save
  rebases completed rows while retaining pending edits. Outbound services and
  unrelated camera settings are never written by access management.

Live web API tests changed Balcon's primary password, verified a subsequent
request used the saved credentials, then restored the original through the
same API. ONVIF create/update actions were also tested through the web API.
All temporary users were removed and original passwords restored. No PTZ
movement occurred.

## Protocol references

- [OpenIPC python-dvr implementation](https://github.com/OpenIPC/python-dvr/blob/master/dvrip.py): Users, Groups, User, ModifyPassword and DelUser messages.
- [ONVIF Device Management WSDL](https://www.onvif.org/ver10/device/wsdl/devicemgmt.wsdl): GetUsers, CreateUsers and SetUser operations.
- [ONVIF Media WSDL](https://www.onvif.org/ver10/media/wsdl/media.wsdl): advertised profiles and stream URIs.

These references identify wire operations. Firmware behavior and limitations
above come from live tests of these specific cameras, not the specifications.
