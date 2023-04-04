<?php
return (object)[
    ##################################################
    #region LOCAL DEFINITIONS
    'accessTokenFile'           => __DIR__ . '/dynamic/access-token-storage',
    'accessTokenFileEnterprise' => __DIR__ . '/dynamic/access-token-storage-enterprise',
    /**
     * Some folder ID in Box Disk.
     * Get here (see URL path):
     * https://app.box.com/folder/0
     */
    'folder_id'                 => 0,
    #endregion LOCAL DEFINITIONS
    ##################################################
    #region AUTH
    /**
     * Documentation:
     * https://github.com/box-community/samples-docs-authenticate-with-jwt-api/blob/main/config.json.example
     */
    'boxAppSettings'            => (object)[
        /**
         * Get here:
         * https://app.box.com/master/custom-apps/configure/4853
         * or Get here:
         * https://app.box.com/developers/console/app/217558/configuration
         */
        'clientID'     => 'yt193kvi5tm9jlmr6hpb4793wckn2qst',
        /**
         * Get here (search on page "secret" and copy from hidden input box):
         * https://app.box.com/developers/console/app/217558/configuration
         *
         */
        'clientSecret' => 'hrRMWdoCeQXOHaWDFzO7zUPmNqGVdill',
        'appAuth'      => (object)[
            /**
             * Public Key ID (aka kid, aka public_key_id)
             * Get here:
             * https://app.box.com/developers/console/app/217558/configuration
             */
            'publicKeyID' => 'ejgrxup7',
            /**
             * Could be generated and copied only once on page below:
             * Get here:
             * https://app.box.com/developers/console/app/217558/configuration
             */
            'privateKey'  => file_get_contents(__DIR__ . '/static/private_key.pem'),
            /**
             * Get here: Box Developer Account password.
             */
            'passphrase'  => 'qqqwwweee',
        ],
        /**
         * Enterprise ID
         * Get here:
         * https://app.box.com/master/settings/accountBilling
         * or Get here:
         * https://app.box.com/developers/console/app/217558
         */
        'enterpriseID' => '911269',
    ],
    #endregion AUTH
    ##################################################
];
