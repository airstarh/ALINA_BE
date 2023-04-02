<?php
return [
    'folder_id'                       => 0,
    'access_token_storage'            => __DIR__ . '/dynamic/access-token-storage',
    'access_token_storage_enterprise' => __DIR__ . '/dynamic/access-token-storage-enterprise',
    'header'                          => [
        'alg' => 'RS256',    // The algorithm used to verify the signature. Values may only be set to: “RS256″, “RS384″, or “RS512.″
        'typ' => 'JWT',      // Type of token. Default is “JWT” to specify a JSON Web Token (JWT).
        'kid' => 'ejgrxup7', // Public Key ID
    ],
    // Claims Attributes
    'claims'                          => [
        'iss'            => 'yt193kvi5tm9jlmr6hpb4793wckn2qst', // The API key of the service that created the JWT assertion.
        // For Enterprise
        //'sub' => '911269', //enterprise_id for a token specific to an enterprise when creating and managing app users. app user_id for a token specific to an individual app user.
        //'box_sub_type' => 'enterprise', // “enterprise” or “user” depending on the ID that was passed in the sub claim.
        // For App User
        // App User id
        'sub'            => '271874469',
        'box_sub_type'   => 'user',
        // For the flexible switching between Enterprise and App User actions
        'sub_enterprise' => '911269',                           // Enterprise ID
        'sub_user'       => '271874469',                        // App User ID
        'aud'            => 'https://api.box.com/oauth2/token',
        'jti'            => base64_encode(random_bytes(32)), // A unique identifier specified by the client for this JWT. This is a unique string that is at least 16 characters and at most 128 characters.
        'exp'            => time() + 60,                     // The unix time as to when this JWT will expire. This can be set to a maximum value of 60 seconds beyond the issue time. Note: It is recommended to set this value to less than the maximum allowed 60 seconds.
        // Optional
        'iat'            => time(),                          // Issued at time. The token cannot be used before this time.
        'nbf'            => time() + 60,                     // Not before. Specifies when the token will start being valid.
    ],
    'signature'                       => [
        'public_key'  => file_get_contents(__DIR__ . '/static/public_key.pem'),
        'private_key' => file_get_contents(__DIR__ . '/static/private_key.pem'),
        'pass'        => 'qqqwwweee',
    ],
    'oauth_request'                   => [
        'url'           => 'https://api.box.com/oauth2/token',
        'grant_type'    => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'client_id'     => 'yt193kvi5tm9jlmr6hpb4793wckn2qst',
        'client_secret' => 'hrRMWdoCeQXOHaWDFzO7zUPmNqGVdill',
        'assertion'     => FALSE, // Parameter is set while runtime
    ],
];
