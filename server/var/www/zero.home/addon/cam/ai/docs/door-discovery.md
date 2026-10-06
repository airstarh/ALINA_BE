# Door camera discovery

Last updated: 2026-10-06 (Europe/Moscow)

Door is a simpler Xiongmai/Sofia DVRIP camera using the same TCP 34567
management protocol and legacy ActiveX family as Balcon. Its reported hardware
model is `IPC_GK7605V100_83K50W_S38`, firmware build date 2021-09-15.

## Automatic editor discovery

The application no longer assumes that every configured camera supports all
68 schema-driven editors. When a camera is selected, the `editors` API action:

1. reads `SystemFunction` and `SystemInfo`;
2. reads every relevant top-level configuration family once;
3. validates each editor's required branch and field paths without writing;
4. omits editors whose branch is missing, rejected, or structurally
   incompatible;
5. applies hardware capability checks for dummy branches such as Wi-Fi.

Transport failures are not interpreted as missing features; they still fail
the connection so that a temporary network problem cannot silently remove UI.

Door advertises no `NetWifi` capability even though its generic `NetWork`
object contains an inert disabled Wi-Fi template. Consequently the Wi-Fi and
Wi-Fi signal editor pages are hidden. This is capability-driven rather than a
camera-name or model-specific exception.

The same discovery removed editor branches that Door rejects or cannot use,
including tracking, PTZ resume, network watchdog, vehicle detection,
recording/snapshot storage, local alarm input, and alarm output. Door exposes
57 applicable schema-driven editors. Balcon exposes all 68.

Door's Wired network editor was live-read successfully. It reports DHCP off on
`eth0`, static address `192.168.1.115`, subnet `255.255.255.0`, and gateway
`192.168.1.254`. No network value was changed during verification.

Live timing after grouping probes by top-level family was approximately 2.43
seconds for Door and 2.09 seconds for Balcon. The initial one-request-per-form
prototype took roughly 13–14 seconds and was replaced.

## Reboot verification

Door accepts the standard DVRIP message 1450 `OPMachine.Action = Reboot` used
by Balcon. A live production-API test returned success; immediately afterward
Door became temporarily unreachable and then returned. No alternate reboot
command or per-camera reboot implementation is required.

Door's `DeviceRunTime` field did not provide a reliable second-by-second uptime
counter, so temporary network disappearance and recovery were used as the
operational reboot evidence.
