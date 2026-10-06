# Ten-page network-editor batch

This batch adds dedicated forms for:

- DHCP interfaces → `NetWork.NetDHCP`
- Wi-Fi → `NetWork.Wifi`
- Wi-Fi signal thresholds → `NetWork.WifiStrengthLevel`
- Vendor connection controls → `NetWork.DisableXmServerConn`
- Online upgrade policy → `NetWork.OnlineUpgrade`
- DDNS provider 1 through 5 → five views over `NetWork.NetDDNS`

Each DDNS page owns only one provider entry. Saving still reads and writes the
complete latest `NetWork.NetDDNS` array, so the other four providers and their
metadata are preserved. This behavior was later changed: Wi-Fi and DDNS
password/key values are visible in dedicated forms, and blank fields clear the
camera values.

The UI now supports multiple boolean switches and integer-backed firmware
switches. Connectivity, credential, vendor-service, and upgrade forms display
warnings. Live read-only requests confirmed every schema against Balcon; no
camera values were written during verification.
