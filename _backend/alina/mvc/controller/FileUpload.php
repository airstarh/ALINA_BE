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
    protected $resp;

    public function actionCommon()
    {
        $vd = $this->processUpload();
        //        if ($processUpload) {
        //            $this->processFileModel();
        //        }
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }

    public function actionCkEditor()
    {
        $resp = $this->processUpload();
        $vd   = (object)[
            'uploaded'    => $resp->uploaded,
            'fileName'    => $resp->fileName[0],
            'newFileName' => $resp->newFileName[0],
            'url'         => $resp->url[0],
        ];
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }

    ##################################################
    #region Utils
    protected function processUpload()
    {
        $this->resp = (object)[
            'uploaded'    => 0,
            'fileName'    => [],
            'newFileName' => [],
            'url'         => [],
        ];
        #####
        if (isset($_FILES[ALINA_FILE_UPLOAD_KEY])) {
            $FILE_CONTAINER       = $_FILES[ALINA_FILE_UPLOAD_KEY];
            $targetDir            = $this->destinationDir();
            $counterUploadedFiles = 0;
            foreach ($FILE_CONTAINER["error"] as $i => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $sourceFileFullPath        = $FILE_CONTAINER["tmp_name"][$i];
                    $sourceFileCleanName       = $FILE_CONTAINER["name"][$i];
                    $newFileCleanName          = md5_file($sourceFileFullPath);
                    $ext                       = FS::fileEXT($sourceFileCleanName);
                    $this->resp->fileName[]    = $sourceFileCleanName;
                    $this->resp->newFileName[] = $newFileName = "{$newFileCleanName}.{$ext}";
                    $targetFile                = FS::buildPathFromBlocks($targetDir, $newFileName);
                    $muf                       = move_uploaded_file($sourceFileFullPath, $targetFile);
                    if ($muf) {
                        //Todo: SECURITY!!!
                        $webPath = $this->webPath($targetFile);
                        Message::set("Uploaded: $webPath");
                        $this->resp->url[]    = $webPath;
                        $this->resp->uploaded = ++$counterUploadedFiles;
                    }
                }
            }
        }

        #####
        return $this->resp;
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
        //ToDo: Attention! Slashes
        $res = str_replace('\\', '/', $res);

        return $res;
    }

    #endregion Utils
    ##################################################
}
