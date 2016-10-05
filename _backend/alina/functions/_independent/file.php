<?php

/**
 * Creates a Directory by chained path.
 * If path does not exist, creates the path too.
 * PHP mkdir() cannot create a subdirectory if upper directory does not exist.
 */
function mkChainedDirIfNotExists($fullPath) {
    $fullPath = normalPath($fullPath);

    $pathParts = explode(DIRECTORY_SEPARATOR, $fullPath);
    $chain = array();
    foreach ($pathParts as $dir) {
        if (empty($dir)) continue;
        $chain[] = $dir;
        $chainPath = implode(DIRECTORY_SEPARATOR, $chain);
        if (!is_dir($chainPath)) {
            mkdir($chainPath);
        }
    }

    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0777, true);
    }
}

/**
 * Path adaptation for Windows AND (*nix OR Mac).
 * Normalize path string for various path separators.
 */
function normalPath($path) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
    return $path;
}

/**
 * Remove even not empty directories.
 * PHP rmdir() cannot delete not empty directory.
 */
function rmDirCompletely($path) {
    foreach(scandir($path) as $file) {
        if ('.' === $file || '..' === $file) continue;
        $curPath = $path.DIRECTORY_SEPARATOR.$file;
        if (is_dir($curPath)) rmDirCompletely($curPath);
        else unlink($curPath);
    }
    rmdir($path);
}

/**
 * Check if file exists in a directory.
 * If yes: add microtime suffix to file name until name becomes unique.
 * @return string file name.
 */
function unifyFileName($dir, $fileName) {
    $dir = normalPath($dir);
    $uniqueFileName = $fileName;
    $repeat = TRUE;
    do {
        $dirFile = $dir.DIRECTORY_SEPARATOR.$uniqueFileName;
        if (file_exists($dirFile)) {

            // Build suffix
            list($usec, $sec) = explode(" ", microtime());
            $suffix = $sec;
            $suffix .= '-';
            $suffix .= str_replace(array('.',','), '', $usec);

            // Build new file name
            $fileParts = pathinfo($fileName);
            $newFileName  = '';
            $newFileName .= $fileParts['filename'];
            $newFileName .= '-';
            $newFileName .= $suffix;
            $newFileName .= (isset($fileParts['extension'])) ? '.'.$fileParts['extension'] : '';

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
function fileEXT($filePath) {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    return strtoupper($extension);
}
#end region Real file system

