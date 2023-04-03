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

use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;

class BoxService2023
{
    ##################################################
    #region FIELDS
    private array  $_appConfig;
    private        $_accessToken;
    private string $tokenDelimiter = '|||';
    private string $DIR_STATIC     = __DIR__ . '/static';
    private string $DIR_DYNAMIC    = __DIR__ . '/dynamic';
    #endregion FIELDS
    ##################################################
    #region INIT
    public function __construct()
    {
        $this->_appConfig   = $this->getBoxApiConfig();
        $this->_accessToken = $this->getAppUserAccessTokenFromBoxApi();
    }

    function getBoxApiConfig(): array
    {
        return require __DIR__ . '/config-box-api.php';
    }

    ##############################
    #region TOKEN
    function getAppUserAccessTokenFromBoxApi()
    {
        $boxApiConfig                           = $this->_appConfig; //$this->getBoxApiConfig();
        $boxApiConfig['claims']['sub']          = $boxApiConfig['claims']['sub_user'];
        $boxApiConfig['claims']['box_sub_type'] = 'user';

        return $this->retrieveAccessTokenFromBoxApi($boxApiConfig);
    }

    /**
     * Documentation:
     * Official Box API about JWT creation:
     * https://developer.box.com/guides/authentication/jwt/without-sdk/
     * StackOverFlow JWT discussion:
     * https://stackoverflow.com/a/45989059/6369072
     */
    function retrieveAccessTokenFromBoxApi($boxApiConfig = [])
    {
        error_log("retrieveAccessTokenFromBoxApi()", 0);
        //error_log("  config=".json_encode($boxApiConfig),0);
        $boxCfg = (isset($boxApiConfig) && !empty($boxApiConfig))
            ? $boxApiConfig
            : $this->getBoxApiConfig();
        // If current token is valid, return current token.
        $currentToken = $this->currentTokenIsValid($boxCfg['access_token_storage']);
        error_log("  token=" . json_encode($currentToken), 0);
        if ($currentToken) return $currentToken;
        ##############################
        #region Generate JWT
        $token = new Builder();
        // Header
        /*
        $token->setHeader('alg', $boxCfg['header']['alg']);
        $token->setHeader('typ', $boxCfg['header']['typ']);
        $token->setHeader('kid', $boxCfg['header']['kid']);
        */
        $token
            ->setIssuer($boxCfg['claims']['iss'])// Configures the issuer (iss claim)
            ->setAudience($boxCfg['claims']['aud'])// Configures the audience (aud claim)
            ->setId($boxCfg['claims']['jti'], TRUE)// Configures the id (jti claim), replicating as a header item
            ->setExpiration($boxCfg['claims']['exp'])// Configures the expiration time of the token (exp claim)
            ->set('sub', $boxCfg['claims']['sub'])// Configures a new claim, called "uid"
            ->set('box_sub_type', $boxCfg['claims']['box_sub_type'])// Configures a new claim, called "uid"
            ->set('alg', $boxCfg['header']['alg'])
            ->set('typ', $boxCfg['header']['typ'])
            ->set('kid', $boxCfg['header']['kid'])
            // Optional parameters. @link
            //->setIssuedAt($boxCfg['claims']['iat'])// Configures the time that the token was issue (iat claim)
            //->setNotBefore($boxCfg['claims']['nbf'])// Configures the time that the token can be used (nbf claim)
        ;
        // Signature (Private Key)
        $signer     = new Sha256();
        $privateKey = new Key($boxCfg['signature']['private_key'], $boxCfg['signature']['pass']);
        $token->sign($signer, $privateKey);
        $JWT = $token->getToken();
        #endregion Generate JWT
        ##############################
        #region Constructing the OAuth2 Request
        $url     = $boxCfg['oauth_request']['url'];
        $request = [
            'grant_type'    => $boxCfg['oauth_request']['grant_type'],
            'client_id'     => $boxCfg['oauth_request']['client_id'],
            'client_secret' => $boxCfg['oauth_request']['client_secret'],
            'assertion'     => $JWT,
        ];
        #endregion Constructing the OAuth2 Request
        ##############################
        #region Send request to Box API
        $response = $this->urlRequest($url, $request);
        $response = json_decode($response);
        if (isset($response->error) && !empty($response->error)) {
            error_log("  box login error {$response->error_description}", 0);
            throw new Exception("BoxAPI Error: " . $response->error_description);
        }
        $access_token = (isset($response->access_token) && !empty($response->access_token))
            ? $response->access_token
            : 'Not Defined';
        $expiresIn    = (isset($response->expires_in) && !empty($response->expires_in))
            ? $response->expires_in
            : 0;
        $expiresAt    = $expiresIn + time();
        // Write current token to file
        file_put_contents($boxCfg['access_token_storage'], $access_token);
        file_put_contents($boxCfg['access_token_storage'], $this->tokenDelimiter, FILE_APPEND);
        file_put_contents($boxCfg['access_token_storage'], $expiresAt, FILE_APPEND);
        #endregion Send request to Box API
        ##############################
        error_log("  new token=" . json_encode($response), 0);

        return $access_token;
    }

    function currentTokenIsValid($tokenStorage)
    {
        // Get expired-at time
        $tokenString = file_get_contents($tokenStorage);
        $tokenArray  = explode($this->tokenDelimiter, $tokenString);
        //error_log("  token=".json_encode($tokenArray),0);
        if (!isset($tokenArray[1]) || empty($tokenArray[1])) return FALSE;
        $expiresAt = $tokenArray[1];
        // Define validity
        $time = time();
        //error_log("  check expire={$expiresAt} now={$time}",0);
        if ($expiresAt <= ($time - 10)/*seconds**/) {
            //error_log("  expired",0);
            return FALSE;
        }

        return $tokenArray[0]; // current access token
    }

    function getAccessTokenHeader()
    {
        $accessToken = $this->_accessToken;

        return "Authorization: Bearer {$accessToken}";
    }

    function getAccessTokenHeaderEnterprise()
    {
        $accessToken = $this->getAccessTokenEnterprise();

        return "Authorization: Bearer {$accessToken}";
    }

    function getAccessTokenEnterprise()
    {
        $boxCfg      = $this->_appConfig; //$this->getBoxApiConfig();
        $tokenString = file_get_contents($boxCfg['access_token_storage_enterprise']);
        $tokenArray  = explode($this->tokenDelimiter, $tokenString);

        return $tokenArray[0];
    }

    function getEnterpriseAccessTokenFromBoxApi()
    {
        $boxApiConfig                           = $this->_appConfig;  //$this->getBoxApiConfig();
        $boxApiConfig['claims']['sub']          = $boxApiConfig['claims']['sub_enterprise'];
        $boxApiConfig['claims']['box_sub_type'] = 'enterprise';
        $boxApiConfig['access_token_storage']   = $boxApiConfig['access_token_storage_enterprise'];

        return $this->retrieveAccessTokenFromBoxApi($boxApiConfig);
    }
    #endregion TOKEN
    ##############################
    #endregion INIT
    ##################################################
    #region REQUEST
    function urlRequest($url, $post = [], $headers = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // POST
        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        // Set Headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    #endregion REQUEST
    ##################################################
    #region USER MANAGEMENT
    function createAppUser()
    {
        $this->getEnterpriseAccessTokenFromBoxApi();
        $url      = 'https://api.box.com/2.0/users';
        $user     = [
            'name'                    => 'arena_' . time(),
            'is_platform_access_only' => TRUE,
        ];
        $user     = json_encode($user);
        $response = $this->urlRequest($url, $user, [$this->getAccessTokenHeaderEnterprise()]);
        file_put_contents($this->DIR_DYNAMIC . '/app-users-storage', $response, FILE_APPEND);
        file_put_contents($this->DIR_DYNAMIC . '/app-users-storage', PHP_EOL . PHP_EOL, FILE_APPEND);
        echo $response;
    }
    #endregion USER MANAGEMENT
    ##################################################
    #region FOLDERS/FILES
    function getBoxFolderObject($boxFolderId = NULL)
    {
        /*
         *
         * curl https://api.box.com/2.0/folders/FOLDER_ID \
         * -H "Authorization: Bearer ACCESS_TOKEN"
         */
        $boxFolderId = $boxFolderId === NULL ? $this->_appConfig['folder_id'] : $boxFolderId;
        $url         = "https://api.box.com/2.0/folders/{$boxFolderId}";
        $response    = $this->urlRequest($url, [], [$this->getAccessTokenHeader()]);

        return $response;
    }

    function getFileSha1($path)
    {
        return sha1_file($path);
    }

    function uploadFileToBox($path, $boxFolderId = NULL)
    {
        error_log('uploadFileToBox path=' . $path, 0);
        /*
         *
         * curl https://upload.box.com/api/2.0/files/content \
         * -H "Authorization: Bearer ACCESS_TOKEN" -X POST \
         * -F attributes='{"name":"tigers.jpeg", "parent":{"id":"11446498"}}' \
         * -F file=@myfile.jpg
         */
        $boxFolderId = $boxFolderId === NULL ? $this->_appConfig['folder_id'] : $boxFolderId;
        $realPath    = realpath($path);
        if (!file_exists($realPath)) throw new Exception('No original file on server.');
        // Preparations
        $fileName = time() . '-' . basename($realPath);
        $fileSha1 = $this->getFileSha1($realPath);
        //$boxFolderObj = $this->getBoxFolderObject($boxFolderId); // Seems unnecessary
        $attributes = [
            'name'   => $fileName,
            'parent' => [
                'id' => $boxFolderId,
            ],
        ];
        // Build Request
        $url                 = 'https://upload.box.com/api/2.0/files/content';
        $headers[]           = $this->getAccessTokenHeader();
        $post['Content-MD5'] = $fileSha1;
        $post['attributes']  = json_encode($attributes);
        //$post['name'] = $fileName;
        //$post['parent'] = $boxFolderObj;
        //$post['id'] = $boxFolderId;
        //$post['file'] = '@'.$realPath;
        $post['file'] = new \CURLFile($realPath); // For PHP 5.6 only
        $response     = $this->urlRequest($url, $post, $headers);

        //error_log('  response='.json_encode($response), 0);
        return $response;
    }
    ##############################
    #region PREVIEW
    /**
     * @param object $fileObj : {file_id, box_id, fullPath}
     * @param string|null $boxFolderId
     * @return string
     * @throws Exception
     */
    function retrieveBoxPreviewUrl(object $fileObj, string $boxFolderId = NULL): string
    {
        // @file api/boxApi/access-token-storage
        //$this->getAppUserAccessTokenFromBoxApi();
        $boxFolderId = $boxFolderId === NULL ? $this->_appConfig['folder_id'] : $boxFolderId;
        // ToDo: It is possible to set a default 'Preview unavailable' URL;
        $embedLink             = '';
        $flagEmbedLinkReceived = FALSE;
        if (isset($fileObj->box_id) && !empty($fileObj->box_id)) {
            $boxFileId = $fileObj->box_id;
            $response  = $this->getBoxEmbedUrl($boxFileId);
            $response  = json_decode($response);
            if (isset($response->expiring_embed_link->url) && !empty($response->expiring_embed_link->url)) {
                $flagEmbedLinkReceived = TRUE;
                $embedLink             = $response->expiring_embed_link->url;
            }
        }
        //error_log(" link received? {$flagEmbedLinkReceived} link={$embedLink}", 0);
        // When there is no file in Box storage
        if (!$flagEmbedLinkReceived) {
            $path = $fileObj->fullPath;
            $file = $this->uploadFileToBox($path, $boxFolderId);
            $file = json_decode($file);
            if (!isset($file->entries[0]) || empty($file->entries[0]))
                throw new Exception('File preview failed.');
            $file      = $file->entries[0];
            $boxFileId = $file->id;
            $response  = $this->getBoxEmbedUrl($boxFileId);
            $response  = json_decode($response);
            if (!isset($response->expiring_embed_link->url) || empty($response->expiring_embed_link->url))
                throw new Exception('File preview failed.');
            $embedLink = $response->expiring_embed_link->url;
            if (isset($fileObj->file_id) && !empty($fileObj->file_id) && function_exists('dboUpdateByTableName')) {
                dboUpdateByTableName(
                    'file',
                    ['box_id' => $boxFileId],
                    'file_id',
                    $fileObj->file_id
                );
            }
        }

        return $embedLink;
    }

    function getBoxEmbedUrl($boxFileId)
    {
        /*
         *
         * curl https://api.box.com/2.0/files/FILE_ID?fields=expiring_embed_link \
         * -H "Authorization: Bearer ACCESS_TOKEN"
         *
         */
        $url       = "https://api.box.com/2.0/files/{$boxFileId}?fields=expiring_embed_link";
        $headers[] = $this->getAccessTokenHeader();
        $response  = $this->urlRequest($url, [], $headers);

        return $response;
    }

    #endregion PREVIEW
    ##############################
    #endregion FOLDERS/FILES
    ##################################################
}

