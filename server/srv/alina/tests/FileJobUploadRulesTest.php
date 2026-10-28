<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/FileJob/FileJobUploadRules.php';

use alina\Services\FileJob\FileJobUploadRules;

function uploadRuleExpect(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
uploadRuleExpect(FileJobUploadRules::isVideo('clip.webm','application/octet-stream'),'known video extension must be detected');
uploadRuleExpect(FileJobUploadRules::isVideo('camera.bin','video/mp4'),'video MIME must be detected');
uploadRuleExpect(!FileJobUploadRules::isVideo('voice.webm','audio/webm'),'audio-only WebM must remain an ordinary upload');
uploadRuleExpect(!FileJobUploadRules::isVideo('image.png','image/png'),'ordinary image must not enter video queue');
uploadRuleExpect(FileJobUploadRules::safeExtension('My VIDEO.MOV')==='mov','extension must normalize');
uploadRuleExpect(FileJobUploadRules::safeExtension('no-extension')==='bin','missing extension must be safe');
echo "PASS: file job upload rules\n";
