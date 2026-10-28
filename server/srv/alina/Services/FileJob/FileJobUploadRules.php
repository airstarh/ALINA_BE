<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

final class FileJobUploadRules
{
    private const VIDEO_EXTENSIONS=['mp4','webm','mov','m4v','avi','mkv','mpeg','mpg','ogv','3gp'];
    public static function isVideo(string $name,string $mime):bool
    {
        $mime=mb_strtolower(trim($mime));
        if (str_starts_with($mime,'audio/')) return false;
        return str_starts_with($mime,'video/') || in_array(self::safeExtension($name),self::VIDEO_EXTENSIONS,true);
    }
    public static function safeExtension(string $name):string
    {
        $extension=mb_strtolower((string)pathinfo($name,PATHINFO_EXTENSION));
        return preg_match('/^[a-z0-9]{1,10}$/D',$extension)?$extension:'bin';
    }
}
