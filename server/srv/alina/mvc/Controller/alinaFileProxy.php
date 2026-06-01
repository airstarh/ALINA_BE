<?php

namespace alina\mvc\Controller;

use alina\mvc\View\html;
use alina\Utils\FS;
use function Alina;
use function AlinaRejectIfNotAdmin;

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
            FS::giveFile($p);
        }
    }

    public function actionTestIt()
    {
        $p = 'alinaFileProxy/fullHtmlLayout.php';
        echo (new html)->piece($p);
    }
}
