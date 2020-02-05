<?php
// @link http://alinazero/egFileUpload
namespace alina\mvc\controller;

use alina\Message;
use alina\mvc\model\CurrentUser;
use alina\mvc\view\html as htmlAlias;
use alina\mvc\view\json as jsonView;
use alina\utils\FS;
use alina\utils\Request;

class FileUpload
{
    protected $responseData;

    public function actionCommon()
    {
        error_log('actionCommon',0);
        error_log(json_encode(Request::obj()->FILES),0);
        $vd = $this->processUpload();
        //        if ($processUpload) {
        //            $this->processFileModel();
        //        }
        if (Request::obj()->AJAX) {
            echo (new jsonView())->simpleRestApiResponse($vd);
        } else {
            echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
        }
    }
    ##################################################
    #region Utils
    protected function processUpload()
    {
        $this->responseData = (object)[
            'uploaded' => 0,
            'fileName' => 'no',
            'url'      => 'no',
        ];
        #####
        if (isset($_FILES[ALINA_FILE_UPLOAD_KEY])) {
            $FILE_CONTAINER       = $_FILES[ALINA_FILE_UPLOAD_KEY];
            $targetDir            = $this->destinationDir();
            $counterUploadedFiles = 0;
            $url                  = '';
            foreach ($FILE_CONTAINER["error"] as $i => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $sourceFileFullPath  = $FILE_CONTAINER["tmp_name"][$i];
                    $sourceFileCleanName = $FILE_CONTAINER["name"][$i];
                    $newFileCleanName    = md5_file($sourceFileFullPath);
                    $ext                 = FS::fileEXT($sourceFileCleanName);
                    $newFileName         = "{$newFileCleanName}.{$ext}";
                    $targetFile          = FS::buildPathFromBlocks($targetDir, $newFileName);
                    $muf                 = move_uploaded_file($sourceFileFullPath, $targetFile);
                    if ($muf) {
                        //Todo: SECURITY!!!
                        $webPath = $this->webPath($targetFile);
                        Message::set("Uploaded: $webPath");
                        $counterUploadedFiles++;
                        $url = $webPath;
                    }
                }
            }
            $this->responseData = (object)[
                'uploaded' => $counterUploadedFiles,
                'fileName' => $newFileName,
                'url'      => $url,
            ];
        }

        #####
        return $this->responseData;
    }

    protected function processFileModel()
    {

    }

    protected function destinationDir()
    {
        $blocks = [
            AlinaCFG('fileUploadDir'),
            CurrentUser::obj()->id ?: 0,
        ];
        $res    = FS::buildPathFromBlocks($blocks);
        FS::mkChainedDirIfNotExists($res);

        return $res;
    }

    protected function webPath($filePath)
    {
        $res      = '';
        $filePath = FS::normalizePath($filePath);
        $webPath  = FS::normalizePath(ALINA_WEB_PATH);
        $relPath  = str_replace($webPath, '', $filePath);
        $blocks   = [
            Request::obj()->DOMAIN,
            $relPath,
        ];
        $res      = '//' . FS::buildPathFromBlocks($blocks);

        return $res;
    }

    #endregion Utils
    ##################################################
}
