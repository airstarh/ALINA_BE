# Second network-editor batch

The second five dedicated forms are:

- Multicast → `NetWork.Multicast`
- PPPoE → `NetWork.NetPPPoE`
- RTSP server → `NetWork.RTSP` (server sub-branch only)
- Serial network bridge → `NetWork.TranCommData`
- Vendor cloud / NAT → `NetWork.Nat`

They use the same allow-listed, typed, read/merge/write/read-back mechanism as
the first batch. Passwords remain server-side and blank password inputs preserve
the current camera value. Unowned values—including RTSP client configuration,
PPPoE assigned address, NAT DNS fields, and nested server metadata—are preserved.

Live read-only requests confirmed all five structures on Balcon. No camera write
was performed during verification. Connectivity-sensitive forms display a
warning before their controls; in particular, changing RTSP may interrupt
MotionEye.
