<?php

namespace alina\mvc\Controller;
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
        AlinaRejectIfNotAdmin();
    }

    public function actionIndex()
    {
        if (isset($_GET['file']) && !empty($_GET['file'])) {
            $relativePath = $_GET['file'];
            $relativePath = trim($relativePath, "'");
            $relativePath = trim($relativePath, '"');
            // Preventive Validation
            $pathInfo = pathinfo($relativePath);
            if (!in_array($pathInfo['extension'], $this->allowedExtensions)) {
                return NULL;
            }
            $p = Alina()->resolvePath($relativePath);
            \alina\Utils\FS::giveFile($p);
        }
    }

    public function actionTestIt()
    {
        $p = 'alinaFileProxy/fullHtmlLayout.php';
        echo (new \alina\mvc\View\html)->piece($p);
    }
}
