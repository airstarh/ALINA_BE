<?php

namespace alina\mvc\Controller;

use alina\Utils\FS;
use alina\Utils\Request;

class alinaFileProxy
{
    public $allowedExtensions = [
        'js',
        'css',
        'gif',
        'png',
        'jpq',
        'jpeg',
        'bmp',
    ];

    public function __construct()
    {
        \AlinaRejectIfNotAdmin();
    }

    /**
     * Outputs '' or file content
     */
    public function actionIndex()
    {
        $flagDo = true;
        $get    = (string) (Request::obj()->GET ?? '');

        if ($get->file === '') {
            $flagDo = false;
        }

        $relativePath = trim($get->file, "'\"");

        $pathInfo = pathinfo($relativePath);
        $ext      = $pathInfo['extension'] ?? '';

        if ($ext === '' || ! in_array($ext, $this->allowedExtensions, true)) {
            $flagDo = false;
        }

        $p = \Alina()->resolvePath($relativePath);

        $realPath    = realpath($p);
        $allowedBase = realpath(ALINA_WEB_PATH . '/uploads');

        if ($realPath === false || mb_strpos($realPath, $allowedBase) !== 0) {
            $flagDo = false;
        }

        if ($flagDo) {
            FS::giveFile($realPath);
            AlinaExit('file proxy');
        }

        AlinaEcho('');
    }
}
