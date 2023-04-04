<?php
/**
 * Box API Service 2023.
 *
 * Box API key pages:
 * Box API admin: https://app.box.com/master
 * Box API developer: https://app.box.com/developers/console
 * Box API disk: https://app.box.com/folder/0
 * Box API code samples. Consumer: https://github.com/box-community/samples-docs-authenticate-with-jwt-api/blob/main/sample.php
 * Box API code samples. Config: https://github.com/box-community/samples-docs-authenticate-with-jwt-api/blob/main/config.json.example
 *
 * JWT Documentation:
 * Official Box API about JWT creation:
 * https://developer.box.com/guides/authentication/jwt/without-sdk/
 * StackOverFlow JWT discussion:
 * https://stackoverflow.com/a/45989059/6369072
 *
 */

namespace alina\services\thirdPart\BoxApi;

use \Firebase\JWT\JWT;
use GuzzleHttp\Client;

class BoxService2023
{
    ##################################################
    #region FROM OFFICIAL DOCUMENTATION
    /**
     * Basic example.
     * https://github.com/box-community/samples-docs-authenticate-with-jwt-api/blob/main/sample.php
     */
    public function egPrimitive()
    {
        $config      = require('config-box-api-2023.php');
        $private_key = $config->boxAppSettings->appAuth->privateKey;
        $passphrase  = $config->boxAppSettings->appAuth->passphrase;
        $key         = openssl_pkey_get_private($private_key, $passphrase);
        // We will need the authenticationUrl  again later,
        // so it is handy to define here
        $authenticationUrl = 'https://api.box.com/oauth2/token';
        $claims            = [
            'iss'          => $config->boxAppSettings->clientID,
            'sub'          => $config->enterpriseID,
            'box_sub_type' => 'enterprise',
            'aud'          => $authenticationUrl,
            // This is an identifier that helps protect against
            // replay attacks
            'jti'          => base64_encode(random_bytes(64)),
            // We give the assertion a lifetime of 45 seconds
            // before it expires
            'exp'          => time() + 45,
            'kid'          => $config->boxAppSettings->appAuth->publicKeyID,
        ];
        // Rather than constructing the JWT assertion manually, we are
        // using the firebase/php-jwt library.
        // The API support "RS256", "RS384", and "RS512" encryption
        $assertion = JWT::encode($claims, $key, 'RS512');
        // We are using the excellent guzzlehttp/guzzle package
        // to simplify the API call
        $params = [
            'grant_type'    => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'     => $assertion,
            'client_id'     => $config->boxAppSettings->clientID,
            'client_secret' => $config->boxAppSettings->clientSecret,
        ];
        // Make the request
        $client   = new Client();
        $response = $client->request('POST', $authenticationUrl, [
            'form_params' => $params,
        ]);
        // Parse the JSON and extract the access token
        $data         = $response->getBody()->getContents();
        $access_token = json_decode($data)->access_token;
        // Folder 0 is the root folder for this account
        // and should be empty by default
        $response = $client->request('GET', 'https://api.box.com/2.0/folders/0', [
            'headers' => [
                'Authorization' => "Bearer {$access_token}",
            ],
        ])->getBody()->getContents();

        return $response;
    }
    #endregion FROM OFFICIAL DOCUMENTATION
    ##################################################
}
