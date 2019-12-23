<?php

// @link http://alinazero/egFileUpload
namespace alina\mvc\controller;

use alina\Message;
use alina\mvc\model\CurrentUser;
use alina\mvc\view\html as htmlAlias;
use alina\utils\FS;

class FileUpload
{
    public function actionCommon()
    {
        $processUpload = $this->processUpload();
        if ($processUpload) {
            $this->processFileModel();
        }
        echo (new htmlAlias)->page(NULL, '_system/html/htmlLayoutMiddled.php');
    }
    ##################################################
    #region Utils
    protected function processUpload()
    {
        if (isset($_FILES[ALINA_FILE_UPLOAD_KEY])) {
            $FILECONTAINER = $_FILES[ALINA_FILE_UPLOAD_KEY];
            $targetDir     = $this->destinationDir();
            foreach ($FILECONTAINER["error"] as $i => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $sourceFileFullPath  = $FILECONTAINER["tmp_name"][$i];
                    $sourceFileCleanName = $FILECONTAINER["name"][$i];
                    $newFileCleanName    = md5_file($sourceFileFullPath);
                    $ext                 = FS::fileEXT($sourceFileCleanName);
                    $newFileName         = "{$newFileCleanName}.{$ext}";
                    $targetFile          = FS::buildPathFromBlocks($targetDir, $newFileName);
                    $muf                 = move_uploaded_file($sourceFileFullPath, $targetFile);
                    if ($muf) {
                        //Todo: SECURITY!!!
                        Message::set("Uploaded: {$targetFile}");
                    }
                }
            }

            return TRUE;
        }

        return FALSE;
    }

    protected function processFileModel()
    {

    }

    protected function destinationDir()
    {
        $blocks = [
            AlinaCFG('fileUploadDir'),
            CurrentUser::obj()->id,
        ];
        $res    = FS::buildPathFromBlocks($blocks);
        FS::mkChainedDirIfNotExists($res);

        return $res;
    }
    #endregion Utils
    ##################################################
}
