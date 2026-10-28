<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/FileJob/FileJobFinalizer.php';

use alina\Services\FileJob\FileJobFinalizer;

$dir = sys_get_temp_dir() . '/alina-finalizer-' . bin2hex(random_bytes(4));
mkdir($dir, 0700, true);
$part = $dir . '/result.mp4.part';
$final = $dir . '/hash.mp4';
$source = $dir . '/source.webm.uploading';
file_put_contents($part, 'video');
file_put_contents($source, 'source-video');
$persisted = null;
$finalizer = new FileJobFinalizer(function (array $data) use (&$persisted): void { $persisted = $data; }, [$dir]);
$job = (object) [
    'owner_id'=>17,'entity_table'=>'tale','entity_id'=>8,'original_name'=>'clip.webm',
    'source_tmp'=>$source,'result_tmp'=>$part,'payload'=>json_encode(['final_path'=>$final,'name_fs'=>'hash.mp4','url_path'=>'/uploads/17/hash.mp4']),
];
try {
    $finalizer->finalize($job);
    if (!is_file($final) || is_file($part)) throw new RuntimeException('output must be atomically moved to final path');
    if (is_file($source)) throw new RuntimeException('successful finalization must remove the staged original');
    if (($persisted['name_human']??null)!=='clip.webm.mp4' || ($persisted['owner_id']??null)!==17) throw new RuntimeException('normal file metadata must preserve owner and original name');
    if (!is_int($persisted['created_at']??null) || $persisted['created_at'] < time()-2) throw new RuntimeException('final file timestamp must use completion time');
} finally { @unlink($source); @unlink($part); @unlink($final); @rmdir($dir); }

$dir = sys_get_temp_dir() . '/alina-finalizer-' . bin2hex(random_bytes(4));mkdir($dir,0700,true);
$part=$dir.'/result.part';$final=$dir.'/result.mp4';$source=$dir.'/source.uploading';file_put_contents($part,'video');file_put_contents($source,'source');$persisted=null;
try {
    $finalizer=new FileJobFinalizer(function(array $data)use(&$persisted):void{$persisted=$data;},[$dir]);
    $finalizer->finalize((object)['owner_id'=>17,'entity_table'=>'tale','entity_id'=>8,'original_name'=>'already.mp4','source_tmp'=>$source,'result_tmp'=>$part,'payload'=>json_encode(['final_path'=>$final,'name_fs'=>'result.mp4','url_path'=>'/result.mp4'])]);
    if (($persisted['name_human']??null)!=='already.mp4') throw new RuntimeException('existing MP4 name must not receive a duplicate extension');
} finally {@unlink($source);@unlink($part);@unlink($final);@rmdir($dir);}
echo "PASS: file job finalization\n";
