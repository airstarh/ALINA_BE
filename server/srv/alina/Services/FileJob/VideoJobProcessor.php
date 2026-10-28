<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

use React\EventLoop\LoopInterface;
use RuntimeException;
use Throwable;

final class VideoJobProcessor implements FileJobProcessor
{
    public function __construct(private readonly VideoTranscoder $transcoder, private readonly LoopInterface $loop)
    {
    }

    public function start(object $job, callable $progress, callable $complete, callable $failure): void
    {
        if (($job->type ?? null) !== 'video_transcode') throw new RuntimeException('Unsupported file job type.');
        $source=(string)($job->source_tmp??'');$output=(string)($job->result_tmp??'');
        if (!is_file($source) || $output==='') throw new RuntimeException('Video source is missing.');

        try {
            $probe=$this->transcoder->probe($source);
            @unlink($output);
            $running=$this->transcoder->start($source,$output,(bool)$probe['hasAudio']);
        } catch (Throwable $error) { @unlink($output); $failure($error->getMessage()); return; }

        $stdout='';$stderr='';$lastProgress=0;$lastHeartbeat=microtime(true);
        $timer=$this->loop->addPeriodicTimer(0.25, function () use (&$timer,&$stdout,&$stderr,&$lastProgress,&$lastHeartbeat,$running,$probe,$output,$progress,$complete,$failure):void {
            $out=stream_get_contents($running['stdout']);$err=stream_get_contents($running['stderr']);
            if ($out!==false) $stdout.=$out;
            if ($err!==false) $stderr=mb_substr($stderr.$err,-8000);
            $next=$this->transcoder->progress($stdout,(float)$probe['duration'],$lastProgress);
            if ($next>$lastProgress && $next<100) { $lastProgress=$next; $lastHeartbeat=microtime(true); $progress($next); }
            elseif (microtime(true)-$lastHeartbeat>=20) { $lastHeartbeat=microtime(true); $progress($lastProgress); }
            $status=proc_get_status($running['resource']);
            if ($status['running']) return;
            $this->loop->cancelTimer($timer);
            $out=stream_get_contents($running['stdout']);$err=stream_get_contents($running['stderr']);
            if ($out!==false) $stdout.=$out;
            if ($err!==false) $stderr=mb_substr($stderr.$err,-8000);
            fclose($running['stdout']);fclose($running['stderr']);
            proc_close($running['resource']);
            if ((int)$status['exitcode']===0 && is_file($output) && filesize($output)>0) { $complete(); return; }
            @unlink($output);
            $failure($stderr!==''?$stderr:'Video converter exited unsuccessfully.');
        });
    }
}
