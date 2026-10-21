<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/php/DvripReader.php';
require_once dirname(__DIR__) . '/php/DvripException.php';
require_once dirname(__DIR__) . '/php/ExtendedEditorDefinitions.php';
require_once dirname(__DIR__) . '/php/NetworkServiceEditor.php';

function imageControlsFixture(): array
{
    $reader = new class implements Cam\DvripReader {
        public array $values = [];
        public function getConfig(string $command): mixed { return $this->values[$command]; }
        public function setConfig(string $command, mixed $value): bool { $this->values[$command] = $value; return true; }
        public function getCameraCapability(): array { return ['Count' => 2, 'Speeds' => ['0x00004E20', '0x0000208D']]; }
        public function getSystemInfo(): array { return []; }
        public function getInfo(string $command): mixed { return null; }
        public function getSystemFunction(): array { return []; }
        public function testMail(array $settings): bool { return true; }
        public function testFtp(array $settings): bool { return true; }
        public function reboot(): bool { return true; }
        public function close(): void {}
    };
    $reader->values['Camera.Param'] = [[
        'AeSensitivity' => 5, 'Day_nfLevel' => 3, 'Night_nfLevel' => 3,
        'DncThr' => 30, 'ElecLevel' => 50, 'GainParam' => ['Gain' => 50, 'AutoGain' => 0],
        'IRCUTMode' => 0, 'InfraredSwap' => 0, 'IrcutSwap' => 0,
        'PictureFlip' => '0x00000001', 'PictureMirror' => '0x00000001',
        'WhiteBalance' => '0x00000002', 'EsShutter' => '0x00000006',
        'DayNightColor' => '0x00000003', 'ApertureMode' => '0x00000000', 'BLCMode' => '0x00000000',
        'ExposureParam' => ['Level' => 0, 'LeastTime' => '0x00000100', 'MostTime' => '0x00010000'],
    ]];
    $reader->values['Camera.ParamEx'] = [[
        'AutomaticAdjustment' => 3, 'CorridorMode' => 0, 'Dis' => 0,
        'LowLuxMode' => 1, 'PreventOverExpo' => 0, 'SoftPhotosensitivecontrol' => 0,
        'Style' => 'type1', 'BroadTrends' => ['AutoGain' => 0, 'Gain' => 50],
    ]];
    return [new Cam\NetworkServiceEditor($reader), $reader];
}

test('image_controls_save_capability_exposure_and_preserve_orientation', function (): void {
    [$editor, $reader] = imageControlsFixture();
    $model = $editor->get('image_basic');
    assertSameValue([0, 1, 2], $model['fields']['exposureMode']['options'] ?? null);
    assertSameValue('Manual 1/50', $model['fields']['exposureMode']['optionLabels'][1]);
    assertSameValue(256, $model['values']['exposureMinimumUs']);
    assertSameValue('Star IR', $model['fields']['dayNightMode']['optionLabels']['0x00000000']);
    $input = $model['values'];
    $input['exposureMode'] = 1;
    $input['dayNightMode'] = '0x00000000';
    $input['autoIris'] = '0x00000001';
    $input['backlightCompensation'] = '0x00000001';
    $saved = $editor->update('image_basic', $input);
    assertSameValue('0x00000000', $reader->values['Camera.Param'][0]['ExposureParam']['LeastTime']);
    assertSameValue('0x00000000', $reader->values['Camera.Param'][0]['ExposureParam']['MostTime']);
    assertSameValue('0x00000001', $reader->values['Camera.Param'][0]['PictureFlip']);
    assertSameValue('0x00000001', $reader->values['Camera.Param'][0]['PictureMirror']);
    assertSameValue('0x00000001', $saved['values']['backlightCompensation']);
    assertSameValue([0, 1, 2], $saved['fields']['exposureMode']['options']);
});

test('advanced_image_controls_save_dwdr_separately_from_regular_gain', function (): void {
    [$editor, $reader] = imageControlsFixture();
    $model = $editor->get('image_advanced');
    assertSameValue([0, 1], $model['fields']['dwdr']['options'] ?? null);
    $input = $model['values'];
    $input['dwdr'] = 1;
    $input['dwdrLimit'] = 60;
    $saved = $editor->update('image_advanced', $input);
    assertSameValue(['AutoGain' => 1, 'Gain' => 60], $reader->values['Camera.ParamEx'][0]['BroadTrends']);
    assertSameValue(['Gain' => 50, 'AutoGain' => 0], $reader->values['Camera.Param'][0]['GainParam']);
    assertSameValue(60, $saved['values']['dwdrLimit']);
});

test('image_orientation_checkboxes_translate_to_camera_hex_flags', function (): void {
    [$editor, $reader] = imageControlsFixture();
    $model = $editor->get('image_basic');
    assertSameValue('boolean-integer', $model['fields']['pictureMirror']['type']);
    assertSameValue(1, $model['values']['pictureMirror']);
    assertSameValue(1, $model['values']['pictureFlip']);
    $input = $model['values'];
    $input['pictureMirror'] = 0;
    $input['pictureFlip'] = 0;
    $saved = $editor->update('image_basic', $input);
    assertSameValue('0x00000000', $reader->values['Camera.Param'][0]['PictureMirror']);
    assertSameValue('0x00000000', $reader->values['Camera.Param'][0]['PictureFlip']);
    assertSameValue(0, $saved['values']['pictureMirror']);
    assertSameValue(0, $saved['values']['pictureFlip']);
});
