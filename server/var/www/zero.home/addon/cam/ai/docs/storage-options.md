# Camera data storage options

Reviewed 2026-10-07. Read-only queries and FTP-test diagnostics only; no camera
setting, server service, Docker container, or storage path was changed. Do not
put FTP credentials here.

## Confirmed on Balcon and Door

- `SystemFunction.NetServerFunction.NetFTP = true` on both. Both expose
  `NetWork.NetFTP` with `Enable`, `Directory`, `MaxFileLen`, and `Server`
  (`Address`, `Anonymity`, `Name`, `Password`, `Port`, `UserName`). `Enable` was
  false on both when checked. The web app already has an FTP editor for this
  branch.
- Both advertise `OtherFunction.SupportFTPTest = true`. The FTP editor now
  has a test button that sends the saved configuration with DVRIP `OPFTPTest`
  (request 2008, response 2009), without saving form edits. A successful
  test would not prove that an alarm produced the desired file. On
  2026-10-07 Balcon returned code 109 to this test. A packet capture on the
  FTP server established the cause: the camera logged in successfully, then
  ran `PWD`, `CWD /`, `CWD ./Test`, `CWD Test`, and `MKD Test`. vsftpd replied
  `550 Create directory operation failed` to `MKD Test`; the camera then
  retried `CWD ./Test`, got `550`, and quit. The server config has
  `local_root=/`, so the FTP login root is the host's `/`; user `qqq` cannot
  create `/Test` there. The saved upload directory `/mnt/d500/cam` is writable
  by `qqq`, but **the camera's test does not use that directory**. This is not
  evidence of an FTP protocol-version incompatibility. The app now displays
  its test outcome beside the test button as well as in the top status line.
  No server or camera configuration was changed during diagnosis.
- Both have `Detect.MotionDetect.[0].EventHandler.FTPEnable`, currently false.
  This is an event action separate from the FTP server settings. As with
  Send Email, configuring a destination alone does not enable alarm uploads.
  We have not yet captured whether the native Human Detection FTP checkbox, if
  present, maps to this exact shared field.
- No SFTP, FTPS, SMB/CIFS, NFS, NAS/network-disk configuration or capability
  was found in the live `NetWork`, `Storage`, or `SystemFunction.NetServerFunction`
  structures. This is evidence against native support in these firmware builds,
  not proof that no undocumented command exists.
- Both `Storage` branches expose `Snapshot`, storage alarms, and
  `StoragePosition` template flags. Both report `SATA=true`, `SD=false` there;
  that configuration is not evidence of a physically attached disk. Door
  additionally advertises `OtherFunction.NOHDDRECORD=true`. Automatic editor
  discovery hides Door's local recording form, but now retains Snapshot
  recording because Door exposes `Storage.Snapshot` and sends event snapshots.
  Its saved mode was `ManualSnap`; Balcon's was `ClosedSnap` when read.

## Snapshot recording editor

The mode is a select with the three documented DVRIP wire values:
`ClosedSnap` (Off), `ManualSnap` (Manual snapshots), and `ConfigSnap`
(Configured / scheduled snapshots). The camera supplies its current value,
not a per-device list of supported options. See the vendor
[configuration-exchange specification](https://git.pblr-nyk.pro/mirror/python-dvr/raw/commit/81acf8845d7892be5b9ecb1ee92ebbe0a1c3ef5a/doc/%E9%85%8D%E7%BD%AE%E4%BA%A4%E6%8D%A2%E6%A0%BC%E5%BC%8FV2.0.pdf),
section 1.10. The editor only changes its allow-listed fields and preserves
schedules and masks.

Door rejects a direct read of `Storage.Snapshot` although the parent `Storage`
read succeeds. The editor now falls back to the parent branch for Door and,
on save, fresh-reads and merges into that complete parent before write and
read-back verification. The live Door form was verified read-only; a live
write through this fallback has not yet been tested. Balcon continues using
its direct `Storage.Snapshot` path. Do not assume Balcon's missing email image
is caused solely by `ClosedSnap`; the compared alert emails represented
different event phases.

## Practical routes

1. **Continue storing through MotionEye** (best for ordinary continuous/event
   recordings). Its Docker media directory can be bound to a Kubuntu host path
   or a host-mounted network volume. This is MotionEye storage, not a camera
   network-storage feature. Verify the existing container mount before changing
   it; mounting over existing container data can obscure the old files.
2. **Camera-origin FTP uploads** (for an independent event/snapshot path): run
   an FTP server such as `vsftpd` on a reachable Kubuntu/Linux host; configure
   the camera's `NetFTP` destination and the relevant event's FTP upload
   action (verify its wire mapping first); use a test and actual event to
   discover the file naming and whether this firmware uploads photos, clips,
   or both. No server or camera setup has
   been performed yet. Native SFTP is not currently evidenced.

KDE Plasma and Wayland affect the desktop session, not whether an unattended
FTP daemon can accept camera uploads. A Dolphin network-share mount is a
desktop/client mount; it does not turn the camera into an SMB/NFS client.

## Source references

- Live read-only DVRIP configuration and capability responses for Balcon and
  Door on 2026-10-07 (selected keys/flags above; secrets omitted).
- Xiongmai's [FTP network-settings explanation](https://www.xiongmaitech.com/en/index.php/service/problem_info/140/39)
  describes the remote directory as an upload destination for pictures/video;
  exact media behavior on these two cameras remains untested.
- [Ubuntu FTP server documentation](https://documentation.ubuntu.com/server/how-to/networking/ftp/index.html)
  describes `vsftpd` and distinguishes FTP from SFTP and FTPS.
- [MotionEye configuration](https://github.com/motioneye-project/motioneye/wiki/Configuration-File)
  documents the media path and optional SMB mounts; [Docker bind-mount
  documentation](https://docs.docker.com/engine/storage/bind-mounts/) explains
  how a host path becomes available in a container.
