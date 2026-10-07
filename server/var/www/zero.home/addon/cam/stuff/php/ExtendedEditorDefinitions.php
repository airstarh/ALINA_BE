<?php

declare(strict_types=1);

namespace Cam;

final class ExtendedEditorDefinitions
{
    public static function get(): array
    {
        $definitions = [
            'auto_maintain' => self::definition('Automatic maintenance', 'General.AutoMaintain', [
                'deleteAfterDays' => self::integer('Delete files after (days)', ['AutoDeleteFilesDays'], 0, 3650),
                'rebootDay' => self::string('Automatic reboot day', ['AutoRebootDay']),
                'rebootHour' => self::integer('Automatic reboot hour', ['AutoRebootHour'], 0, 23),
            ], 'These settings can schedule an automatic camera reboot.'),
            'ptz_resume' => self::definition('PTZ resume state', 'General.ResumePtzState', [
                'enable' => self::boolean('Resume PTZ state', ['Enable']),
                'powerOffResume' => self::boolean('Resume power-off preset', ['PtzState', 0, 'PoweroffState', 'Enable']),
                'powerOffPreset' => self::integer('Power-off preset ID', ['PtzState', 0, 'PoweroffState', 'PresetID'], 0, 255),
                'presetResume' => self::boolean('Resume preset', ['PtzState', 0, 'PresetState', 'Enable']),
                'presetId' => self::integer('Preset ID', ['PtzState', 0, 'PresetState', 'PresetID'], 0, 255),
            ], 'Changing PTZ resume behavior may move a motorized camera after startup.'),
            'network_watchdog' => self::definition('Network connectivity watchdog', 'NetWork.CheckNetState', [
                'enable' => self::boolean('Connectivity watchdog', ['Enable']),
                'rebootCount' => self::integer('Reboot count', ['RebootCnt'], 0, 1000000),
            ], 'The connectivity watchdog may reboot the camera after repeated network failures.'),
            'ip_adaptive' => self::definition('Adaptive IP', 'NetWork.IPAdaptive', [
                'enable' => self::boolean('Adaptive IP', ['IPAdaptive']),
            ], 'Changing adaptive-IP behavior can affect how the camera obtains or retains its address.'),
            'network_order' => self::definition('Network priority', 'NetWork.NetOrder', [
                'enable' => self::boolean('Network priority', ['Enable']),
                'networkCount' => self::integer('Active network count', ['NetCount'], 0, 3),
                'firstType' => self::integer('First network type code', ['NetOrder', 0, 'NetType'], 0, 16),
                'firstOrder' => self::integer('First priority code', ['NetOrder', 0, 'NetOrder'], 0, 16),
                'secondType' => self::integer('Second network type code', ['NetOrder', 1, 'NetType'], 0, 16),
                'secondOrder' => self::integer('Second priority code', ['NetOrder', 1, 'NetOrder'], 0, 16),
                'thirdType' => self::integer('Third network type code', ['NetOrder', 2, 'NetType'], 0, 16),
                'thirdOrder' => self::integer('Third priority code', ['NetOrder', 2, 'NetOrder'], 0, 16),
            ], 'Changing network priority can affect connectivity.'),
        ];

        for ($index = 0; $index < 5; $index++) {
            $number = $index + 1;
            $definitions["alarm_server_{$number}"] = self::definition(
                "Alarm server {$number}",
                'NetWork.AlarmServer',
                [
                    'enable' => self::boolean("Alarm server {$number}", [$index, 'Enable']),
                    'sendAlarms' => self::boolean('Send alarms', [$index, 'Alarm']),
                    'sendLogs' => self::boolean('Send logs', [$index, 'Log']),
                    'protocol' => self::string('Protocol', [$index, 'Protocol']),
                    'server' => self::string('Server', [$index, 'Server', 'Name']),
                    'port' => self::integer('Port', [$index, 'Server', 'Port'], 1, 65535),
                    'username' => self::string('Username', [$index, 'Server', 'UserName']),
                    'password' => self::password('Password', [$index, 'Server', 'Password']),
                ],
                'Enabling this page sends alarm or log data to the configured server.'
            );
        }

        return $definitions + [
            'distortion' => self::definition('Lens distortion correction', 'Camera.DistortionCorrect', [
                'lensType' => self::integer('Lens type code', ['Lenstype'], 0, 32),
                'version' => self::integer('Configuration version', ['Version'], 0, 1000000),
            ], 'Image geometry may change immediately after saving.'),
            'fisheye' => self::definition('Fisheye parameters', 'Camera.FishLensParam', [
                'centerX' => self::integer('Center X', [0, 'CenterOffsetX'], 0, 8192),
                'centerY' => self::integer('Center Y', [0, 'CenterOffsetY'], 0, 8192),
                'radius' => self::integer('Lens radius', [0, 'Radius'], 0, 8192),
                'lensType' => self::integer('Lens type code', [0, 'LensType'], 0, 32),
                'viewAngle' => self::integer('View-angle code', [0, 'ViewAngle'], 0, 360),
                'viewMode' => self::integer('View-mode code', [0, 'ViewMode'], 0, 32),
                'zoom' => self::integer('Zoom', [0, 'Zoom'], 1, 1000),
            ], 'Incorrect lens geometry can severely distort the image.'),
            'image_basic' => self::definition('Image controls', 'Camera.Param', [
                'aeSensitivity' => self::integer('AE sensitivity', [0, 'AeSensitivity'], 0, 10),
                'dayNoiseFilter' => self::integer('Day noise-filter level', [0, 'Day_nfLevel'], 0, 10),
                'nightNoiseFilter' => self::integer('Night noise-filter level', [0, 'Night_nfLevel'], 0, 10),
                'dayNightThreshold' => self::integer('Day/night threshold', [0, 'DncThr'], 0, 100),
                'electronicLevel' => self::integer('Electronic level', [0, 'ElecLevel'], 0, 100),
                'gain' => self::integer('Gain', [0, 'GainParam', 'Gain'], 0, 100),
                'autoGain' => self::booleanInteger('Automatic gain', [0, 'GainParam', 'AutoGain']),
                'irCutMode' => self::integer('IR-cut mode code', [0, 'IRCUTMode'], 0, 16),
                'infraredSwap' => self::booleanInteger('Swap infrared', [0, 'InfraredSwap']),
                'irCutSwap' => self::booleanInteger('Swap IR-cut', [0, 'IrcutSwap']),
                'pictureFlip' => self::string('Picture flip code', [0, 'PictureFlip']),
                'pictureMirror' => self::string('Picture mirror code', [0, 'PictureMirror']),
                'whiteBalance' => self::string('White-balance code', [0, 'WhiteBalance']),
            ], 'These controls alter the live camera image.'),
            'image_advanced' => self::definition('Advanced image controls', 'Camera.ParamEx', [
                'autoAdjustment' => self::integer('Automatic adjustment code', [0, 'AutomaticAdjustment'], 0, 16),
                'corridorMode' => self::integer('Corridor mode code', [0, 'CorridorMode'], 0, 16),
                'distortionCorrection' => self::integer('Distortion-correction code', [0, 'Dis'], 0, 16),
                'lowLuxMode' => self::integer('Low-light mode code', [0, 'LowLuxMode'], 0, 16),
                'preventOverExposure' => self::integer('Prevent overexposure code', [0, 'PreventOverExpo'], 0, 16),
                'softPhotosensitive' => self::integer('Soft photosensitive control', [0, 'SoftPhotosensitivecontrol'], 0, 16),
                'style' => self::string('Image style', [0, 'Style']),
            ], 'These controls alter the live camera image.'),
            'motion_detection' => self::event('Motion detection', 'Detect.MotionDetect', [0], [
                'level' => self::integer('Sensitivity level', [0, 'Level'], 0, 10),
            ]),
            'blind_detection' => self::event('Video blind detection', 'Detect.BlindDetect', [0], [
                'level' => self::integer('Sensitivity level', [0, 'Level'], 0, 10),
            ]),
            'video_loss_detection' => self::event('Video-loss detection', 'Detect.LossDetect', [0]),
            'vehicle_detection' => self::event('Vehicle detection', 'Detect.CarShapeDetection', [0], [
                'level' => self::integer('Sensitivity level', [0, 'Level'], 0, 10),
                'loiterLatch' => self::integer('Loiter latch', [0, 'LoiterLatch'], 0, 3600),
            ]),
            'main_stream' => self::stream('Main video stream', 'MainFormat'),
            'extra_stream' => self::stream('Extra video stream', 'ExtraFormat'),
            'smart_h264' => self::definition('Smart H.264', 'AVEnc.SmartH264', [
                'enable' => self::boolean('Smart H.264', [0, 'SmartH264']),
            ], 'Encoding changes can interrupt or make video incompatible with MotionEye.'),
            'video_color_day' => self::videoColor('Video color profile 1', 0),
            'video_color_night' => self::videoColor('Video color profile 2', 1),
            'video_widget' => self::definition('Video title and widget', 'AVEnc.VideoWidget', [
                'channelName' => self::string('Channel name', [0, 'ChannelTitle', 'Name']),
                'channelSerial' => self::string('Channel serial', [0, 'ChannelTitle', 'SerialNo']),
                'channelEncodeBlend' => self::boolean('Encode channel title', [0, 'ChannelTitleAttribute', 'EncodeBlend']),
                'channelPreviewBlend' => self::boolean('Preview channel title', [0, 'ChannelTitleAttribute', 'PreviewBlend']),
                'timeEncodeBlend' => self::boolean('Encode time title', [0, 'TimeTitleAttribute', 'EncodeBlend']),
                'timePreviewBlend' => self::boolean('Preview time title', [0, 'TimeTitleAttribute', 'PreviewBlend']),
            ]),
            'snapshot' => self::definition('Snapshot recording', 'Storage.Snapshot', [
                'mode' => [
                    'label' => 'Snapshot mode', 'type' => 'string', 'path' => [0, 'SnapMode'],
                    'options' => ['ClosedSnap', 'ManualSnap', 'ConfigSnap'],
                    'optionLabels' => [
                        'ClosedSnap' => 'Off',
                        'ManualSnap' => 'Manual snapshots',
                        'ConfigSnap' => 'Configured / scheduled snapshots',
                    ],
                    'strictOptions' => true,
                ],
                'preSnapshot' => self::integer('Pre-snapshot count', [0, 'PreSnap'], 0, 100),
                'redundancy' => self::boolean('Redundant storage', [0, 'Redundancy']),
            ], 'Schedules and masks are preserved but are not editable on this page.'),
            'recording' => self::definition('Recording', 'Record', [
                'mode' => self::string('Recording mode', [0, 'RecordMode']),
                'packetLength' => self::integer('Packet length (minutes)', [0, 'PacketLength'], 1, 1440),
                'preRecord' => self::integer('Pre-record (seconds)', [0, 'PreRecord'], 0, 600),
                'redundancy' => self::boolean('Redundant recording', [0, 'Redundancy']),
            ], 'Recording schedules and channel masks are preserved but are not editable on this page.'),
            'storage_failure' => self::event('Storage-failure alarm', 'Storage.StorageFailure', []),
            'storage_low_space' => self::event('Low-storage alarm', 'Storage.StorageLowSpace', [], [
                'lowerLimit' => self::integer('Low-space threshold', ['LowerLimit'], 0, 100),
            ]),
            'storage_missing' => self::event('Storage-missing alarm', 'Storage.StorageNotExist', []),
            'alarm_output' => self::definition('Alarm output', 'Alarm.AlarmOut', [
                'status' => self::string('Output status', [0, 'AlarmOutStatus']),
                'type' => self::string('Output type', [0, 'AlarmOutType']),
            ], 'Changing alarm output may activate connected equipment.'),
            'local_alarm' => self::event('Local alarm input', 'Alarm.LocalAlarm', [0], [
                'sensorType' => self::string('Sensor type', [0, 'SensorType']),
            ]),
            'network_abort_alarm' => self::event('Network-disconnect alarm', 'Alarm.NetAbort', []),
            'network_alarm' => self::event('Network alarm input', 'Alarm.NetAlarm', [0]),
            'ip_conflict_alarm' => self::event('IP-conflict alarm', 'Alarm.NetIPConflict', []),
            'uart_comm_1' => self::serial('UART communication port', 'Uart.Comm', 0),
            'ptz_serial' => self::serial('PTZ serial port', 'Uart.PTZ', 0, true),
            'rs485' => self::serial('RS-485 port', 'Uart.RS485', 0, true),
        ];
    }

    private static function definition(string $label, string $branch, array $fields, ?string $warning = null): array
    {
        return ['label' => $label, 'branch' => $branch, 'warning' => $warning, 'fields' => $fields];
    }

    private static function event(string $label, string $branch, array $base, array $extra = []): array
    {
        $path = static fn (array $tail): array => [...$base, ...$tail];
        return self::definition($label, $branch, [
            'enable' => self::boolean($label, $path(['Enable'])),
            'log' => self::boolean('Write log', $path(['EventHandler', 'LogEnable'])),
            'mail' => self::boolean('Send email', $path(['EventHandler', 'MailEnable'])),
            'ftp' => self::boolean('Upload by FTP', $path(['EventHandler', 'FTPEnable'])),
            'snapshot' => self::boolean('Take snapshot', $path(['EventHandler', 'SnapEnable'])),
            'record' => self::boolean('Start recording', $path(['EventHandler', 'RecordEnable'])),
            'alarmOutput' => self::boolean('Activate alarm output', $path(['EventHandler', 'AlarmOutEnable'])),
            'beep' => self::boolean('Beep', $path(['EventHandler', 'BeepEnable'])),
            'showInfo' => self::boolean('Show on-screen information', $path(['EventHandler', 'ShowInfo'])),
            'tip' => self::boolean('Show event tip', $path(['EventHandler', 'TipEnable'])),
            ...$extra,
        ], 'Event schedules, masks, PTZ links, and region geometry are preserved but not editable here.');
    }

    private static function stream(string $label, string $format): array
    {
        $base = [0, $format];
        return self::definition($label, 'Simplify.Encode', [
            'videoEnable' => self::boolean('Video', [...$base, 'VideoEnable']),
            'audioEnable' => self::boolean('Audio', [...$base, 'AudioEnable']),
            'compression' => self::string('Codec', [...$base, 'Video', 'Compression']),
            'resolution' => self::string('Resolution', [...$base, 'Video', 'Resolution']),
            'fps' => self::integer('Frames per second', [...$base, 'Video', 'FPS'], 1, 120),
            'bitRate' => self::integer('Bit rate (kbps)', [...$base, 'Video', 'BitRate'], 16, 100000),
            'bitRateControl' => self::string('Bit-rate control', [...$base, 'Video', 'BitRateControl']),
            'quality' => self::integer('Quality code', [...$base, 'Video', 'Quality'], 0, 10),
            'gop' => self::integer('GOP', [...$base, 'Video', 'GOP'], 1, 300),
            'virtualGop' => self::integer('Virtual GOP', [...$base, 'Video', 'VirtualGOP'], 0, 300),
        ], 'Encoding changes can interrupt MotionEye or make the stream incompatible with existing clients.');
    }

    private static function videoColor(string $label, int $profile): array
    {
        $base = [0, $profile];
        $param = [...$base, 'VideoColorParam'];
        return self::definition($label, 'AVEnc.VideoColor', [
            'enable' => self::boolean($label, [...$base, 'Enable']),
            'timeSection' => self::string('Time section', [...$base, 'TimeSection']),
            'brightness' => self::integer('Brightness', [...$param, 'Brightness'], 0, 100),
            'contrast' => self::integer('Contrast', [...$param, 'Contrast'], 0, 100),
            'saturation' => self::integer('Saturation', [...$param, 'Saturation'], 0, 100),
            'hue' => self::integer('Hue', [...$param, 'Hue'], 0, 100),
            'gain' => self::integer('Gain', [...$param, 'Gain'], 0, 100),
            'sharpness' => self::integer('Sharpness / acutance', [...$param, 'Acutance'], 0, 8192),
            'whiteBalance' => self::integer('White balance', [...$param, 'Whitebalance'], 0, 255),
        ], 'Image-color changes are immediately visible in the stream.');
    }

    private static function serial(string $label, string $branch, int $index, bool $device = false): array
    {
        $fields = [
            'baudRate' => self::integer('Baud rate', [$index, 'Attribute', 0], 50, 4000000),
            'parity' => self::string('Parity', [$index, 'Attribute', 1]),
            'dataBits' => self::integer('Data bits', [$index, 'Attribute', 2], 5, 8),
            'stopBits' => self::integer('Stop bits', [$index, 'Attribute', 3], 1, 2),
            'portNumber' => self::integer('Port number', [$index, 'PortNo'], 0, 255),
            'protocol' => self::string('Protocol', [$index, 'ProtocolName']),
        ];
        if ($device) {
            $fields['deviceNumber'] = self::integer('Device number', [$index, 'DeviceNo'], 0, 255);
        }
        return self::definition($label, $branch, $fields, 'Incorrect serial parameters can interrupt attached PTZ or serial equipment.');
    }

    private static function boolean(string $label, array $path): array
    {
        return ['label' => $label, 'type' => 'boolean', 'path' => $path];
    }

    private static function booleanInteger(string $label, array $path): array
    {
        return ['label' => $label, 'type' => 'boolean-integer', 'path' => $path];
    }

    private static function integer(string $label, array $path, int $min, int $max): array
    {
        return ['label' => $label, 'type' => 'integer', 'min' => $min, 'max' => $max, 'path' => $path];
    }

    private static function string(string $label, array $path): array
    {
        return ['label' => $label, 'type' => 'string', 'path' => $path];
    }

    private static function password(string $label, array $path): array
    {
        return ['label' => $label, 'type' => 'password', 'path' => $path];
    }
}
