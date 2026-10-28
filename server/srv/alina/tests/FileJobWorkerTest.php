<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Services/FileJob/FileJobStorage.php';
require_once __DIR__ . '/../Services/FileJob/FileJobProcessor.php';
require_once __DIR__ . '/../Services/FileJob/FileJobWorker.php';

use alina\Services\FileJob\FileJobProcessor;
use alina\Services\FileJob\FileJobStorage;
use alina\Services\FileJob\FileJobWorker;
use React\EventLoop\StreamSelectLoop;

final class WorkerMemoryStorage implements FileJobStorage
{
    public bool $secretValid = true;
    public ?object $job;
    public array $progress = [];
    public array $terminal = [];
    public function __construct() { $this->job=(object)['id'=>7,'public_id'=>'job-1','type'=>'video_transcode','attempts'=>1]; }
    public function authorizedSnapshot(string $publicId,int $userId,string $token,int $now):?array{return null;}
    public function snapshot(string $publicId):?array{return ['publicId'=>$publicId,'status'=>'processing'];}
    public function wakeSecretMatches(string $publicId,string $secret):bool{return $this->secretValid&&$publicId==='job-1'&&$secret==='secret';}
    public function claim(string $publicId,string $workerId,int $now,int $leaseSeconds):?object{$job=$this->job;$this->job=null;return $job;}
    public function claimNext(string $workerId,int $now,int $leaseSeconds):?object{return null;}
    public function updateProgress(int $jobId,string $workerId,int $progress,int $now,int $leaseSeconds):bool{$this->progress[]=$progress;return true;}
    public function complete(int $jobId,string $workerId,int $now):bool{$this->terminal[]='completed';return true;}
    public function retryOrFail(int $jobId,string $workerId,string $publicError,string $privateError,int $now,int $maxAttempts):string{$this->terminal[]='failed';return 'failed';}
}

final class ImmediateProcessor implements FileJobProcessor
{
    public bool $fail = false;
    public function start(object $job, callable $progress, callable $complete, callable $failure): void
    {
        $progress(38);
        $this->fail ? $failure('private failure') : $complete();
    }
}

function workerExpect(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}

$storage=new WorkerMemoryStorage();
$processor=new ImmediateProcessor();
$published=[];$finalized=[];
$publish = function (string $id) use (&$published): void { $published[] = $id; };
$finalize = function (object $job) use (&$finalized): void { $finalized[] = $job->id; };
$worker=new FileJobWorker($storage,$processor,new StreamSelectLoop(),$publish,$finalize,fn()=>1000,'worker-test');
workerExpect(!$worker->wake('job-1','wrong'),'invalid wake token must be rejected');
workerExpect($worker->wake('job-1','secret'),'valid wake token must be accepted');
workerExpect($storage->progress===[38],'worker must persist processor progress');
workerExpect($finalized===[7],'worker must finalize output before completion');
workerExpect($storage->terminal===['completed'],'successful job must complete');
workerExpect(count($published)>=2,'progress and completion must be published');

$failedStorage=new WorkerMemoryStorage();$failedProcessor=new ImmediateProcessor();$failedProcessor->fail=true;
$worker=new FileJobWorker($failedStorage,$failedProcessor,new StreamSelectLoop(),static function(){},static function(){},fn()=>1000,'worker-test');
workerExpect($worker->wake('job-1','secret'),'failing job wake must still be accepted');
workerExpect($failedStorage->terminal===['failed'],'processor failure must enter retry/failure policy');

echo "PASS: file job worker lifecycle\n";
