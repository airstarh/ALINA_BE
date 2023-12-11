<?php

namespace alina\Utils;

use alina\mvc\Model\error_log;

class FS
{
    /**
     * Creates a Directory by chained path.
     * If path does not exist, creates the path too.
     * PHP mkdir() cannot create a subdirectory if upper directory does not exist.
     */
    static public function mkChainedDirIfNotExists($fullPath)
    {
        $fullPath  = static::normalizePath($fullPath);
        $pathParts = explode(DIRECTORY_SEPARATOR, $fullPath);
        //Sys::fDebug($pathParts);
        $chain              = [];
        $state_NIX_ABS_PATH = FALSE;
        foreach ($pathParts as $i => $dir) {
            if ($i === 0 && empty($dir)) {
                $state_NIX_ABS_PATH = TRUE;
            }
            if (empty($dir)) {
                continue;
            }
            $chain[]   = $dir;
            $chainPath = implode(DIRECTORY_SEPARATOR, $chain);
            if ($state_NIX_ABS_PATH) {
                $chainPath = DIRECTORY_SEPARATOR . $chainPath;
            }
            if (!is_dir($chainPath)) {
                mkdir($chainPath);
            }
        }
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0777, TRUE);
        }
    }

    /**
     * Path adaptation for Windows AND (*nix OR Mac).
     * Normalize path string for various path separators.
     */
    static public function normalizePath($path)
    {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        return $path;
    }

    /**
     * Remove even not empty directories.
     * PHP rmdir() cannot delete not empty directory.
     */
    static public function rmDirCompletely($path)
    {
        foreach (scandir($path) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            $curPath = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($curPath)) {
                static::rmDirCompletely($curPath);
            }
            else {
                unlink($curPath);
            }
        }
        rmdir($path);
    }

    /**
     * Check if file exists in a directory.
     * If yes: add microtime suffix to file name until name becomes unique.
     * @return string file name.
     */
    static public function unifyFileName($dir, $fileName)
    {
        $dir            = static::normalizePath($dir);
        $uniqueFileName = $fileName;
        $repeat         = TRUE;
        do {
            $dirFile = $dir . DIRECTORY_SEPARATOR . $uniqueFileName;
            if (file_exists($dirFile)) {
                // Build suffix
                [$usec, $sec] = explode(" ", microtime());
                $suffix = $sec;
                $suffix .= '-';
                $suffix .= str_replace(['.', ','], '', $usec);
                // Build new file name
                $fileParts      = pathinfo($fileName);
                $newFileName    = '';
                $newFileName    .= $fileParts['filename'];
                $newFileName    .= '-';
                $newFileName    .= $suffix;
                $newFileName    .= (isset($fileParts['extension'])) ? '.' . $fileParts['extension'] : '';
                $uniqueFileName = $newFileName;
            }
            else {
                $repeat = FALSE;
            }
        } while ($repeat);

        return $uniqueFileName;
    }

    /**
     * Retrieve file extension in upper case
     * or empty string '';
     */
    static public function fileEXT($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return strtolower($extension);
    }

    static public function mkFileIfNotExists($path)
    {
        $path = static::normalizePath($path);
        if (!file_exists($path)) {
            $pathInfo = pathinfo($path);
            $dir      = $pathInfo['dirname'];
            static::mkChainedDirIfNotExists($dir);
            if (FALSE === file_put_contents($path, NULL)) {
                throw new \Exception("Unable to create file {$pathInfo}");
            }
        }

        return realpath($path);
    }

    /**
     * @see buildClassNameFromBlocks
     */
    static public function buildPathFromBlocks()
    {
        $args   = func_get_args();
        $blocks = [];
        foreach ($args as $block) {
            if (is_array($block)) {
                $blocks = array_merge($blocks, $block);
            }
            else {
                $blocks[] = $block;
            }
        }
        $pp = [];
        foreach ($blocks as $i => $block) {
            $b = static::normalizePath($block);
            #####
            if ($i === 0) {
                $b = rtrim($b, DIRECTORY_SEPARATOR);
            }
            else {
                $b = trim($b, DIRECTORY_SEPARATOR);
            }
            #####
            if (empty($b)) {
                continue;
            }
            $pp[] = $b;
        }
        $path = implode(DIRECTORY_SEPARATOR, $pp);

        return $path;
    }

    static public function giveFile($realPath)
    {
        if (!file_exists($realPath)) {
            throw new \ErrorException("File {$realPath} does not exist.");
        }
        $pathInfo = pathinfo($realPath);
        $fileSize = filesize($realPath);
        $ext      = $pathInfo['extension'];
        $baseName = $pathInfo['basename'];
        $mimeObj  = new \Mimey\MimeTypes;
        $mimeType = $mimeObj->getMimeType($ext);
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $baseName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);
        readfile($realPath);
        exit;
    }

    static public function getCleanFileName($path)
    {
        $res = pathinfo($path, PATHINFO_FILENAME);
        if (empty($res)) {
            $res = FALSE;
        }

        return $res;
    }

    static public function getExtension($path)
    {
        $res = pathinfo($path, PATHINFO_EXTENSION);
        if (empty($res)) {
            $res = FALSE;
        }

        return $res;
    }

    static public function countFilesInDir($dir)
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $dir = rtrim($dir, '/');
        $dir = rtrim($dir, '\\');
        $dir = $dir . DIRECTORY_SEPARATOR . '*';
        $res = count(glob($dir));

        return $res;
    }

    static public function dirToRelativeUrlList($scan, $pathToRemove = NULL)
    {
        $log  = [];
        $scan = realpath($scan) . DIRECTORY_SEPARATOR . '*';
        if (empty($pathToRemove)) $pathToRemove = $_SERVER['DOCUMENT_ROOT'];
        $pathToRemove = realpath($pathToRemove);
        $list         = glob($scan);
        foreach ($list as $index => $item) {
            $source      = $item;
            $link        = $item;
            $header      = $item;
            $description = '';
            if (is_file($item)) {
                #####
                $link = $item;
                $link = str_replace($pathToRemove, '', $link);
                $link = str_replace('\\', '/', $link);
                #####
                $source = $item;
                $source = str_replace('\\', '/', $source);
                #####
                $header      = basename($link);
                $description = '';
                #####
                $content = file_get_contents($item);
                if (preg_match("'<h1>(.*?)</h1>'si", $content, $match)) {
                    $header = $match[1];
                }
                if (preg_match("'<section>(.*?)</section>'si", $content, $match)) {
                    $description = $match[1];
                }
                $log[$index] = [
                    'source'      => $source,
                    'link'        => $link,
                    'header'      => $header,
                    'description' => $description,
                ];
            }
            #####
        }

        return $log;
    }

    static public function dirToClassActionIndex($scan)
    {
        $log  = [];
        $scan = str_replace('\\', '/', $scan);
        $scan = $scan . '/' . '*';
        $list = glob($scan);
        foreach ($list as $index => $item) {
            #####
            # Defaults:
            $source      = $item;
            $header      = $item;
            $description = '';
            $ns          = '';
            $class       = '';
            $ns_class    = '';
            $methodList  = [];
            $url         = [];
            #####
            if (is_file($item)) {
                #####
                #####
                $source = $item;
                $source = str_replace('\\', '/', $source);
                #####
                $header      = basename($item);
                $description = '';
                #####
                $content = file_get_contents($item);
                if (preg_match("'<h1>(.*?)</h1>'si", $content, $match)) {
                    $header = $match[1];
                }
                if (preg_match("'<section>(.*?)</section>'si", $content, $match)) {
                    $description = $match[1];
                }
                if (preg_match("'namespace(.*?);'si", $content, $match)) {
                    $ns = $match[1];
                }
                if (preg_match("'class\s(.*?)[\s\n]'si", $content, $match)) {
                    $class = $match[1];
                }
                #####
                if ($class) {
                    $ns_class   = \alina\Utils\Resolver::buildClassNameFromBlocks($ns, $class);
                    $methodList = get_class_methods($ns_class);
                    foreach ($methodList as $i => $m) {
                        if (str_starts_with($m, 'action')) {
                            $path  = ltrim($m, 'action');
                            $url[] = "/$class/$path";
                        }
                    }
                }
                #####
                $log[$index] = [
                    'scan'        => $scan,
                    'source'      => $source,
                    'header'      => $header,
                    'description' => $description,
                    'ns'          => $ns,
                    'class'       => $class,
                    'ns_class'    => $ns_class,
                    'methodList'  => $methodList,
                    'url'         => $url,
                ];
            }
            #####
        }

        return $log;
    }
}
