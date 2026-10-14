<?php

declare(strict_types=1);

namespace Cam;

use InvalidArgumentException;

final class NetworkServiceEditor
{
    private const DEFINITIONS = [
        'ftp' => [
            'label' => 'FTP', 'branch' => 'NetWork.NetFTP',
            'fields' => [
                'enable' => ['label' => 'FTP uploads', 'type' => 'boolean', 'path' => ['Enable']],
                'server' => ['label' => 'Server', 'type' => 'string', 'path' => ['Server', 'Name']],
                'port' => ['label' => 'Port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Server', 'Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => ['Server', 'UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => ['Server', 'Password']],
                'directory' => ['label' => 'Directory', 'type' => 'string', 'path' => ['Directory']],
                'maxFileLen' => ['label' => 'Maximum file length', 'type' => 'integer', 'min' => 1, 'max' => 2147483647, 'path' => ['MaxFileLen']],
            ],
        ],
        'ntp' => [
            'label' => 'NTP / time sync', 'branch' => 'NetWork.NetNTP',
            'fields' => [
                'enable' => ['label' => 'Time synchronization', 'type' => 'boolean', 'path' => ['Enable']],
                'server' => ['label' => 'NTP server', 'type' => 'string', 'path' => ['Server', 'Name']],
                'port' => ['label' => 'Port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Server', 'Port']],
                'timeZone' => ['label' => 'Camera timezone code', 'type' => 'integer', 'min' => 0, 'max' => 26, 'path' => ['TimeZone']],
                'updatePeriod' => ['label' => 'Update period (minutes)', 'type' => 'integer', 'min' => 1, 'max' => 1440, 'path' => ['UpdatePeriod']],
            ],
        ],
        'upnp' => [
            'label' => 'UPnP', 'branch' => 'NetWork.Upnp',
            'fields' => [
                'enable' => ['label' => 'UPnP port mapping', 'type' => 'boolean', 'path' => ['Enable']],
                'httpPort' => ['label' => 'HTTP port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['HTTPPort']],
                'mediaPort' => ['label' => 'Media port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['MediaPort']],
                'mobilePort' => ['label' => 'Mobile port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['MobilePort']],
            ],
        ],
        'pms' => [
            'label' => 'PMS push service', 'branch' => 'NetWork.PMS',
            'fields' => [
                'enable' => ['label' => 'PMS push service', 'type' => 'boolean', 'path' => ['Enable']],
                'boxId' => ['label' => 'Box ID', 'type' => 'string', 'path' => ['BoxID']],
                'server' => ['label' => 'Server', 'type' => 'string', 'path' => ['ServName']],
                'port' => ['label' => 'Port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Port']],
                'pushInterval' => ['label' => 'Push interval (seconds)', 'type' => 'integer', 'min' => 1, 'max' => 86400, 'path' => ['PushInterval']],
            ],
        ],
        'das' => [
            'label' => 'DAS service', 'branch' => 'NetWork.DAS',
            'fields' => [
                'enable' => ['label' => 'DAS service', 'type' => 'boolean', 'path' => ['Enable']],
                'deviceId' => ['label' => 'Device ID', 'type' => 'string', 'path' => ['DeviceID']],
                'server' => ['label' => 'Server address', 'type' => 'string', 'path' => ['ServerAddr']],
                'port' => ['label' => 'Port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => ['UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => ['Password']],
            ],
        ],
        'multicast' => [
            'label' => 'Multicast', 'branch' => 'NetWork.Multicast',
            'fields' => [
                'enable' => ['label' => 'Multicast streaming', 'type' => 'boolean', 'path' => ['Enable']],
                'address' => ['label' => 'Multicast address', 'type' => 'string', 'path' => ['Addr']],
                'port' => ['label' => 'Port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Port']],
            ],
        ],
        'pppoe' => [
            'label' => 'PPPoE', 'branch' => 'NetWork.NetPPPoE',
            'warning' => 'Enabling PPPoE can change how the camera reaches the network.',
            'fields' => [
                'enable' => ['label' => 'PPPoE connection', 'type' => 'boolean', 'path' => ['Enable']],
                'service' => ['label' => 'Service name', 'type' => 'string', 'path' => ['Server', 'Name']],
                'port' => ['label' => 'Service port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Server', 'Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => ['Server', 'UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => ['Server', 'Password']],
            ],
        ],
        'rtsp' => [
            'label' => 'RTSP server', 'branch' => 'NetWork.RTSP',
            'warning' => 'Changing or disabling RTSP can interrupt MotionEye and other video clients.',
            'fields' => [
                'enable' => ['label' => 'RTSP server', 'type' => 'boolean', 'path' => ['IsServer']],
                'server' => ['label' => 'Server name', 'type' => 'string', 'path' => ['Server', 'Name']],
                'port' => ['label' => 'RTSP port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Server', 'Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => ['Server', 'UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => ['Server', 'Password']],
            ],
        ],
        'serial_bridge' => [
            'label' => 'Serial network bridge', 'branch' => 'NetWork.TranCommData',
            'warning' => 'Enable this only when a serial-to-network integration is intentionally configured.',
            'fields' => [
                'enable' => ['label' => 'Serial network bridge', 'type' => 'boolean', 'path' => ['bEnable']],
                'server' => ['label' => 'Server address', 'type' => 'string', 'path' => ['ServerAddr']],
                'port' => ['label' => 'Server port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['ServerPort']],
                'serialType' => ['label' => 'Serial type code', 'type' => 'integer', 'min' => 0, 'max' => 16, 'path' => ['SerialType']],
                'serverType' => ['label' => 'Server type code', 'type' => 'integer', 'min' => 0, 'max' => 16, 'path' => ['ServerType']],
            ],
        ],
        'nat' => [
            'label' => 'Vendor cloud / NAT', 'branch' => 'NetWork.Nat',
            'warning' => 'Enabling this allows the camera to contact its configured vendor cloud service.',
            'fields' => [
                'enable' => ['label' => 'Vendor cloud / NAT', 'type' => 'boolean', 'path' => ['NatEnable']],
                'server' => ['label' => 'Service address', 'type' => 'string', 'path' => ['Addr']],
                'port' => ['label' => 'Service port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['Port']],
                'mtu' => ['label' => 'XMEye MTU', 'type' => 'integer', 'min' => 576, 'max' => 1500, 'path' => ['XMeyeMTU']],
            ],
        ],
        'wired_network' => [
            'label' => 'Wired network', 'branch' => 'NetWork.NetCommon',
            'warning' => 'Changing the wired IP or DHCP setting can make the camera unreachable. Confirm the new address before saving.',
            'fields' => [
                'hostIp' => ['label' => 'IP address', 'type' => 'ipv4-little-endian', 'path' => ['HostIP'], 'disableWhen' => 'dhcp'],
                'subnet' => ['label' => 'Subnet mask', 'type' => 'ipv4-little-endian', 'path' => ['Submask'], 'disableWhen' => 'dhcp'],
                'gateway' => ['label' => 'Gateway', 'type' => 'ipv4-little-endian', 'path' => ['GateWay'], 'disableWhen' => 'dhcp'],
                'hostName' => ['label' => 'Hostname', 'type' => 'string', 'path' => ['HostName']],
                'mac' => ['label' => 'MAC address', 'type' => 'string', 'path' => ['MAC'], 'disabled' => true, 'writable' => false],
            ],
        ],
        'dhcp' => [
            'label' => 'DHCP interfaces', 'branch' => 'NetWork.NetDHCP',
            'warning' => 'Disabling DHCP on eth0 can make the camera unreachable if no usable static address is configured.',
            'fields' => [
                'eth0' => ['label' => 'DHCP on eth0', 'type' => 'boolean', 'path' => [0, 'Enable']],
                'eth1' => ['label' => 'DHCP on eth1', 'type' => 'boolean', 'path' => [1, 'Enable']],
                'eth2' => ['label' => 'DHCP on eth2', 'type' => 'boolean', 'path' => [2, 'Enable']],
                'eth3' => ['label' => 'DHCP on eth3', 'type' => 'boolean', 'path' => [3, 'Enable']],
            ],
        ],
        'wifi' => [
            'label' => 'Wi-Fi', 'branch' => 'NetWork.Wifi',
            'warning' => 'Changing Wi-Fi settings can interrupt the wireless connection. AP search runs from the camera. Wi-Fi DHCP is interface eth1 on the separate DHCP interfaces page.',
            'fields' => [
                'enable' => ['label' => 'Wi-Fi', 'type' => 'boolean', 'path' => ['Enable']],
                'ssid' => ['label' => 'SSID', 'type' => 'string', 'path' => ['SSID']],
                'auth' => [
                    'label' => 'Authentication mode', 'type' => 'byte-string', 'path' => ['Auth'],
                    'options' => ['OPEN', 'SHARED', 'WEPAUTO', 'WPAPSK', 'WPA2PSK', 'WPANONE', 'WPA', 'WPA2'],
                    'optionLabels' => [
                        'OPEN' => 'Open network (no authentication)',
                        'SHARED' => 'WEP shared key',
                        'WEPAUTO' => 'WEP automatic authentication',
                        'WPAPSK' => 'WPA Personal (password)',
                        'WPA2PSK' => 'WPA2 Personal (password)',
                        'WPANONE' => 'WPA without authentication',
                        'WPA' => 'WPA Enterprise',
                        'WPA2' => 'WPA2 Enterprise',
                    ],
                    'unknownLabel' => 'Unknown authentication value reported by camera',
                    'byteMap' => [1 => 'WPA2PSK'],
                    'readMap' => ['WPAPSK' => 'WPA2PSK'],
                    'wireMap' => ['WPA2PSK' => 'WPAPSK'],
                ],
                'encryption' => [
                    'label' => 'Encryption type', 'type' => 'byte-string', 'path' => ['EncrypType'],
                    'options' => ['NONE', 'WEP', 'TKIP', 'AES'],
                    'optionLabels' => [
                        'NONE' => 'No encryption',
                        'WEP' => 'WEP encryption',
                        'TKIP' => 'TKIP encryption',
                        'AES' => 'AES encryption',
                    ],
                    'unknownLabel' => 'Unknown encryption value reported by camera',
                    'byteMap' => [5 => 'AES', 6 => 'AES'],
                    'readMap' => ['TKIP' => 'AES'],
                    'wireMap' => ['AES' => 'TKIP'],
                ],
                'key' => ['label' => 'Wi-Fi key', 'type' => 'password', 'path' => ['Keys']],
                'channel' => [
                    'label' => 'Channel', 'type' => 'integer', 'min' => 0, 'max' => 13, 'path' => ['Channel'],
                    'options' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13'],
                    'optionLabels' => ['0' => 'Automatic'],
                ],
                'networkType' => [
                    'label' => 'Network type', 'type' => 'string', 'path' => ['NetType'],
                    'options' => ['Infra', 'Adhoc'],
                    'optionLabels' => ['Infra' => 'Infrastructure', 'Adhoc' => 'Ad-hoc'],
                ],
                'keyType' => [
                    'label' => 'Password format', 'type' => 'integer', 'min' => 0, 'max' => 1, 'path' => ['KeyType'],
                    'options' => ['0', '1'],
                    'optionLabels' => ['0' => 'Hexadecimal key', '1' => 'Text password (ASCII)'],
                    'disabled' => true,
                ],
                'hostIp' => ['label' => 'IP address', 'type' => 'ipv4-little-endian', 'path' => ['HostIP'], 'disableWhen' => 'dhcp'],
                'subnet' => ['label' => 'Subnet mask', 'type' => 'ipv4-little-endian', 'path' => ['Submask'], 'disableWhen' => 'dhcp'],
                'gateway' => ['label' => 'Gateway', 'type' => 'ipv4-little-endian', 'path' => ['GateWay'], 'disableWhen' => 'dhcp'],
            ],
        ],
        'wifi_signal' => [
            'label' => 'Wi-Fi signal thresholds', 'branch' => 'NetWork.WifiStrengthLevel',
            'fields' => [
                'excellent' => ['label' => 'Excellent threshold', 'type' => 'integer', 'min' => -120, 'max' => 0, 'path' => ['Excellent']],
                'veryGood' => ['label' => 'Very good threshold', 'type' => 'integer', 'min' => -120, 'max' => 0, 'path' => ['VeryGood']],
                'good' => ['label' => 'Good threshold', 'type' => 'integer', 'min' => -120, 'max' => 0, 'path' => ['Good']],
                'low' => ['label' => 'Low threshold', 'type' => 'integer', 'min' => -120, 'max' => 0, 'path' => ['Low']],
                'veryLow' => ['label' => 'Very low threshold', 'type' => 'integer', 'min' => -120, 'max' => 0, 'path' => ['VeryLow']],
                'noSignal' => ['label' => 'No signal threshold', 'type' => 'integer', 'min' => -120, 'max' => 0, 'path' => ['NoSingnal']],
            ],
        ],
        'vendor_connections' => [
            'label' => 'Vendor connection controls', 'branch' => 'NetWork.DisableXmServerConn',
            'warning' => 'These switches disable vendor services. Changing them can affect cloud applications and remote access.',
            'fields' => [
                'disableAll' => ['label' => 'Disable all vendor connections', 'type' => 'boolean', 'path' => ['DisableAll']],
                'disableDss' => ['label' => 'Disable DSS', 'type' => 'boolean', 'path' => ['DisableDSS']],
                'disableNat' => ['label' => 'Disable NAT service', 'type' => 'boolean', 'path' => ['DisableNAT']],
                'disableRps' => ['label' => 'Disable RPS', 'type' => 'boolean', 'path' => ['DisableRPS']],
            ],
        ],
        'online_upgrade' => [
            'label' => 'Online upgrade policy', 'branch' => 'NetWork.OnlineUpgrade',
            'warning' => 'This page controls automatic firmware checks and upgrades. Incorrect settings can trigger an unwanted firmware change.',
            'fields' => [
                'enable' => ['label' => 'Online upgrade service', 'type' => 'boolean-integer', 'path' => ['Enable']],
                'autoCheck' => ['label' => 'Automatically check', 'type' => 'boolean-integer', 'path' => ['AutoCheck']],
                'autoUpgradeImportant' => ['label' => 'Automatically install important upgrades', 'type' => 'boolean-integer', 'path' => ['AutoUpgradeImp']],
                'importantOnly' => ['label' => 'Important versions only', 'type' => 'boolean-integer', 'path' => ['JustImpVersion']],
                'ignoreAllVersion' => ['label' => 'Ignore all version checks', 'type' => 'boolean-integer', 'path' => ['IgnoreAllVersion']],
                'ignoreCurrentVersion' => ['label' => 'Ignore current version', 'type' => 'boolean-integer', 'path' => ['IgnoreCurVersion']],
                'ignoreCurrentTime' => ['label' => 'Ignore current time restriction', 'type' => 'boolean-integer', 'path' => ['IgnoreCurTime']],
                'server' => ['label' => 'Upgrade server', 'type' => 'string', 'path' => ['ServerAddr']],
                'port' => ['label' => 'Upgrade server port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => ['ServerPort']],
            ],
        ],
        'ddns_1' => [
            'label' => 'DDNS provider 1', 'branch' => 'NetWork.NetDDNS',
            'warning' => 'Enabling DDNS sends the camera hostname and credentials to the configured provider.',
            'fields' => [
                'enable' => ['label' => 'DDNS provider 1', 'type' => 'boolean', 'path' => [0, 'Enable']],
                'hostName' => ['label' => 'Public hostname', 'type' => 'string', 'path' => [0, 'HostName']],
                'serviceKey' => ['label' => 'DDNS key', 'type' => 'password', 'path' => [0, 'DDNSKey']],
                'server' => ['label' => 'Provider server', 'type' => 'string', 'path' => [0, 'Server', 'Name']],
                'port' => ['label' => 'Provider port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => [0, 'Server', 'Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => [0, 'Server', 'UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => [0, 'Server', 'Password']],
            ],
        ],
        'ddns_2' => [
            'label' => 'DDNS provider 2', 'branch' => 'NetWork.NetDDNS',
            'warning' => 'Enabling DDNS sends the camera hostname and credentials to the configured provider.',
            'fields' => [
                'enable' => ['label' => 'DDNS provider 2', 'type' => 'boolean', 'path' => [1, 'Enable']],
                'hostName' => ['label' => 'Public hostname', 'type' => 'string', 'path' => [1, 'HostName']],
                'serviceKey' => ['label' => 'DDNS key', 'type' => 'password', 'path' => [1, 'DDNSKey']],
                'server' => ['label' => 'Provider server', 'type' => 'string', 'path' => [1, 'Server', 'Name']],
                'port' => ['label' => 'Provider port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => [1, 'Server', 'Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => [1, 'Server', 'UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => [1, 'Server', 'Password']],
            ],
        ],
        'ddns_3' => [
            'label' => 'DDNS provider 3', 'branch' => 'NetWork.NetDDNS',
            'warning' => 'Enabling DDNS sends the camera hostname and credentials to the configured provider.',
            'fields' => [
                'enable' => ['label' => 'DDNS provider 3', 'type' => 'boolean', 'path' => [2, 'Enable']],
                'hostName' => ['label' => 'Public hostname', 'type' => 'string', 'path' => [2, 'HostName']],
                'serviceKey' => ['label' => 'DDNS key', 'type' => 'password', 'path' => [2, 'DDNSKey']],
                'server' => ['label' => 'Provider server', 'type' => 'string', 'path' => [2, 'Server', 'Name']],
                'port' => ['label' => 'Provider port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => [2, 'Server', 'Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => [2, 'Server', 'UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => [2, 'Server', 'Password']],
            ],
        ],
        'ddns_4' => [
            'label' => 'DDNS provider 4', 'branch' => 'NetWork.NetDDNS',
            'warning' => 'Enabling DDNS sends the camera hostname and credentials to the configured provider.',
            'fields' => [
                'enable' => ['label' => 'DDNS provider 4', 'type' => 'boolean', 'path' => [3, 'Enable']],
                'hostName' => ['label' => 'Public hostname', 'type' => 'string', 'path' => [3, 'HostName']],
                'serviceKey' => ['label' => 'DDNS key', 'type' => 'password', 'path' => [3, 'DDNSKey']],
                'server' => ['label' => 'Provider server', 'type' => 'string', 'path' => [3, 'Server', 'Name']],
                'port' => ['label' => 'Provider port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => [3, 'Server', 'Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => [3, 'Server', 'UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => [3, 'Server', 'Password']],
            ],
        ],
        'ddns_5' => [
            'label' => 'DDNS provider 5', 'branch' => 'NetWork.NetDDNS',
            'warning' => 'Enabling DDNS sends the camera hostname and credentials to the configured provider.',
            'fields' => [
                'enable' => ['label' => 'DDNS provider 5', 'type' => 'boolean', 'path' => [4, 'Enable']],
                'hostName' => ['label' => 'Public hostname', 'type' => 'string', 'path' => [4, 'HostName']],
                'serviceKey' => ['label' => 'DDNS key', 'type' => 'password', 'path' => [4, 'DDNSKey']],
                'server' => ['label' => 'Provider server', 'type' => 'string', 'path' => [4, 'Server', 'Name']],
                'port' => ['label' => 'Provider port', 'type' => 'integer', 'min' => 1, 'max' => 65535, 'path' => [4, 'Server', 'Port']],
                'username' => ['label' => 'Username', 'type' => 'string', 'path' => [4, 'Server', 'UserName']],
                'password' => ['label' => 'Password', 'type' => 'password', 'path' => [4, 'Server', 'Password']],
            ],
        ],
        'general_preferences' => [
            'label' => 'General preferences', 'branch' => 'General.General',
            'fields' => [
                'machineName' => ['label' => 'Machine name', 'type' => 'string', 'path' => ['MachineName']],
                'fontSize' => ['label' => 'Interface font size', 'type' => 'integer', 'min' => 8, 'max' => 72, 'path' => ['FontSize']],
                'autoLogout' => ['label' => 'Auto logout (minutes)', 'type' => 'integer', 'min' => 0, 'max' => 1440, 'path' => ['AutoLogout']],
                'screenSaveTime' => ['label' => 'Screen saver delay', 'type' => 'integer', 'min' => 0, 'max' => 1440, 'path' => ['ScreenSaveTime']],
                'screenAutoShutdown' => ['label' => 'Screen shutdown delay', 'type' => 'integer', 'min' => 0, 'max' => 1440, 'path' => ['ScreenAutoShutdown']],
                'overwriteMode' => ['label' => 'Overwrite mode', 'type' => 'string', 'path' => ['OverWrite']],
                'videoOutput' => ['label' => 'Video output mode', 'type' => 'string', 'path' => ['VideoOutPut']],
            ],
        ],
        'locale_formats' => [
            'label' => 'Locale and display formats', 'branch' => 'General.Location',
            'fields' => [
                'language' => ['label' => 'Language', 'type' => 'string', 'path' => ['Language']],
                'dateFormat' => ['label' => 'Date format', 'type' => 'string', 'path' => ['DateFormat']],
                'dateSeparator' => ['label' => 'Date separator', 'type' => 'string', 'path' => ['DateSeparator']],
                'timeFormat' => ['label' => 'Time format', 'type' => 'string', 'path' => ['TimeFormat']],
                'videoFormat' => ['label' => 'Video standard', 'type' => 'string', 'path' => ['VideoFormat']],
                'dstRule' => ['label' => 'Daylight-saving rule', 'type' => 'string', 'path' => ['DSTRule']],
                'iranCalendar' => ['label' => 'Iranian calendar', 'type' => 'boolean-integer', 'path' => ['IranCalendar']],
                'workDaysMask' => ['label' => 'Work-days mask', 'type' => 'integer', 'min' => 0, 'max' => 127, 'path' => ['WorkDay']],
            ],
        ],
        'defog' => [
            'label' => 'Defog', 'branch' => 'Camera.ClearFog',
            'warning' => 'Changing image processing may temporarily alter the live picture.',
            'fields' => [
                'enable' => ['label' => 'Defog processing', 'type' => 'boolean', 'path' => [0, 'enable']],
                'level' => ['label' => 'Defog level', 'type' => 'integer', 'min' => 0, 'max' => 100, 'path' => [0, 'level']],
            ],
        ],
        'white_light' => [
            'label' => 'White-light control', 'branch' => 'Camera.WhiteLight',
            'warning' => 'These settings control the camera illuminator and can produce visible light.',
            'fields' => [
                'brightness' => ['label' => 'Brightness', 'type' => 'integer', 'min' => 0, 'max' => 100, 'path' => ['Brightness']],
                'workMode' => ['label' => 'Work mode', 'type' => 'string', 'path' => ['WorkMode']],
                'motionDuration' => ['label' => 'Motion-trigger duration (seconds)', 'type' => 'integer', 'min' => 0, 'max' => 3600, 'path' => ['MoveTrigLight', 'Duration']],
                'motionLevel' => ['label' => 'Motion-trigger level', 'type' => 'integer', 'min' => 0, 'max' => 10, 'path' => ['MoveTrigLight', 'Level']],
                'scheduleEnable' => ['label' => 'Scheduled work period', 'type' => 'boolean-integer', 'path' => ['WorkPeriod', 'Enable']],
                'startHour' => ['label' => 'Start hour', 'type' => 'integer', 'min' => 0, 'max' => 23, 'path' => ['WorkPeriod', 'SHour']],
                'startMinute' => ['label' => 'Start minute', 'type' => 'integer', 'min' => 0, 'max' => 59, 'path' => ['WorkPeriod', 'SMinute']],
                'endHour' => ['label' => 'End hour', 'type' => 'integer', 'min' => 0, 'max' => 23, 'path' => ['WorkPeriod', 'EHour']],
                'endMinute' => ['label' => 'End minute', 'type' => 'integer', 'min' => 0, 'max' => 59, 'path' => ['WorkPeriod', 'EMinute']],
            ],
        ],
        'overlay_indicators' => [
            'label' => 'Overlay indicators', 'branch' => 'fVideo.GUISet',
            'fields' => [
                'alarmState' => ['label' => 'Alarm state', 'type' => 'boolean', 'path' => ['AlarmStateEnable']],
                'bitRate' => ['label' => 'Bit-rate state', 'type' => 'boolean', 'path' => ['ChanStateBitRateEnable']],
                'locked' => ['label' => 'Lock state', 'type' => 'boolean', 'path' => ['ChanStateLckEnable']],
                'motion' => ['label' => 'Motion state', 'type' => 'boolean', 'path' => ['ChanStateMtdEnable']],
                'recording' => ['label' => 'Channel recording state', 'type' => 'boolean', 'path' => ['ChanStateRecEnable']],
                'videoLoss' => ['label' => 'Video-loss state', 'type' => 'boolean', 'path' => ['ChanStateVlsEnable']],
                'channelTitle' => ['label' => 'Channel title', 'type' => 'boolean', 'path' => ['ChannelTitleEnable']],
                'recordState' => ['label' => 'Record state', 'type' => 'boolean', 'path' => ['RecordStateEnable']],
                'timeTitle' => ['label' => 'Time title', 'type' => 'boolean', 'path' => ['TimeTitleEnable']],
                'windowAlpha' => ['label' => 'Window opacity', 'type' => 'integer', 'min' => 0, 'max' => 255, 'path' => ['WindowAlpha']],
            ],
        ],
        'osd_logo' => [
            'label' => 'OSD logo', 'branch' => 'fVideo.OsdLogo',
            'fields' => [
                'enable' => ['label' => 'OSD logo', 'type' => 'boolean', 'path' => ['Enable']],
                'left' => ['label' => 'Left position', 'type' => 'integer', 'min' => 0, 'max' => 8192, 'path' => ['Left']],
                'top' => ['label' => 'Top position', 'type' => 'integer', 'min' => 0, 'max' => 8192, 'path' => ['Top']],
                'foregroundTransparency' => ['label' => 'Foreground transparency', 'type' => 'integer', 'min' => 0, 'max' => 255, 'path' => ['FgTrans']],
                'backgroundTransparency' => ['label' => 'Background transparency', 'type' => 'integer', 'min' => 0, 'max' => 255, 'path' => ['BgTrans']],
            ],
        ],
        'output_volume' => [
            'label' => 'Output volume', 'branch' => 'fVideo.Volume',
            'warning' => 'High output-volume values may produce loud audio at connected equipment.',
            'fields' => [
                'audioMode' => ['label' => 'Audio mode', 'type' => 'string', 'path' => [0, 'AudioMode']],
                'leftVolume' => ['label' => 'Left volume', 'type' => 'integer', 'min' => 0, 'max' => 100, 'path' => [0, 'LeftVolume']],
                'rightVolume' => ['label' => 'Right volume', 'type' => 'integer', 'min' => 0, 'max' => 100, 'path' => [0, 'RightVolume']],
            ],
        ],
        'input_volume' => [
            'label' => 'Input volume', 'branch' => 'fVideo.VolumeIn',
            'fields' => [
                'audioMode' => ['label' => 'Audio mode', 'type' => 'string', 'path' => [0, 'AudioMode']],
                'leftVolume' => ['label' => 'Left input volume', 'type' => 'integer', 'min' => 0, 'max' => 100, 'path' => [0, 'LeftVolume']],
                'rightVolume' => ['label' => 'Right input volume', 'type' => 'integer', 'min' => 0, 'max' => 100, 'path' => [0, 'RightVolume']],
            ],
        ],
        'detection_tracking' => [
            'label' => 'Detection tracking', 'branch' => 'Detect.DetectTrack',
            'fields' => [
                'sensitivity' => ['label' => 'Tracking sensitivity', 'type' => 'integer', 'min' => 0, 'max' => 10, 'path' => ['Sensitivity']],
            ],
        ],
        'human_detection' => [
            'label' => 'Human detection', 'branch' => 'Detect.HumanDetection',
            'warning' => 'Send Email, App Push, FTP upload, Write log, event snapshots, Alarm Sound, active periods, and PTZ activation use the camera’s shared motion-alert settings. Email and FTP destinations are configured on their separate pages. Attaching an event snapshot to email also requires Send Email. Alarm Sound and PTZ activation appear only when their corresponding device capabilities are advertised. A camera-reported preset list does not prove that a preset has been saved; Pattern playback has not been verified on Balcon.',
            'fields' => [
                'enable' => ['label' => 'Human detection', 'type' => 'boolean', 'path' => [0, 'Enable']],
                'pushInterval' => ['label' => 'Push interval', 'type' => 'integer', 'min' => 0, 'max' => 3600000, 'path' => [0, 'PushInterval']],
                'showRule' => ['label' => 'Show detection rule', 'type' => 'boolean-integer', 'path' => [0, 'ShowRule']],
                'showTrack' => ['label' => 'Show tracking overlay', 'type' => 'boolean-integer', 'path' => [0, 'ShowTrack']],
                'ruleEnabled' => ['label' => 'Intrusion / tripwire rule', 'type' => 'boolean', 'path' => [0, 'PedRule', 0, 'Enable']],
                'ruleType' => [
                    'label' => 'Rule type', 'type' => 'integer', 'min' => 0, 'max' => 1,
                    'options' => ['0', '1'],
                    'optionLabels' => ['0' => 'Tripwire', '1' => 'Intrusion area'],
                    'path' => [0, 'PedRule', 0, 'RuleType'],
                ],
            ],
        ],
    ];

    private const HUMAN_ACTION_DEFINITION = [
        'label' => 'Human detection actions',
        'branch' => 'Detect.MotionDetect.[0]',
        'fields' => [
            'appPush' => [
                'label' => 'App Push (shared alert action)',
                'type' => 'boolean',
                'path' => ['EventHandler', 'MessageEnable'],
            ],
            'sendEmail' => [
                'label' => 'Send Email (shared alert action)',
                'type' => 'boolean',
                'path' => ['EventHandler', 'MailEnable'],
            ],
            'alarmSound' => [
                'label' => 'Alarm Sound (camera voice)',
                'type' => 'boolean',
                'path' => ['EventHandler', 'VoiceEnable'],
            ],
        ],
    ];

    private const HUMAN_OPTIONAL_ACTION_FIELDS = [
        'ftpUpload' => [
            'label' => 'Upload by FTP (shared alert action)',
            'type' => 'boolean',
            'path' => ['EventHandler', 'FTPEnable'],
        ],
        'writeLog' => [
            'label' => 'Write log (shared alert action)',
            'type' => 'boolean',
            'path' => ['EventHandler', 'LogEnable'],
        ],
    ];

    public function __construct(private DvripReader $reader, private array $alarmSoundSettings = [])
    {
    }

    public static function list(): array
    {
        $definitions = self::definitions();
        return array_map(
            static fn (string $id, array $definition): array => [
                'id' => $id,
                'name' => $definition['label'],
            ],
            array_keys($definitions),
            array_values($definitions)
        );
    }

    public function available(array $capabilities, array $systemInfo): array
    {
        $available = [];
        $families = [];
        foreach (self::definitions() as $id => $definition) {
            if ($this->isHardwareInapplicable($id, $capabilities, $systemInfo)) {
                continue;
            }
            try {
                $branch = $this->discoveryBranch($definition['branch'], $families);
                $this->model($id, $definition, $branch);
                $dhcpIndex = $this->dhcpIndex($id);
                if ($dhcpIndex !== null) {
                    $this->validateDhcpInterface(
                        $this->discoveryBranch('NetWork.NetDHCP', $families),
                        $dhcpIndex,
                        'eth' . $dhcpIndex,
                    );
                }
                $available[] = ['id' => $id, 'name' => $definition['label']];
            } catch (DvripException $exception) {
                if (!in_array($exception->publicCode(), [
                    'device_unsupported', 'device_config_missing', 'device_error',
                ], true)) {
                    throw $exception;
                }
            }
        }
        return $available;
    }

    public function get(string $id): array
    {
        $definition = $this->definition($id);
        $branch = $this->readBranch($definition);
        $model = $this->model($id, $definition, $branch);
        $model = $this->withConfirmedSelects($model);
        if ($id === 'human_detection') {
            $model['ruleGeometry'] = $this->humanRuleGeometry($branch);
            $actions = $this->readHumanActions();
            return $actions === null ? $model : $this->withHumanActions($model, $actions);
        }
        if (in_array($id, ['main_stream', 'extra_stream'], true)) {
            $model = $this->withStreamOptions($model);
        }
        if ($id === 'snapshot_image') {
            $model = $this->withSnapshotImageOptions($model);
        }
        $dhcpIndex = $this->dhcpIndex($id);
        return $dhcpIndex === null
            ? $model
            : $this->withDhcp($model, $this->readDhcpInterface($dhcpIndex), $dhcpIndex);
    }

    private function withConfirmedSelects(array $model): array
    {
        // SDK values, not inferred from the order of translated CMS labels.
        $lists = match ($model['id']) {
            'locale_formats' => [
                'language' => ['English' => 'English'],
                'dateFormat' => ['YYMMDD' => 'Year / Month / Day', 'MMDDYY' => 'Month / Day / Year', 'DDMMYY' => 'Day / Month / Year'],
                'dateSeparator' => ['.' => 'Dot (.)', '-' => 'Hyphen (-)', '/' => 'Slash (/)'],
                'timeFormat' => ['12' => '12-hour', '24' => '24-hour'],
                'videoFormat' => ['PAL' => 'PAL', 'NTSC' => 'NTSC'],
            ],
            'image_basic' => [
                'dayNightMode' => ['0x00000000' => 'Star IR', '0x00000001' => 'Full color', '0x00000002' => 'Black and white', '0x00000003' => 'Smart Alert'],
                'autoIris' => ['0x00000000' => 'Close', '0x00000001' => 'Open'],
                'backlightCompensation' => ['0x00000000' => 'Close', '0x00000001' => 'Open'],
                'whiteBalance' => ['0x00000000' => 'Automatic', '0x00000001' => 'Indoor', '0x00000002' => 'Outdoor'],
                'slowShutter' => ['0x00000000' => 'None', '0x00000002' => 'Low', '0x00000004' => 'Medium', '0x00000006' => 'High'],
                'irCutMode' => [0 => 'IR synchronous switch', 1 => 'Automatic switch'],
                'dayNoiseFilter' => [0 => '0 — No filtering', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5 — Strongest filtering'],
                'nightNoiseFilter' => [0 => '0 — No filtering', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5 — Strongest filtering'],
            ],
            'image_advanced' => ['dwdr' => [0 => 'Close', 1 => 'Open']],
            default => [],
        };
        foreach ($lists as $name => $labels) {
            if (!isset($model['fields'][$name])) continue;
            $current = $model['values'][$name];
            if (!array_key_exists($current, $labels)) $labels[$current] = 'Current camera value (' . $current . ')';
            $options = array_keys($labels);
            if ($model['fields'][$name]['type'] !== 'integer') $options = array_map('strval', $options);
            $model['fields'][$name]['options'] = $options;
            $model['fields'][$name]['optionLabels'] = $labels;
        }
        if ($model['id'] === 'locale_formats') {
            $model['fields']['language']['help'] = 'English only for now. Any existing different language is preserved until you choose English.';
            $model['fields']['videoFormat']['help'] = 'Changing PAL/NTSC can affect available frame rates and interrupt the stream.';
            $model['fields']['dstRule']['help'] = 'DST mode and transition dates remain unchanged; their complete wire-value mapping is not yet confirmed.';
        }
        return $model['id'] === 'image_basic' ? $this->withExposureOptions($model) : $model;
    }

    private function withExposureOptions(array $model): array
    {
        if (!isset($model['fields']['exposureMode'])) return $model;
        $labels = [0 => 'Automatic'];
        $capability = null;
        if (method_exists($this->reader, 'getCameraCapability')) {
            try {
                $capability = $this->reader->getCameraCapability();
            } catch (DvripException $exception) {
                if (!in_array($exception->publicCode(), ['device_unsupported', 'device_config_missing', 'device_error'], true)) throw $exception;
            }
        }
        if (is_array($capability) && is_array($capability['Speeds'] ?? null)) {
            $count = min(64, max(0, (int) ($capability['Count'] ?? 0)), count($capability['Speeds']));
            for ($i = 0; $i < $count; $i++) {
                $speed = $capability['Speeds'][$i];
                if (!is_string($speed) || !preg_match('/^0x[0-9a-fA-F]{8}$/D', $speed) || hexdec($speed) <= 0) break;
                $labels[$i + 1] = 'Manual 1/' . (int) round(1000000 / hexdec($speed));
            }
        }
        $current = $model['values']['exposureMode'];
        if (!array_key_exists($current, $labels)) $labels[$current] = 'Current camera mode (' . $current . ')';
        $model['fields']['exposureMode']['options'] = array_keys($labels);
        $model['fields']['exposureMode']['optionLabels'] = $labels;
        $model['fields']['exposureMode']['disabled'] = !is_array($capability);
        $model['fields']['exposureMode']['help'] = is_array($capability)
            ? 'Manual presets come from this camera. Choosing a different manual preset clears automatic timing limits, as in CMS.'
            : 'Exposure presets could not be retrieved; the current mode is preserved.';
        return $model;
    }

    private function withStreamOptions(array $model): array
    {
        if (!method_exists($this->reader, 'getEncodeCapability')) {
            return $this->withStreamPresets($model, []);
        }

        try {
            $capability = $this->reader->getEncodeCapability();
        } catch (DvripException $exception) {
            if (in_array($exception->publicCode(), [
                'device_unsupported', 'device_config_missing', 'device_error',
            ], true)) {
                return $this->withStreamPresets($model, []);
            }
            throw $exception;
        }

        $streamType = $model['id'] === 'main_stream' ? 'MainStream' : 'ExtraStream2';
        $optionsByField = [
            'compression' => self::codecNames($capability, $streamType),
            'resolution' => self::resolutionNames($capability, $streamType),
        ];
        foreach ($optionsByField as $field => $options) {
            if ($options === [] || !isset($model['fields'][$field])) {
                continue;
            }
            $current = (string) ($model['values'][$field] ?? '');
            if ($current !== '' && !array_key_exists($current, $options)) {
                $options[$current] = 'Current camera value (not listed as supported)';
            }
            $model['fields'][$field]['options'] = array_keys($options);
            $model['fields'][$field]['optionLabels'] = $options;
            $model['fields'][$field]['help'] = 'Supported options reported by this camera for this stream.';
        }
        return $this->withStreamPresets($model, is_array($capability) ? $capability : []);
    }

    private function withStreamPresets(array $model, array $capability): array
    {
        $choices = [
            'fps' => range(1, 120),
            'bitRate' => [64, 128, 256, 384, 512, 535, 743, 768, 1024, 1072, 1536, 1608, 2048, 2145, 3072, 3217, 4096, 4290, 6144, 8192, 12288, 16384, 24576, 32768, 49152, 61440],
            'bitRateControl' => ['CBR', 'VBR'],
            'quality' => range(0, 10),
            'gop' => range(1, 300),
        ];
        foreach ($choices as $name => $values) {
            if (!isset($model['fields'][$name])) continue;
            if ($name === 'bitRate' && is_int($capability['MaxBitrate'] ?? null)) {
                $values = array_values(array_filter($values, fn ($value) => $value <= $capability['MaxBitrate']));
            }
            $current = $model['values'][$name];
            if (!in_array($current, $values, true)) $values[] = $current;
            if ($name !== 'bitRateControl') sort($values, SORT_NUMERIC);
            $model['fields'][$name]['options'] = $values;
            $model['fields'][$name]['help'] = 'App-provided choices, not a camera-advertised list. The current camera value is retained.';
            if ($name === 'bitRate' && is_int($capability['MaxBitrate'] ?? null)) {
                $model['fields'][$name]['help'] .= ' Camera-reported maximum: ' . $capability['MaxBitrate'] . ' kbps.';
            }
        }
        $model['fields']['bitRateControl']['optionLabels'] = ['CBR' => 'CBR — Constant bit rate', 'VBR' => 'VBR — Variable bit rate'];
        $model['fields']['gop']['label'] = 'I-frame interval (GOP)';
        return $model;
    }

    private function withSnapshotImageOptions(array $model): array
    {
        $supported = $this->snapshotResolutionOptions();
        foreach (['regularResolution', 'motionResolution', 'alarmResolution'] as $name) {
            $options = $supported;
            $current = (string) $model['values'][$name];
            if (!array_key_exists($current, $options)) {
                $options[$current] = $supported === []
                    ? 'Current camera value'
                    : 'Current camera value (not listed as supported)';
            }
            $model['fields'][$name]['options'] = array_keys($options);
            $model['fields'][$name]['optionLabels'] = $options;
            $model['fields'][$name]['help'] = $supported === []
                ? 'The camera did not advertise alternatives; the current resolution can be retained.'
                : 'Supported resolutions reported by this camera’s video encoder.';
        }
        foreach (['regularBitRate', 'motionBitRate', 'alarmBitRate'] as $name) {
            $values = [64, 128, 256, 384, 512, 768, 1024, 1536, 2048, 3072, 4096, 6144, 8192, 12288, 16384];
            $current = $model['values'][$name];
            if (!in_array($current, $values, true)) $values[] = $current;
            sort($values, SORT_NUMERIC);
            $model['fields'][$name]['options'] = $values;
            $model['fields'][$name]['help'] = 'App-provided bit-rate choices; the current camera value is always retained.';
        }
        return $model;
    }

    private function snapshotResolutionOptions(): array
    {
        if (!method_exists($this->reader, 'getEncodeCapability')) return [];
        try {
            $capability = $this->reader->getEncodeCapability();
        } catch (DvripException $exception) {
            if (in_array($exception->publicCode(), ['device_unsupported', 'device_config_missing', 'device_error'], true)) return [];
            throw $exception;
        }
        return self::resolutionNames($capability, 'MainStream')
            + self::resolutionNames($capability, 'ExtraStream2');
    }

    private static function encodeInfo(mixed $capability, string $streamType): ?array
    {
        if (!is_array($capability) || !isset($capability['EncodeInfo']) || !is_array($capability['EncodeInfo'])) {
            return null;
        }
        foreach ($capability['EncodeInfo'] as $item) {
            if (is_array($item) && ($item['StreamType'] ?? null) === $streamType) {
                return $item;
            }
        }
        return null;
    }

    private static function codecNames(mixed $capability, string $streamType): array
    {
        $stream = self::encodeInfo($capability, $streamType);
        $maskText = is_array($stream) ? ($stream['CompressionMask'] ?? null) : null;
        if (!is_string($maskText) || preg_match('/^0x[0-9a-f]+$/iD', $maskText) !== 1) {
            return [];
        }

        $mask = hexdec(substr($maskText, 2));
        $supported = [
            0 => ['DIVX MPEG4', 'DivX MPEG-4'],
            1 => ['MS MPEG4', 'Microsoft MPEG-4'],
            2 => ['MPEG2', 'MPEG-2'],
            3 => ['MPEG1', 'MPEG-1'],
            4 => ['H.263', 'H.263'],
            5 => ['MJPEG', 'Motion JPEG (MJPEG)'],
            6 => ['FCCMPEG4', 'FCC MPEG-4'],
            7 => ['H.264', 'H.264 / AVC'],
            8 => ['H.265', 'H.265 / HEVC'],
        ];
        $options = [];
        foreach ($supported as $bit => [$wireValue, $label]) {
            if (($mask & (1 << $bit)) !== 0) {
                $options[$wireValue] = $label;
            }
        }
        return $options;
    }

    private static function resolutionNames(mixed $capability, string $streamType): array
    {
        $stream = self::encodeInfo($capability, $streamType);
        $maskText = is_array($stream) ? ($stream['ResolutionMask'] ?? null) : null;
        if (!is_string($maskText) || preg_match('/^0x[0-9a-f]+$/iD', $maskText) !== 1) {
            return [];
        }

        // ResolutionMask bit positions and values are the Xiongmai resolution enum.
        $supported = [
            0 => ['D1', 'D1 (704 × 576 PAL / 704 × 480 NTSC)'],
            1 => ['HD1', 'HD1 (half-D1)'],
            2 => ['BCIF', 'BCIF'],
            3 => ['CIF', 'CIF (352 × 288 PAL / 352 × 240 NTSC)'],
            4 => ['QCIF', 'QCIF (176 × 144 PAL / 176 × 120 NTSC)'],
            5 => ['VGA', 'VGA (640 × 480)'],
            6 => ['QVGA', 'QVGA (320 × 240)'],
            7 => ['SVCD', 'SVCD'],
            8 => ['QQVGA', 'QQVGA (160 × 120)'],
            9 => ['ND1', 'ND1'],
            10 => ['650TVL', '650 TVL'],
            11 => ['720P', '720p (1280 × 720)'],
            12 => ['1_3M', '1.3 megapixel'],
            13 => ['UXGA', 'UXGA (1600 × 1200)'],
            14 => ['1080P', '1080p (1920 × 1080)'],
            15 => ['WUXGA', 'WUXGA (1920 × 1200)'],
            16 => ['2_5M', '2.5 megapixel'],
            17 => ['3M', '3 megapixel'],
            18 => ['5M', '5 megapixel'],
            19 => ['1080N', '1080N'],
            20 => ['4M', '4 megapixel'],
            21 => ['6M', '6 megapixel'],
            22 => ['8M', '8 megapixel'],
            23 => ['12M', '12 megapixel'],
            24 => ['4K', '4K / 8 megapixel'],
            25 => ['720N', '720N'],
            26 => ['WSVGA', 'WSVGA'],
            27 => ['NHD', 'nHD (640 × 360)'],
            28 => ['3M_N', '3M-N'],
            29 => ['4M_N', '4M-N'],
            30 => ['5M_N', '5M-N'],
            31 => ['4K_N', '4K-N'],
        ];
        $mask = (int) hexdec(substr($maskText, 2));
        $options = [];
        foreach ($supported as $bit => [$wireValue, $label]) {
            if (($mask & (1 << $bit)) !== 0) {
                $options[$wireValue] = $label;
            }
        }
        return $options;
    }

    public function update(string $id, array $input): array
    {
        $definition = $this->definition($id);
        $dhcpIndex = $this->dhcpIndex($id);
        if ($dhcpIndex !== null && (!array_key_exists('dhcp', $input) || !is_bool($input['dhcp']))) {
            throw new InvalidArgumentException('Field dhcp must be boolean.');
        }
        $clean = $this->validate($definition, $input);
        $parentBranch = null;
        $current = $this->readBranch($definition, $parentBranch);
        if ($id === 'image_basic' && isset($clean['exposureMode'])) {
            $exposure = $this->withExposureOptions($this->model($id, $definition, $current));
            $oldMode = $exposure['values']['exposureMode'];
            if ($clean['exposureMode'] !== $oldMode
                && ($exposure['fields']['exposureMode']['disabled'] || !in_array($clean['exposureMode'], $exposure['fields']['exposureMode']['options'], true))) {
                throw new InvalidArgumentException('Choose a camera-provided exposure preset.');
            }
            if ($clean['exposureMode'] !== 0) {
                foreach (['exposureMinimumUs' => 'LeastTime', 'exposureMaximumUs' => 'MostTime'] as $name => $key) {
                    if (isset($clean[$name])) $clean[$name] = $clean['exposureMode'] !== $oldMode ? 0 : (int) hexdec($current[0]['ExposureParam'][$key]);
                }
            } elseif (isset($clean['exposureMinimumUs'], $clean['exposureMaximumUs']) && $clean['exposureMinimumUs'] > $clean['exposureMaximumUs']) {
                throw new InvalidArgumentException('Minimum exposure must not exceed maximum exposure.');
            }
        }
        $humanActions = $id === 'human_detection' ? $this->readHumanActions() : null;
        $optionalHumanActions = $humanActions === null ? [] : $this->availableHumanOptionalActions($humanActions);
        if ($id === 'human_detection') {
            if ($humanActions === null && array_intersect(['appPush', 'sendEmail', 'snapshot', 'alarmSound', 'soundType', 'ftpUpload', 'writeLog', 'periodChanges', 'ptzAction', 'ptzNumber'], array_keys($input)) !== []) {
                throw new DvripException('device_config_missing', 'Camera does not expose alert action settings.');
            }
            if ($humanActions !== null) {
                foreach (['appPush', 'sendEmail'] as $name) {
                    if (!array_key_exists($name, $input) || !is_bool($input[$name])) {
                        throw new InvalidArgumentException("Field {$name} must be boolean.");
                    }
                }
                $snapshotCurrent = $this->humanSnapshotState($humanActions);
                if ($snapshotCurrent !== null && (!array_key_exists('snapshot', $input) || !is_bool($input['snapshot']))) {
                    throw new InvalidArgumentException('Field snapshot must be boolean.');
                }
                if ($snapshotCurrent === null && array_key_exists('snapshot', $input)) {
                    throw new InvalidArgumentException('Event snapshot is not available on this camera.');
                }
                if ($this->supportsHumanAlarmSound()) {
                    if (!array_key_exists('alarmSound', $input) || !is_bool($input['alarmSound'])) {
                        throw new InvalidArgumentException('Field alarmSound must be boolean.');
                    }
                } elseif (array_key_exists('alarmSound', $input)) {
                    throw new InvalidArgumentException('Alarm Sound is not supported by this camera.');
                }
                $soundOptions = $this->humanSoundOptions($humanActions);
                if ($soundOptions !== []) {
                    if (!array_key_exists('soundType', $input) || !is_int($input['soundType'])
                        || !array_key_exists($input['soundType'], $soundOptions)) {
                        throw new InvalidArgumentException('Sound type must be one advertised by this camera.');
                    }
                } elseif (array_key_exists('soundType', $input)) {
                    throw new InvalidArgumentException('Sound type is not available on this camera.');
                }
                foreach (self::HUMAN_OPTIONAL_ACTION_FIELDS as $name => $field) {
                    if (isset($optionalHumanActions[$name])) {
                        if (!array_key_exists($name, $input) || !is_bool($input[$name])) {
                            throw new InvalidArgumentException("Field {$name} must be boolean.");
                        }
                    } elseif (array_key_exists($name, $input)) {
                        throw new InvalidArgumentException("Field {$name} is not available on this camera.");
                    }
                }
                $ptz = $this->humanPtzLink($humanActions);
                if ($ptz === null) {
                    if (array_key_exists('ptzAction', $input) || array_key_exists('ptzNumber', $input)) {
                        throw new InvalidArgumentException('PTZ activation is not available on this camera.');
                    }
                } else {
                    $options = $this->humanPtzOptions();
                    $ptzAction = $input['ptzAction'] ?? null;
                    $submittedNumber = $input['ptzNumber'] ?? null;
                    if (!is_string($ptzAction) || !in_array($ptzAction, $options, true)) {
                        throw new InvalidArgumentException('PTZ activation action is not available on this camera.');
                    }
                    if ($ptzAction === 'None') {
                        if ($submittedNumber !== null && $submittedNumber !== 0) {
                            throw new InvalidArgumentException('No PTZ activation must not specify a saved slot number.');
                        }
                        $ptzNumber = 0;
                    } else {
                        if (!is_int($submittedNumber)
                            || ($submittedNumber < 1 && !([$ptzAction, $submittedNumber] === $ptz))) {
                            throw new InvalidArgumentException('Enter the number of an existing saved PTZ action.');
                        }
                        $ptzNumber = $submittedNumber;
                    }
                }
            }
        }
        if (in_array($id, ['main_stream', 'extra_stream'], true)) {
            $this->validateStreamOptions($id, $definition, $current, $clean);
        }
        if ($id === 'snapshot_image') {
            $this->validateSnapshotImageOptions($definition, $current, $clean);
        }
        $dhcp = $dhcpIndex === null ? null : $this->readDhcpInterface($dhcpIndex);
        $updated = $current;
        $updatedActions = $humanActions;

        if ($humanActions !== null) {
            foreach (self::HUMAN_ACTION_DEFINITION['fields'] + $optionalHumanActions as $name => $field) {
                if (array_key_exists($name, $input)) {
                    $this->setPath($updatedActions, $field['path'], $input[$name]);
                }
            }
            if ($soundOptions !== []) {
                $updatedActions['EventHandler']['VoiceType'] = $input['soundType'];
            }
            if (array_key_exists('periodChanges', $input)) {
                $this->applyHumanPeriodChanges($updatedActions, $input['periodChanges']);
            }
            if ($snapshotCurrent !== null && $input['snapshot'] !== $snapshotCurrent) {
                $this->applyHumanSnapshotChange($updatedActions, $input['snapshot']);
            }
            if ($ptz !== null) {
                $selected = [$input['ptzAction'], $ptzNumber];
                if ($selected !== $ptz) {
                    $updatedActions['EventHandler']['PtzLink'][0] = $selected;
                    if ($input['ptzAction'] !== 'None') {
                        $updatedActions['EventHandler']['PtzEnable'] = true;
                    }
                }
            }
        }

        foreach ($definition['fields'] as $name => $field) {
            if (!array_key_exists($name, $clean)) continue;
            if (($field['writable'] ?? true) !== true) {
                continue;
            }
            $wireValue = $field['wireMap'][$clean[$name]] ?? $clean[$name];
            if ($field['hexString'] ?? false) {
                $original = $this->getPath($current, $field['path']);
                $wireValue = (int) hexdec($original) === $clean[$name] ? $original : sprintf('0x%08X', $clean[$name]);
            }
            $this->setPath($updated, $field['path'], $wireValue);
        }

        if ($id === 'human_detection' && array_key_exists('ruleGeometryChanges', $input)) {
            $this->applyHumanRuleGeometry($updated, $input['ruleGeometryChanges']);
        }

        if (in_array($id, ['human_detection', 'snapshot'], true) && $updated === $current) {
            $verified = $current;
        } elseif ($id === 'snapshot' && $parentBranch !== null) {
            $parentBranch['Snapshot'] = $updated;
            $this->reader->setConfig('Storage', $parentBranch);
            $verifiedParent = $this->reader->getConfig('Storage');
            if ($verifiedParent !== $parentBranch) {
                throw new DvripException(
                    'write_verification_failed',
                    'Storage read-back did not match the submitted object.'
                );
            }
            $verified = $verifiedParent['Snapshot'];
        } else {
            $this->reader->setConfig($definition['branch'], $updated);
            $verified = $this->readBranch($definition);
        }
        $model = $this->model($id, $definition, $verified);
        $model = $this->withConfirmedSelects($model);
        if ($id === 'snapshot_image') {
            $model = $this->withSnapshotImageOptions($model);
        }
        if ($id === 'human_detection') {
            $model['ruleGeometry'] = $this->humanRuleGeometry($verified);
        }
        if ($id === 'wifi'
            ? !$this->wifiMatchesInput($definition, $model, $input, $clean)
            : $verified !== $updated
        ) {
            throw new DvripException(
                'write_verification_failed',
                'Configuration read-back did not match the submitted object.'
            );
        }
        if ($humanActions !== null) {
            if ($updatedActions !== $humanActions) {
                $this->reader->setConfig(self::HUMAN_ACTION_DEFINITION['branch'], $updatedActions);
                $verifiedActions = $this->readBranch(self::HUMAN_ACTION_DEFINITION);
                if ($verifiedActions !== $updatedActions) {
                    throw new DvripException(
                        'write_verification_failed',
                        'Alert action read-back did not match the submitted object.'
                    );
                }
            } else {
                $verifiedActions = $humanActions;
            }
            return $this->withHumanActions($model, $verifiedActions);
        }
        if ($dhcpIndex === null) return $model;

        $updatedDhcp = $dhcp;
        $updatedDhcp[$dhcpIndex]['Enable'] = $input['dhcp'];
        $this->reader->setConfig('NetWork.NetDHCP', $updatedDhcp);
        $verifiedDhcp = $this->readDhcpInterface($dhcpIndex);
        if ($verifiedDhcp !== $updatedDhcp) {
            throw new DvripException(
                'write_verification_failed',
                'DHCP read-back did not match the submitted object.'
            );
        }
        return $this->withDhcp($model, $verifiedDhcp, $dhcpIndex);
    }

    private function validateStreamOptions(string $id, array $definition, array $current, array $input): void
    {
        if (!method_exists($this->reader, 'getEncodeCapability')) {
            return;
        }
        try {
            $capability = $this->reader->getEncodeCapability();
        } catch (DvripException $exception) {
            if (in_array($exception->publicCode(), [
                'device_unsupported', 'device_config_missing', 'device_error',
            ], true)) {
                return;
            }
            throw $exception;
        }
        $streamType = $id === 'main_stream' ? 'MainStream' : 'ExtraStream2';
        $options = [
            'compression' => self::codecNames($capability, $streamType),
            'resolution' => self::resolutionNames($capability, $streamType),
        ];
        foreach ($options as $name => $supported) {
            if ($supported === [] || !isset($definition['fields'][$name])) {
                continue;
            }
            $currentValue = $this->getPath($current, $definition['fields'][$name]['path']);
            if ($input[$name] !== $currentValue && !array_key_exists($input[$name], $supported)) {
                throw new InvalidArgumentException("Field {$name} is not supported by this camera stream.");
            }
        }
    }

    private function validateSnapshotImageOptions(array $definition, array $current, array $input): void
    {
        $supported = $this->snapshotResolutionOptions();
        foreach (['regularResolution', 'motionResolution', 'alarmResolution'] as $name) {
            $currentValue = $this->getPath($current, $definition['fields'][$name]['path']);
            if ($input[$name] !== $currentValue && !array_key_exists($input[$name], $supported)) {
                throw new InvalidArgumentException("Field {$name} is not advertised by this camera encoder.");
            }
        }
    }

    private function readDhcpInterface(int $index): array
    {
        return $this->validateDhcpInterface(
            $this->reader->getConfig('NetWork.NetDHCP'),
            $index,
            'eth' . $index,
        );
    }

    private function validateDhcpInterface(mixed $branch, int $index, string $interface): array
    {
        if (!is_array($branch)
            || !isset($branch[$index])
            || !is_array($branch[$index])
            || !array_key_exists('Enable', $branch[$index])
            || ($branch[$index]['Interface'] ?? null) !== $interface
        ) {
            throw new DvripException('device_config_missing', 'Camera returned an invalid DHCP interface.');
        }
        return $branch;
    }

    private function withDhcp(array $model, array $dhcp, int $index): array
    {
        $model['fields'] = ['dhcp' => [
            'label' => $index === 0 ? 'Use DHCP (wired / eth0)' : 'Use DHCP (Wi-Fi / eth1)',
            'type' => 'boolean',
        ]] + $model['fields'];
        $model['values'] = ['dhcp' => (bool) $dhcp[$index]['Enable']] + $model['values'];
        return $model;
    }

    private function dhcpIndex(string $id): ?int
    {
        return match ($id) {
            'wired_network' => 0,
            'wifi' => 1,
            default => null,
        };
    }

    private function definition(string $id): array
    {
        $definitions = self::definitions();
        if (!isset($definitions[$id])) {
            throw new InvalidArgumentException('Unknown editable network service.');
        }
        return $definitions[$id];
    }

    private static function definitions(): array
    {
        return self::DEFINITIONS + ExtendedEditorDefinitions::get();
    }

    private function isHardwareInapplicable(string $id, array $capabilities, array $systemInfo): bool
    {
        $network = $capabilities['NetServerFunction'] ?? [];
        $other = $capabilities['OtherFunction'] ?? [];

        return match ($id) {
            'wifi', 'wifi_signal' => ($network['NetWifi'] ?? false) !== true,
            'white_light' => ($other['SupportCameraWhiteLight'] ?? false) !== true,
            'recording' => ($other['NOHDDRECORD'] ?? false) === true,
            'alarm_output' => ($systemInfo['AlarmOutChannel'] ?? null) === 0,
            'local_alarm' => ($systemInfo['AlarmInChannel'] ?? null) === 0,
            default => false,
        };
    }

    private function discoveryBranch(string $branch, array &$families): array
    {
        $family = $branch === 'Simplify.Encode'
            ? $branch
            : explode('.', $branch, 2)[0];
        if (!array_key_exists($family, $families)) {
            try {
                $families[$family] = ['value' => $this->reader->getConfig($family)];
            } catch (DvripException $exception) {
                $families[$family] = ['error' => $exception];
            }
        }
        if (isset($families[$family]['error'])) {
            throw $families[$family]['error'];
        }

        $value = $families[$family]['value'];
        if ($branch !== $family) {
            $value = $this->getPath(
                is_array($value) ? $value : [],
                explode('.', substr($branch, strlen($family) + 1))
            );
        }
        if (!is_array($value)) {
            throw new DvripException(
                'device_config_missing',
                'Camera returned an invalid configuration branch.'
            );
        }
        return $value;
    }

    private function readBranch(array $definition, ?array &$parentBranch = null): array
    {
        $parentBranch = null;
        try {
            $branch = $this->reader->getConfig($definition['branch']);
        } catch (DvripException $exception) {
            if ($definition['branch'] !== 'Storage.Snapshot' || !in_array($exception->publicCode(), [
                'device_unsupported', 'device_config_missing', 'device_error',
            ], true)) {
                throw $exception;
            }
            $storage = $this->reader->getConfig('Storage');
            if (!is_array($storage) || !is_array($storage['Snapshot'] ?? null)) {
                throw new DvripException('device_config_missing', 'Camera returned no snapshot configuration.');
            }
            $parentBranch = $storage;
            $branch = $storage['Snapshot'];
        }
        if (!is_array($branch)) {
            throw new DvripException('device_config_missing', 'Camera returned an invalid configuration branch.');
        }
        foreach ($definition['fields'] as $field) {
            $this->getPath($branch, $field['path']);
        }
        return $branch;
    }

    private function readHumanActions(): ?array
    {
        try {
            return $this->readBranch(self::HUMAN_ACTION_DEFINITION);
        } catch (DvripException $exception) {
            if (in_array($exception->publicCode(), ['device_unsupported', 'device_config_missing', 'device_error'], true)) {
                return null;
            }
            throw $exception;
        }
    }

    private function withHumanActions(array $model, array $branch): array
    {
        $actions = $this->model('human_detection', self::HUMAN_ACTION_DEFINITION, $branch);
        foreach ($this->availableHumanOptionalActions($branch) as $name => $field) {
            $public = $field;
            unset($public['path']);
            $actions['fields'][$name] = $public;
            $actions['values'][$name] = $this->getPath($branch, $field['path']);
        }
        if (!$this->supportsHumanAlarmSound()) {
            unset($actions['fields']['alarmSound'], $actions['values']['alarmSound']);
        }
        $model['fields'] += $actions['fields'];
        $model['values'] += $actions['values'];
        $soundOptions = $this->humanSoundOptions($branch);
        if ($soundOptions !== []) {
            $model['fields']['soundType'] = [
                'label' => 'Alarm sound',
                'type' => 'integer',
                'options' => array_map('strval', array_keys($soundOptions)),
                'optionLabels' => $soundOptions,
            ];
            $model['values']['soundType'] = $branch['EventHandler']['VoiceType'];
        }
        $snapshot = $this->humanSnapshotState($branch);
        if ($snapshot !== null) {
            $model['fields']['snapshot'] = [
                'label' => 'Include event snapshot (shared alert action)',
                'type' => 'boolean',
            ];
            $model['values']['snapshot'] = $snapshot;
        }
        $ptz = $this->humanPtzLink($branch);
        if ($ptz !== null) {
            $model['fields']['ptzAction'] = [
                'label' => 'PTZ activation action',
                'type' => 'string',
                'options' => $this->humanPtzOptions(),
                'optionLabels' => [
                    'None' => 'No PTZ activation',
                    'Preset' => 'Move to saved preset',
                    'Tour' => 'Start saved tour',
                    'Pattern' => 'Replay saved movement pattern',
                ],
            ];
            $model['fields']['ptzNumber'] = [
                'label' => 'Saved PTZ action number',
                'type' => 'integer', 'min' => 0, 'required' => true,
            ];
            $model['values']['ptzAction'] = $ptz[0];
            $model['values']['ptzNumber'] = $ptz[1];
        }
        $periods = $this->humanPeriods($branch);
        if ($periods !== null) {
            $model['periods'] = $periods;
        }
        return $model;
    }

    private function humanPtzOptions(): array
    {
        $features = $this->reader->getSystemFunction()['OtherFunction'] ?? [];
        $options = ['None'];
        if (($features['SupportSetPTZPresetAttribute'] ?? false) === true) {
            $options[] = 'Preset';
        }
        if (($features['SupportPTZTour'] ?? false) === true) {
            $options[] = 'Tour';
        }
        if (count($options) > 1) {
            $options[] = 'Pattern';
        }
        return $options;
    }

    private function humanPtzLink(array $branch): ?array
    {
        $features = $this->reader->getSystemFunction()['OtherFunction'] ?? [];
        $event = $branch['EventHandler'] ?? null;
        $link = $event['PtzLink'] ?? null;
        if (($features['SupportPTZDirectionControl'] ?? false) !== true
            || count($this->humanPtzOptions()) < 2
            || !is_array($event) || !is_bool($event['PtzEnable'] ?? null)
            || !is_array($link) || !array_is_list($link) || count($link) < 1
            || !is_array($link[0]) || !array_is_list($link[0]) || count($link[0]) !== 2
            || !is_string($link[0][0]) || !is_int($link[0][1])
            || $link[0][1] < 0
            || !in_array($link[0][0], $this->humanPtzOptions(), true)) {
            return null;
        }
        return $link[0];
    }

    private function availableHumanOptionalActions(array $branch): array
    {
        $event = $branch['EventHandler'] ?? null;
        if (!is_array($event)) return [];

        $available = [];
        if (is_bool($event['FTPEnable'] ?? null)
            && ($this->reader->getSystemFunction()['NetServerFunction']['NetFTP'] ?? false) === true) {
            $available['ftpUpload'] = self::HUMAN_OPTIONAL_ACTION_FIELDS['ftpUpload'];
        }
        if (is_bool($event['LogEnable'] ?? null)) {
            $available['writeLog'] = self::HUMAN_OPTIONAL_ACTION_FIELDS['writeLog'];
        }
        return $available;
    }

    private function humanSnapshotState(array $branch): ?bool
    {
        $event = $branch['EventHandler'] ?? null;
        $mask = $event['SnapShotMask'] ?? null;
        if (!is_array($event) || !is_bool($event['SnapEnable'] ?? null)
            || !is_string($mask) || preg_match('/^0x[0-9a-fA-F]{32}$/D', $mask) !== 1) {
            return null;
        }
        return $event['SnapEnable'] && ((hexdec(substr($mask, 2, 8)) & 1) !== 0);
    }

    private function applyHumanSnapshotChange(array &$branch, bool $enabled): void
    {
        $mask = $branch['EventHandler']['SnapShotMask'];
        $bits = (int) hexdec(substr($mask, 2, 8));
        $bits = $enabled ? ($bits | 1) : ($bits & ~1);
        $mask = '0x' . sprintf('%08X', $bits) . substr($mask, 10);
        $branch['EventHandler']['SnapShotMask'] = $mask;
        $branch['EventHandler']['SnapEnable'] = preg_match('/[1-9a-fA-F]/', substr($mask, 2)) === 1;
    }

    private function humanPeriods(array $branch): ?array
    {
        $matrix = $branch['EventHandler']['TimeSection'] ?? null;
        if (!is_array($matrix) || !array_is_list($matrix) || count($matrix) !== 7) {
            return null;
        }
        $result = [];
        $slotCount = null;
        foreach ($matrix as $row) {
            if (!is_array($row) || !array_is_list($row)
                || count($row) < 1 || count($row) > 6
                || ($slotCount !== null && count($row) !== $slotCount)
            ) {
                return null;
            }
            $slotCount = count($row);
            $day = [];
            foreach ($row as $raw) {
                if (!is_string($raw) || preg_match(
                    '/^([01]) ((?:[01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9])-((?:[01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]|24:00:00)$/D',
                    $raw,
                    $match
                ) !== 1) {
                    return null;
                }
                $day[] = [
                    'enabled' => $match[1] === '1',
                    'start' => self::displayPeriodTime($match[2]),
                    'end' => self::displayPeriodTime($match[3]),
                    'raw' => $raw,
                ];
            }
            $result[] = $day;
        }
        return $result;
    }

    private static function displayPeriodTime(string $time): string
    {
        return str_ends_with($time, ':00') ? substr($time, 0, -3) : $time;
    }

    private function applyHumanPeriodChanges(array &$actions, mixed $changes): void
    {
        $periods = $this->humanPeriods($actions);
        if ($periods === null) {
            throw new DvripException('device_config_missing', 'Camera does not expose a supported weekly period schedule.');
        }
        if (!is_array($changes) || !array_is_list($changes) || count($changes) > 42) {
            throw new InvalidArgumentException('Period changes must be a list of camera slots.');
        }
        $seen = [];
        foreach ($changes as $change) {
            if (!is_array($change)
                || !isset($change['day'], $change['slot'], $change['expected'], $change['enabled'], $change['start'], $change['end'])
                || !is_int($change['day']) || !is_int($change['slot'])
                || !is_string($change['expected']) || !is_bool($change['enabled'])
                || !is_string($change['start']) || !is_string($change['end'])
            ) {
                throw new InvalidArgumentException('Period change has invalid fields.');
            }
            $day = $change['day'];
            $slot = $change['slot'];
            $key = "{$day}:{$slot}";
            if (!isset($periods[$day][$slot]) || isset($seen[$key])) {
                throw new InvalidArgumentException('Period change targets an invalid or duplicate slot.');
            }
            $seen[$key] = true;
            if ($periods[$day][$slot]['raw'] !== $change['expected']) {
                throw new InvalidArgumentException('A camera period changed since this page was loaded. Reload before saving.');
            }
            $start = self::normalizePeriodTime($change['start'], false);
            $end = self::normalizePeriodTime($change['end'], true);
            if (self::periodSeconds($start) >= self::periodSeconds($end)) {
                throw new InvalidArgumentException('Period end must be later than its start.');
            }
            $actions['EventHandler']['TimeSection'][$day][$slot] =
                ($change['enabled'] ? '1 ' : '0 ') . $start . '-' . $end;
        }
    }

    private static function normalizePeriodTime(string $time, bool $allowEndOfDay): string
    {
        if ($allowEndOfDay && ($time === '24:00' || $time === '24:00:00')) {
            return '24:00:00';
        }
        if (preg_match('/^((?:[01][0-9]|2[0-3]):[0-5][0-9])(?::([0-5][0-9]))?$/D', $time, $match) !== 1) {
            throw new InvalidArgumentException('Period time must be HH:MM, with 24:00 allowed only as an end time.');
        }
        return $match[1] . ':' . ($match[2] ?? '00');
    }

    private static function periodSeconds(string $time): int
    {
        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $time));
        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    private function supportsHumanAlarmSound(): bool
    {
        if (array_key_exists('supported', $this->alarmSoundSettings)) {
            return $this->alarmSoundSettings['supported'] === true;
        }
        // These cameras both advertise generic voice-tip menus; only Balcon
        // advertises the additional voice-control capability and has a speaker.
        return ($this->reader->getSystemFunction()['OtherFunction']['SupportCloseVoiceTip'] ?? false) === true;
    }

    private function humanSoundOptions(array $branch): array
    {
        $current = $branch['EventHandler']['VoiceType'] ?? null;
        if (!$this->supportsHumanAlarmSound() || !is_int($current)) return [];

        try {
            $ability = $this->reader->getConfig('Ability.VoiceTipType');
        } catch (DvripException $exception) {
            if (!in_array($exception->publicCode(), ['device_unsupported', 'device_config_missing', 'device_error'], true)) {
                throw $exception;
            }
            $ability = null;
        }
        $codes = [];
        $voiceTips = is_array($ability) ? ($ability['VoiceTip'] ?? null) : null;
        if (is_array($voiceTips)) {
            foreach ($voiceTips as $item) {
                $code = is_array($item) ? ($item['VoiceEnum'] ?? null) : null;
                if (is_int($code) && $code >= 0 && $code <= 65535) $codes[$code] = true;
            }
        }
        $usedFallback = $codes === [];
        if ($codes === []) {
            foreach ($this->alarmSoundSettings['options'] ?? [] as $code => $_label) {
                $codes[(int) $code] = true;
            }
        }
        if ($codes === []) return [];

        $labels = $this->alarmSoundSettings['labels'] ?? [];
        $fallback = $usedFallback ? ($this->alarmSoundSettings['options'] ?? []) : [];
        $options = [];
        foreach (array_keys($codes) as $code) {
            $options[$code] = $labels[$code] ?? $fallback[$code]
                ?? "Camera sound option {$code} (label not verified)";
        }
        if (!isset($codes[$current])) {
            $options[$current] = 'Current sound (not advertised by camera)';
        }
        return $options;
    }

    private function humanRuleGeometry(array $branch): ?array
    {
        $rule = $branch[0]['PedRule'][0] ?? null;
        $line = $rule['RuleLine']['Pts'] ?? null;
        $region = $rule['RuleRegion']['Pts'] ?? null;
        if (!is_array($line) || !is_array($region) || count($region) !== 4) return null;
        return ['extent' => 8192, 'line' => $line, 'region' => $region];
    }

    private function applyHumanRuleGeometry(array &$branch, mixed $changes): void
    {
        $geometry = $this->humanRuleGeometry($branch);
        if ($geometry === null || !is_array($changes) || array_diff(array_keys($changes), ['line', 'region']) !== []) {
            throw new InvalidArgumentException('Camera rule geometry is unavailable or invalid.');
        }
        foreach ($changes as $kind => $change) {
            if (!is_array($change) || !array_key_exists('expected', $change) || !array_key_exists('points', $change)
                || $change['expected'] !== $geometry[$kind]) {
                throw new InvalidArgumentException('Rule geometry changed on the camera; reload before saving.');
            }
            $points = $change['points'];
            if ($kind === 'line') {
                $keys = ['StartX', 'StartY', 'StopX', 'StopY'];
                if (!is_array($points) || array_keys($points) !== $keys) {
                    throw new InvalidArgumentException('Tripwire requires two endpoints.');
                }
                foreach ($points as $number) $this->validateRuleCoordinate($number);
                if ($points['StartX'] === $points['StopX'] && $points['StartY'] === $points['StopY']) {
                    throw new InvalidArgumentException('Tripwire endpoints must differ.');
                }
                $branch[0]['PedRule'][0]['RuleLine']['Pts'] = $points;
                continue;
            }
            if (!is_array($points) || count($points) !== 4 || array_keys($points) !== [0, 1, 2, 3]) {
                throw new InvalidArgumentException('Intrusion area requires four corners.');
            }
            foreach ($points as $point) {
                if (!is_array($point) || array_keys($point) !== ['X', 'Y']) {
                    throw new InvalidArgumentException('Intrusion corner is invalid.');
                }
                $this->validateRuleCoordinate($point['X']);
                $this->validateRuleCoordinate($point['Y']);
            }
            $sign = 0;
            for ($i = 0; $i < 4; $i++) {
                $a = $points[$i]; $b = $points[($i + 1) % 4]; $c = $points[($i + 2) % 4];
                $cross = ($b['X'] - $a['X']) * ($c['Y'] - $b['Y']) - ($b['Y'] - $a['Y']) * ($c['X'] - $b['X']);
                if ($cross === 0 || ($sign !== 0 && ($cross > 0 ? 1 : -1) !== $sign)) {
                    throw new InvalidArgumentException('Intrusion area must be a non-crossing four-sided shape.');
                }
                $sign = $cross > 0 ? 1 : -1;
            }
            $branch[0]['PedRule'][0]['RuleRegion']['Pts'] = $points;
        }
    }

    private function validateRuleCoordinate(mixed $number): void
    {
        if (!is_int($number) || $number < 0 || $number > 8192) {
            throw new InvalidArgumentException('Rule coordinate must be an integer from 0 to 8192.');
        }
    }

    private function model(string $id, array $definition, array $branch): array
    {
        $fields = [];
        $values = [];
        foreach ($definition['fields'] as $name => $field) {
            try {
                $value = $this->getPath($branch, $field['path']);
            } catch (DvripException $exception) {
                if ($field['optional'] ?? false) continue;
                throw $exception;
            }
            $public = $field;
            unset($public['optional'], $public['hexString']);
            unset($public['path']);
            unset($public['writable']);
            unset($public['byteMap']);
            unset($public['readMap']);
            unset($public['wireMap']);
            unset($public['strictOptions']);
            if ($field['hexString'] ?? false) {
                if (!is_string($value) || !preg_match('/^0x[0-9a-fA-F]{8}$/D', $value)) throw new DvripException('device_config_missing', 'Camera returned an invalid exposure time.');
                $values[$name] = (int) hexdec($value);
            } elseif (isset($field['readMap'][$value])) {
                $values[$name] = $field['readMap'][$value];
            } elseif ($field['type'] === 'password') {
                $values[$name] = $value;
            } elseif ($field['type'] === 'byte-string'
                && is_string($value)
                && strlen($value) === 1
                && ord($value) < 0x20
            ) {
                $code = ord($value);
                $values[$name] = $field['byteMap'][$code] ?? ('Code ' . $code);
                if (!in_array($values[$name], $public['options'] ?? [], true)) {
                    $public['options'][] = $values[$name];
                    if (isset($field['unknownLabel'])) {
                        $public['optionLabels'][$values[$name]] = $field['unknownLabel'];
                    }
                }
            } elseif ($field['type'] === 'ipv4-little-endian' && is_string($value)) {
                $values[$name] = self::decodeIpv4($value);
            } else {
                $values[$name] = $value;
            }
            $fields[$name] = $public;
        }
        return [
            'id' => $id,
            'label' => $definition['label'],
            'branch' => $definition['branch'],
            'warning' => $definition['warning'] ?? null,
            'fields' => $fields,
            'values' => $values,
        ];
    }

    private function validate(array $definition, array $input): array
    {
        $clean = [];
        foreach ($definition['fields'] as $name => $field) {
            if (!array_key_exists($name, $input)) {
                if ($field['optional'] ?? false) continue;
                throw new InvalidArgumentException("Field {$name} is required.");
            }
            $value = $input[$name];
            if ($field['type'] === 'boolean') {
                if (!is_bool($value)) throw new InvalidArgumentException("Field {$name} must be boolean.");
                $clean[$name] = $value;
                continue;
            }
            if ($field['type'] === 'boolean-integer') {
                if (!is_int($value) || !in_array($value, [0, 1], true)) {
                    throw new InvalidArgumentException("Field {$name} must be zero or one.");
                }
                $clean[$name] = $value;
                continue;
            }
            if ($field['type'] === 'integer') {
                if (!is_int($value) || $value < $field['min'] || $value > $field['max']) {
                    throw new InvalidArgumentException("Field {$name} is outside its allowed range.");
                }
                $clean[$name] = $value;
                continue;
            }
            if ($field['type'] === 'byte-string') {
                if (!is_string($value) || strlen($value) > 255) {
                    throw new InvalidArgumentException("Field {$name} must be a short string.");
                }
                $value = trim($value);
                if (preg_match('/^Code ([0-9]{1,3})$/D', $value, $match) === 1) {
                    $code = (int) $match[1];
                    if ($code < 0 || $code > 31) {
                        throw new InvalidArgumentException("Field {$name} has an invalid firmware code.");
                    }
                    $clean[$name] = chr($code);
                } else {
                    $clean[$name] = $value;
                }
                continue;
            }
            if ($field['type'] === 'ipv4-little-endian') {
                if (!is_string($value) || filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                    throw new InvalidArgumentException("Field {$name} must be an IPv4 address.");
                }
                $clean[$name] = self::encodeIpv4($value);
                continue;
            }
            if (!is_string($value) || strlen($value) > 255) {
                throw new InvalidArgumentException("Field {$name} must be a short string.");
            }
            $clean[$name] = $field['type'] === 'password' ? $value : trim($value);
            if (($field['strictOptions'] ?? false) === true
                && !in_array($clean[$name], $field['options'] ?? [], true)) {
                throw new InvalidArgumentException("Field {$name} has an unsupported value.");
            }
        }
        return $clean;
    }

    private function wifiMatchesInput(array $definition, array $model, array $input, array $clean): bool
    {
        foreach ($definition['fields'] as $name => $field) {
            $expected = $field['type'] === 'ipv4-little-endian' ? $input[$name] : $clean[$name];
            if (!array_key_exists($name, $model['values']) || $model['values'][$name] !== $expected) {
                return false;
            }
        }
        return true;
    }

    private function getPath(array $branch, array $path): mixed
    {
        $value = $branch;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                throw new DvripException('device_config_missing', 'Camera returned an incomplete configuration branch.');
            }
            $value = $value[$segment];
        }
        return $value;
    }

    private function setPath(array &$branch, array $path, mixed $value): void
    {
        $target = &$branch;
        foreach (array_slice($path, 0, -1) as $segment) {
            $target = &$target[$segment];
        }
        $target[$path[array_key_last($path)]] = $value;
    }

    private static function decodeIpv4(string $value): string
    {
        if (preg_match('/^0x[0-9A-Fa-f]{8}$/D', $value) !== 1) {
            throw new DvripException('device_config_missing', 'Camera returned an invalid IPv4 address.');
        }
        $packed = hex2bin(substr($value, 2));
        return implode('.', array_map('ord', str_split(strrev($packed))));
    }

    private static function encodeIpv4(string $value): string
    {
        $packed = inet_pton($value);
        if ($packed === false) {
            throw new InvalidArgumentException('Invalid IPv4 address.');
        }
        return '0x' . strtoupper(bin2hex(strrev($packed)));
    }
}
