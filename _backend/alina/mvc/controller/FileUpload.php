<?php
// @link http://alinazero/egFileUpload
namespace alina\mvc\controller;

use alina\Message;
use alina\mvc\model\CurrentUser;
use alina\mvc\view\html as htmlAlias;
use alina\mvc\view\json as jsonView;
use alina\utils\FS;
use alina\utils\Request;
use Intervention\Image\ImageManager;

class FileUpload
{
    protected $resp;

    public function __construct()
    {
        AlinaRejectIfNotLoggedIn();
    }

    public function actionCommon()
    {
        $vd = NULL;
        if (Request::isPostPutDelete()) {
            $vd = $this->processUpload();
        }
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }

    public function actionCkEditor()
    {
        $resp = $this->processUpload();
        $vd   = (object)[
            'uploaded'    => $resp->uploaded,
            'fileName'    => $resp->uploaded ? $resp->fileName[0] : '',
            'newFileName' => $resp->uploaded ? $resp->newFileName[0] : '',
            'url'         => $resp->uploaded ? $resp->url[0] : '',
        ];
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }

    ##################################################
    #region Utils
    protected function processUpload()
    {
        #####
        $stateSuccess = FALSE;
        $this->resp   = (object)[
            'uploaded'    => 0,
            'fileName'    => [],
            'newFileName' => [],
            'url'         => [],
        ];
        #####
        if (CurrentUser::obj()->isLoggedIn()) {
            if (isset($_FILES[ALINA_FILE_UPLOAD_KEY])) {
                $FILE_CONTAINER       = $_FILES[ALINA_FILE_UPLOAD_KEY];
                $targetDir            = $this->destinationDir();
                $counterUploadedFiles = 0;
                foreach ($FILE_CONTAINER["error"] as $i => $error) {
                    if ($error == UPLOAD_ERR_OK) {
                        $sourceFileFullPath  = $FILE_CONTAINER["tmp_name"][$i];
                        $sourceFileCleanName = $FILE_CONTAINER["name"][$i];
                        $newFileCleanName    = md5_file($sourceFileFullPath);
                        $ext                 = FS::fileEXT($sourceFileCleanName);
                        #####
                        if (!$this->isExtAllowed($ext)) {
                            Message::setDanger("{$sourceFileCleanName} is not uploaded");
                            continue;
                        }
                        #####
                        $this->resp->fileName[]    = $sourceFileCleanName;
                        $this->resp->newFileName[] = $newFileName = "{$newFileCleanName}.{$ext}";
                        $targetFile                = FS::buildPathFromBlocks($targetDir, $newFileName);
                        $muf                       = move_uploaded_file($sourceFileFullPath, $targetFile);
                        if ($muf) {
                            #####
                            if ($this->isImage($targetFile)) {
                                $targetFile = $this->processImageCompression($targetFile);
                            }
                            #####
                            $webPath = $this->webPath($targetFile);
                            //Message::set("Uploaded: $webPath");
                            $this->resp->url[]    = $webPath;
                            $this->resp->uploaded = ++$counterUploadedFiles;
                            $stateSuccess         = TRUE;
                        }
                    }
                }
            }
        }
        #####
        #####
        if (!$stateSuccess) {
            AlinaResponseSuccess(0);
            Message::setDanger('Upload failed');
        }
        #####
        #####
        return $this->resp;
    }

    protected function processImageCompression($realPath)
    {
        $manager = new ImageManager(['driver' => 'imagick']);
        $image   = $manager
            ->make($realPath);
        if ($image->width() > 1000) {
            $image->widen(1000);
        }
        $image
            ->save($realPath);

        return $realPath;
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
        $res      = str_replace('\\', '/', $res);

        return $res;
    }

    protected function extOfImages()
    {
        return [
            'jpg',
            'jpeg',
            'bmp',
            'png',
            'webp',
            'gif',
        ];
    }

    protected function allowedExtensions()
    {
        return array_merge([], $this->extOfImages());
    }

    protected function isImage($sourcePath)
    {
        return in_array(FS::fileEXT($sourcePath), $this->extOfImages());
    }

    protected function isExtAllowed($ext)
    {
        return
            in_array(mb_strtolower($ext), $this->allowedExtensions());
    }

    #endregion Utils
    ##################################################
}
