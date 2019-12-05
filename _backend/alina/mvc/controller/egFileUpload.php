<?php

// @link http://alinazero/egFileUpload
namespace alina\mvc\controller;

define('ALINA_FILE_UPLOAD_KEY', 'userfile');

use alina\message;

class FileUpload
{
    public function actionCommon()
    {

        // В PHP 4.1.0 и более ранних версиях следует использовать $HTTP_POST_FILES
        // вместо $_FILES.

        if (isset($_FILES[ALINA_FILE_UPLOAD_KEY])) {
            $fileUploadDir = AlinaCFG('fileUploadDir');
            foreach ($_FILES[ALINA_FILE_UPLOAD_KEY]["error"] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES[ALINA_FILE_UPLOAD_KEY]["tmp_name"][$key];
                    $uploadfile = \alina\utils\FS::buildPathFromBlocks($fileUploadDir, basename($_FILES[ALINA_FILE_UPLOAD_KEY]["name"][$key]));
                    $muf = move_uploaded_file($tmp_name, $uploadfile);
                    if ($muf) {
                        message::set("Uploaded: {$uploadfile}");
                    }
                }
            }

        }

        echo (new \alina\mvc\view\html)->page();
        return TRUE;
    }

    public function processUpload() {

    }
}
