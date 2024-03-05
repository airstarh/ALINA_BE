<?php

class BorgTemplate
{
    public const PATH_TEMPLATE_STYLE  = BORG_PATH . '/template/html/css.php';
    public const PATH_TEMPLATE_SCRIPT = BORG_PATH . '/template/html/js.php';
    public const PATH_DIR_CSS         = BORG_PATH . '/fe/css';
    public const PATH_DIR_JS          = BORG_PATH . '/fe/js';

    ##################################################
    #region MAIN

    static public function run($path, $data)
    {
        ob_start();
        ob_implicit_flush(FALSE);
        require($path);
        $output = ob_get_clean();
        return $output;
    }

    static public function scanDirOnlyFiles($path)
    {
        $res     = [];
        $listDir = scandir($path);
        foreach ($listDir as $f) {
            if ($f === '.' || $f === '..') continue;
            $pathFile = "$path/$f";
            if (is_file($pathFile)) {
                $res[] = $pathFile;
            }
        }
        return $res;
    }
    #endregion MAIN
    ##################################################
    #region TAGS
    static public function tagStyle(array $fileList)
    {
        return static::run(static::PATH_TEMPLATE_STYLE, $fileList);
    }

    static public function tagScript(array $fileList)
    {
        return static::run(static::PATH_TEMPLATE_SCRIPT, $fileList);
    }

    static public function allCss()
    {
        $arrFiles = static::scanDirOnlyFiles(static::PATH_DIR_CSS);
        $res      = static::tagStyle($arrFiles);
        return $res;
    }

    static public function allJs()
    {
        $arrFiles = static::scanDirOnlyFiles(static::PATH_DIR_JS);
        $res      = static::tagScript($arrFiles);
        return $res;
    }

    static public function allContent($content)
    {
        return $content;
    }

    #endregion TAGS
    ##################################################
}