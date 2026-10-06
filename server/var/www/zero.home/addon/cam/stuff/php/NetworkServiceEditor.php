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
            'warning' => 'This page changes basic human-detection behavior. Detection regions, rules, schedules, and alarm actions are preserved.',
            'fields' => [
                'enable' => ['label' => 'Human detection', 'type' => 'boolean', 'path' => [0, 'Enable']],
                'sensitivity' => ['label' => 'Sensitivity', 'type' => 'integer', 'min' => 0, 'max' => 10, 'path' => [0, 'Sensitivity']],
                'pushInterval' => ['label' => 'Push interval', 'type' => 'integer', 'min' => 0, 'max' => 3600000, 'path' => [0, 'PushInterval']],
                'showRule' => ['label' => 'Show detection rule', 'type' => 'boolean-integer', 'path' => [0, 'ShowRule']],
                'showTrack' => ['label' => 'Show tracking overlay', 'type' => 'boolean-integer', 'path' => [0, 'ShowTrack']],
                'objectType' => ['label' => 'Object type code', 'type' => 'integer', 'min' => 0, 'max' => 16, 'path' => [0, 'ObjectType']],
                'algorithm' => ['label' => 'Detection algorithm code', 'type' => 'integer', 'min' => 0, 'max' => 16, 'path' => [0, 'PedFdrAlg']],
            ],
        ],
    ];

    public function __construct(private DvripReader $reader)
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
        $model = $this->model($id, $definition, $this->readBranch($definition));
        $dhcpIndex = $this->dhcpIndex($id);
        return $dhcpIndex === null
            ? $model
            : $this->withDhcp($model, $this->readDhcpInterface($dhcpIndex), $dhcpIndex);
    }

    public function update(string $id, array $input): array
    {
        $definition = $this->definition($id);
        $dhcpIndex = $this->dhcpIndex($id);
        if ($dhcpIndex !== null && (!array_key_exists('dhcp', $input) || !is_bool($input['dhcp']))) {
            throw new InvalidArgumentException('Field dhcp must be boolean.');
        }
        $clean = $this->validate($definition, $input);
        $current = $this->readBranch($definition);
        $dhcp = $dhcpIndex === null ? null : $this->readDhcpInterface($dhcpIndex);
        $updated = $current;

        foreach ($definition['fields'] as $name => $field) {
            if (($field['writable'] ?? true) !== true) {
                continue;
            }
            $wireValue = $field['wireMap'][$clean[$name]] ?? $clean[$name];
            $this->setPath($updated, $field['path'], $wireValue);
        }

        $this->reader->setConfig($definition['branch'], $updated);
        $verified = $this->readBranch($definition);
        $model = $this->model($id, $definition, $verified);
        if ($id === 'wifi'
            ? !$this->wifiMatchesInput($definition, $model, $input, $clean)
            : $verified !== $updated
        ) {
            throw new DvripException(
                'write_verification_failed',
                'Configuration read-back did not match the submitted object.'
            );
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
            'snapshot', 'recording' => ($other['NOHDDRECORD'] ?? false) === true,
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

    private function readBranch(array $definition): array
    {
        $branch = $this->reader->getConfig($definition['branch']);
        if (!is_array($branch)) {
            throw new DvripException('device_config_missing', 'Camera returned an invalid configuration branch.');
        }
        foreach ($definition['fields'] as $field) {
            $this->getPath($branch, $field['path']);
        }
        return $branch;
    }

    private function model(string $id, array $definition, array $branch): array
    {
        $fields = [];
        $values = [];
        foreach ($definition['fields'] as $name => $field) {
            $value = $this->getPath($branch, $field['path']);
            $public = $field;
            unset($public['path']);
            unset($public['writable']);
            unset($public['byteMap']);
            unset($public['readMap']);
            unset($public['wireMap']);
            if (isset($field['readMap'][$value])) {
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
