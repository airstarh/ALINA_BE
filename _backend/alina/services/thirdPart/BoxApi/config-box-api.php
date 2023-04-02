<?php
return [
    'access_token_storage'            => __DIR__ . '/dynamic/access-token-storage',
    'access_token_storage_enterprise' => __DIR__ . '/dynamic/access-token-storage-enterprise',
    /**
     * Get here:
     * ToDo...
     */
    'folder_id'                       => 0,
    ##################################################
    #region Header
    'header'                          => [
        /**
         * The algorithm used to verify the signature. Values may only be set to: “RS256″, “RS384″, or “RS512.″
         * Get here:
         * ToDo...
         */
        'alg' => 'RS256',
        /**
         * Type of token. Default is “JWT” to specify a JSON Web Token (JWT).
         * Get here:
         * ToDo...
         */
        'typ' => 'JWT',
        /**
         * Public Key ID
         * Get here:
         * https://app.box.com/developers/console/app/217558/configuration
         */
        'kid' => 'ejgrxup7',
    ],
    #endregion Header
    ##################################################
    #region CLAIMS
    /**
     * Claims Attributes
     */
    'claims'                          => [
        /**
         * The API key of the service that created the JWT assertion.
         * Get here:
         * https://app.box.com/developers/console/app/217558/configuration
         */
        'iss'            => 'yt193kvi5tm9jlmr6hpb4793wckn2qst',
        /**
         * For Enterprise
         */
        //'sub' => '911269', //enterprise_id for a token specific to an enterprise when creating and managing app users. app user_id for a token specific to an individual app user.
        //'box_sub_type' => 'enterprise', // “enterprise” or “user” depending on the ID that was passed in the sub claim.
        // For App User
        /**
         * App User id
         * Get here:
         * ToDo...
         */
        'sub'            => '271874469',
        /**
         * Get here:
         * ToDo...
         */
        'box_sub_type'   => 'user',
        // For the flexible switching between Enterprise and App User actions
        /**
         * Enterprise ID
         * Get here:
         * https://app.box.com/master/settings/accountBilling
         * or Get here:
         * https://app.box.com/developers/console/app/217558
         */
        'sub_enterprise' => '911269',
        /**
         * App User ID
         * Get here:
         * ToDo...
         */
        'sub_user'       => '271874469',
        /**
         * Get here:
         * ToDo...
         */
        'aud'            => 'https://api.box.com/oauth2/token',
        /**
         * A unique identifier specified by the client for this JWT. This is a unique string that is at least 16 characters and at most 128 characters.
         * Get here:
         * ToDo...
         */
        'jti'            => base64_encode(random_bytes(32)),
        /**
         * The unix time as to when this JWT will expire. This can be set to a maximum value of 60 seconds beyond the issue time. Note: It is recommended to set this value to less than the maximum allowed 60 seconds.
         */
        'exp'            => time() + 60,
        ##############################
        #region OPTIONAL
        /**
         * Issued at time. The token cannot be used before this time.
         */
        'iat'            => time(),
        /**
         * Not before. Specifies when the token will start being valid.
         */
        'nbf'            => time() + 60,
        #endregion OPTIONAL
        ##############################
    ],
    #endregion CLAIMS
    ##################################################
    #region SIGNATURE
    'signature'                       => [
        /**
         * Get here:
         * https://app.box.com/developers/console/app/217558/configuration
         */
        'public_key'  => file_get_contents(__DIR__ . '/static/public_key.pem'),
        /**
         * Could be generated and copied only once on page below:
         * Get here:
         * https://app.box.com/developers/console/app/217558/configuration
         */
        'private_key' => file_get_contents(__DIR__ . '/static/private_key.pem'),
        /**
         * Get here: Box Developer Account pass.
         */
        'pass'        => 'qqqwwweee',
    ],
    #endregion SIGNATURE
    ##################################################
    #region REQUEST
    'oauth_request'                   => [
        /**
         * Get here:
         * ToDo...
         */
        'url'           => 'https://api.box.com/oauth2/token',
        /**
         * Get here:
         * ToDo...
         */
        'grant_type'    => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        /**
         * Get here:
         * https://app.box.com/master/custom-apps/configure/4853
         * or Get here:
         * https://app.box.com/developers/console/app/217558/configuration
         */
        'client_id'     => 'yt193kvi5tm9jlmr6hpb4793wckn2qst',
        /**
         * Get here (search on page "secret" and copy from hidden input box):
         * https://app.box.com/developers/console/app/217558/configuration
         *
         */
        'client_secret' => 'hrRMWdoCeQXOHaWDFzO7zUPmNqGVdill',
        /**
         * Parameter is set while runtime
         * Get here:
         * ToDo...
         */
        'assertion'     => FALSE,
    ],
    #endregion REQUEST
    ##################################################
];
