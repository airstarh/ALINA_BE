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

use alina\utils\HttpRequest;
use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;

class BoxService2023
{
    ##################################################
    #region FIELDS
    private object $cfg;
    private string $token;
    private string $DIR_STATIC  = __DIR__ . '/static';
    private string $DIR_DYNAMIC = __DIR__ . '/dynamic';
    #endregion FIELDS
    ##################################################
    #region EXAMPLE FROM OFFICIAL DOCUMENTATION
    /**
     * Basic example.
     * https://github.com/box-community/samples-docs-authenticate-with-jwt-api/blob/main/sample.php
     */
    public function egPrimitive()
    {
        $config     = require('config-box-api-2023.php');
        $privateKey = $config->boxAppSettings->appAuth->privateKey;
        $passphrase = $config->boxAppSettings->appAuth->passphrase;
        $key        = openssl_pkey_get_private($privateKey, $passphrase);
        // We will need the authenticationUrl  again later,
        // so it is handy to define here
        $authenticationUrl = 'https://api.box.com/oauth2/token';
        $claims            = [
            'iss'          => $config->boxAppSettings->clientID,
            #########
            'sub'          => $config->enterpriseID,
            'box_sub_type' => 'enterprise',
            ###
            //'sub'          => '270746149', // Odd user
            // 'sub'          => '271874469',
            // 'box_sub_type' => 'user',
            #########
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

        return json_decode($response);
    }

    static public function selfCheck(): array
    {
        $_this     = new static();
        $aFile     = __DIR__ . '/static/self-check.png';
        $aFileName = 'fc80d59877b4ae21911591b53664b2da1324cf25-PDF_1_PAGE.pdf';

        return [
            //'token'              => $_this->token,
            //'max_execution_time' => ini_get('max_execution_time'),
            //'upload'             => $_this->uploadFileToBox($aFile),
            'searchFileByName' => $_this->searchFileByName($aFileName),
        ];
    }
    #endregion EXAMPLE FROM OFFICIAL DOCUMENTATION
    ##################################################
    #region INIT
    public function __construct()
    {
        $this->cfg   = $this->getBoxApiConfig();
        $this->token = $this->getToken();
    }

    private function getBoxApiConfig()
    {
        return require('config-box-api-2023.php');
    }

    private function getToken()
    {
        if ($this->flagTokenExpired()) {
            return $this->requestNewToken();
        }
        else {
            return file_get_contents($this->cfg->accessTokenFile);
        }
    }

    private function flagTokenExpired(): bool
    {
        $tokenExpiresAt = (int)file_get_contents($this->cfg->accessTokenExpiresTimeFile);

        return $tokenExpiresAt < time() - 3;
    }

    private function requestNewToken()
    {
        $config            = $this->getBoxApiConfig();
        $privateKey        = $config->boxAppSettings->appAuth->privateKey;
        $passphrase        = $config->boxAppSettings->appAuth->passphrase;
        $key               = openssl_pkey_get_private($privateKey, $passphrase);
        $tokenExpiresAfter = time() + 45;
        #####
        # We will need the authenticationUrl  again later,
        # so it is handy to define here
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
            'exp'          => $tokenExpiresAfter,
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
        $data  = $response->getBody()->getContents();
        $data  = json_decode($data);
        $token = $data->access_token;
        #####
        #region STORE TOKEN
        file_put_contents($this->cfg->accessTokenFile, $token);
        file_put_contents($this->cfg->accessTokenExpiresTimeFile, $tokenExpiresAfter);
        #endregion STORE TOKEN
        #####
        return $token;
    }

    #endregion INIT
    ##################################################
    #region REQUEST
    private function getTokenHeader(): string
    {
        $accessToken = $this->token;

        return "Authorization: Bearer {$accessToken}";
    }

    #endregion REQUEST
    ##################################################
    #region FOLDERS/FILES
    function getFileSha1($path)
    {
        return sha1_file($path);
    }

    public function requestFileList($boxFolderId)
    {
        $url  = "https://api.box.com/2.0/folders/$boxFolderId/items";
        $http = new HttpRequest();
        $http->setReqUrl($url);
        $http->setReqMethod('GET');
        $http->addReqHeaders([$this->getTokenHeader()]);
        $res = $http->exe()->take('respBodyObject');

        return $res;
    }

    function requestFolder($boxFolderId = NULL)
    {
        $boxFolderId = $boxFolderId === NULL ? $this->cfg->folderId : $boxFolderId;
        $url         = "https://api.box.com/2.0/folders/{$boxFolderId}";
        $http        = new HttpRequest($url);
        $http->addReqHeaders([$this->getTokenHeader()]);
        $response = $http->exe()->take('respBodyObject');

        return $response;
    }

    public function requestDeleteFile($boxFileId)
    {
        $url  = "https://api.box.com/2.0/files/$boxFileId";
        $http = new HttpRequest();
        $http->setReqUrl($url);
        $http->setReqMethod('DELETE');
        $http->addReqHeaders([$this->getTokenHeader()]);
        $res = $http->exe()->take('respBodyObject');

        return $res;
    }

    public function requestDeleteAllFilesInFolder($boxFolderId)
    {
        $res    = [];
        $folder = $this->requestFolder($boxFolderId);
        foreach ($folder->item_collection->entries as $i => $v) {
            if ($v->type == 'file') {
                $res[] = $this->requestDeleteFile($v->id);
            }
        }

        return $res;
    }

    /**
     * Documentation:
     * https://developer.box.com/reference/get-search/#param-ancestor_folder_ids
     */
    public function searchFileByName($name, $folderId = NULL)
    {
        if ($folderId === NULL) $folderId = $this->cfg->folderId;
        $res  = NULL;
        $url  = 'https://api.box.com/2.0/search';
        $get  = [
            'query'               => $name,
            'type'                => 'file',
            'ancestor_folder_ids' => "$folderId",
            'limit'               => 1,
            'fields'              => 'name',
        ];
        $http = new HttpRequest();
        $http->setAttemptMax(1);
        $http->setReqUrl($url);
        $http->addReqGet($get);
        $http->addReqHeaders([$this->getTokenHeader()]);
        $file = $http->exe()->take('respBodyObject');
        #####
        if ($file) {
            if (property_exists($file, 'entries')) {
                if (isset($file->entries[0])) {
                    $res = $file;
                }
            }
        }

        #####
        return $res;
    }

    function uploadFileToBox($path, $boxFolderId = NULL)
    {
        $boxFolderId = $boxFolderId === NULL ? $this->cfg->folderId : $boxFolderId;
        $realPath    = realpath($path);
        if (!file_exists($realPath)) throw new Exception('No original file on server.');
        #####
        # Prepare. Generate File Name.
        $fileSha1 = $this->getFileSha1($realPath);
        //$fileName   = time() . '-' . basename($realPath);
        $fileName = $fileSha1 . '-' . basename($realPath);
        #####
        #region FILE EXISTS in Box Disk.
        $f = $this->searchFileByName($fileName, $boxFolderId);
        if ($f) {
            return $f;
        }
        #endregion FILE EXISTS in Box Disk.
        #####
        $attributes = [
            'name'   => $fileName,
            'parent' => [
                'id' => $boxFolderId,
            ],
        ];
        // Build Request
        $url                 = 'https://upload.box.com/api/2.0/files/content';
        $post['Content-MD5'] = $fileSha1;
        $post['attributes']  = json_encode($attributes);
        $post['parent_id']   = $boxFolderId;
        $post['file']        = new \CURLFile($realPath); // For PHP 5.6 only
        #####
        $http = new HttpRequest();
        $http->setAttemptMax(2);
        $http->setReqUrl($url);
        $http->addReqHeaders([$this->getTokenHeader()]);
        $http->setReqFields($post);
        $response = $http->exe()->take('respBodyObject');
        if (!$http->flagRespSuccess()) {
            throw new Exception(json_encode($response));
        }

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
    function requestPreview(object $fileObj, string $boxFolderId = NULL): string
    {
        $boxFolderId           = $boxFolderId === NULL ? $this->cfg->folderId : $boxFolderId;
        $embedLink             = '';
        $flagEmbedLinkReceived = FALSE;
        #####
        # When source file object has box_id.
        if (isset($fileObj->box_id) && !empty($fileObj->box_id)) {
            $boxFileId = $fileObj->box_id;
            $response  = $this->requestBoxEmbedUrl($boxFileId);
            $response  = json_decode($response);
            if (isset($response->expiring_embed_link->url) && !empty($response->expiring_embed_link->url)) {
                $flagEmbedLinkReceived = TRUE;
                $embedLink             = $response->expiring_embed_link->url;
            }
        }
        #####
        # When there is no box_id in the source file object.
        if (!$flagEmbedLinkReceived) {
            $path = $fileObj->fullPath;
            $file = $this->uploadFileToBox($path, $boxFolderId);
            if (!isset($file->entries[0]) || empty($file->entries[0]))
                throw new Exception('File preview failed.');
            $file      = $file->entries[0];
            $boxFileId = $file->id;
            $response  = $this->requestBoxEmbedUrl($boxFileId);
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

        #####
        return $embedLink;
    }

    private function requestBoxEmbedUrl($boxFileId)
    {
        $url  = "https://api.box.com/2.0/files/{$boxFileId}?fields=expiring_embed_link";
        $http = (new HttpRequest($url));
        $http->addReqHeaders([$this->getTokenHeader()]);
        $response = $http->exe()->take('respBodyObject');

        return $response;
    }

    #endregion PREVIEW
    ##############################
    #endregion FOLDERS/FILES
    ##################################################
}
