<?php

declare(strict_types=1);

require_once __DIR__ . '/../Utils/Data.php';

use alina\Utils\Data;

function dataSanitizationExpectSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$payload = (object) [
    'password' => 'top-secret',
    'profile'  => (object) [
        'Password' => 'nested-secret',
        'name'     => 'Alice',
    ],
    'rows' => [
        [
            'confirm_password' => 'array-secret',
            'title'            => 'Keep me',
        ],
    ],
];

Data::sanitizeOutputObj($payload);

dataSanitizationExpectSame(false, property_exists($payload, 'password'), 'default output policy must remove a top-level secret');
dataSanitizationExpectSame(false, property_exists($payload->profile, 'Password'), 'sanitization must match nested field names case-insensitively');
dataSanitizationExpectSame(false, array_key_exists('confirm_password', $payload->rows[0]), 'sanitization must recurse into nested arrays');
dataSanitizationExpectSame('Alice', $payload->profile->name, 'sanitization must preserve unrelated object fields');
dataSanitizationExpectSame('Keep me', $payload->rows[0]['title'], 'sanitization must preserve unrelated array fields');

$untouched = (object) [
    'protected' => (object) [
        'password' => 'must-remain',
    ],
    'password' => 'must-be-removed',
];

Data::sanitizeOutputObj($untouched, ['PROTECTED']);

dataSanitizationExpectSame('must-remain', $untouched->protected->password, 'do-not-touch keys must preserve their complete subtree');
dataSanitizationExpectSame(false, property_exists($untouched, 'password'), 'do-not-touch must not disable sanitization of sibling fields');

$storagePayload = (object) [
    'POST' => (object) [
        'password'           => 'secret',
        'reset_code'         => 'reset-secret',
        'alina_form_db_pass' => 'database-secret',
        'inputPassword'      => 'external-api-secret',
        'caption'            => 'Public text',
    ],
    'FILES' => (object) [
        'upload' => (object) [
            'name'     => 'photo.jpg',
            'tmp_name' => '/tmp/php123',
            'size'     => 123,
        ],
    ],
];

Data::sanitizeOutputObj($storagePayload, [], Data::sensitiveStorageFields());

dataSanitizationExpectSame(false, property_exists($storagePayload->POST, 'password'), 'storage policy must remove passwords');
dataSanitizationExpectSame(false, property_exists($storagePayload->POST, 'reset_code'), 'storage policy must remove reset codes');
dataSanitizationExpectSame(false, property_exists($storagePayload->POST, 'alina_form_db_pass'), 'storage policy must remove database-manager passwords');
dataSanitizationExpectSame(false, property_exists($storagePayload->POST, 'inputPassword'), 'storage policy must remove REST tool passwords case-insensitively');
dataSanitizationExpectSame(false, property_exists($storagePayload->FILES->upload, 'tmp_name'), 'storage policy must remove temporary upload paths');
dataSanitizationExpectSame('Public text', $storagePayload->POST->caption, 'storage policy must preserve ordinary request data');
dataSanitizationExpectSame('photo.jpg', $storagePayload->FILES->upload->name, 'storage policy must preserve useful upload metadata');
dataSanitizationExpectSame(123, $storagePayload->FILES->upload->size, 'storage policy must preserve upload size');

$liveRequest = (object) [
    'POST' => (object) [
        'password' => 'controller-still-needs-this',
        'name'     => 'Alice',
    ],
];
$sanitizedStorageCopy = Data::sanitizeForStorage($liveRequest);

dataSanitizationExpectSame('controller-still-needs-this', $liveRequest->POST->password, 'storage sanitization must not mutate live request data');
dataSanitizationExpectSame(false, property_exists($sanitizedStorageCopy->POST, 'password'), 'storage sanitization must remove secrets from its detached copy');
dataSanitizationExpectSame('Alice', $sanitizedStorageCopy->POST->name, 'storage sanitization must preserve ordinary values in its detached copy');

echo "PASS: reusable recursive data sanitization\n";
