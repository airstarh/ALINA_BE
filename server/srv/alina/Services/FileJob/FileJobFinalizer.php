<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

use alina\mvc\Model\file;
use Closure;
use RuntimeException;
use Throwable;

final class FileJobFinalizer
{
    private Closure $persist;

    public function __construct(?callable $persist = null, private readonly array $allowedRoots = ['/var/www'])
    {
        $this->persist = Closure::fromCallable($persist ?? static function (array $data): void {
            (new file())->upsertByUniqueFields($data);
        });
    }

    public function finalize(object $job): void
    {
        $payload = json_decode((string)($job->payload ?? ''), true);
        if (!is_array($payload)) throw new RuntimeException('File job payload is invalid.');
        $source = (string)($job->result_tmp ?? '');
        $target = (string)($payload['final_path'] ?? '');
        if (!is_file($source) || filesize($source) === 0) throw new RuntimeException('Converted video is missing.');
        $this->assertAllowedTarget($target);
        if (file_exists($target)) throw new RuntimeException('Final video already exists.');
        if (!rename($source,$target)) throw new RuntimeException('Unable to finalize converted video.');

        try {
            $originalName=(string)$job->original_name;
            $humanName=str_ends_with(mb_strtolower($originalName),'.mp4')?$originalName:$originalName.'.mp4';
            ($this->persist)([
                'entity_id'=>(int)$job->entity_id,
                'entity_table'=>(string)$job->entity_table,
                'name_fs'=>(string)($payload['name_fs']??basename($target)),
                'name_human'=>$humanName,
                'size'=>filesize($target) ?: null,
                'url_path'=>(string)($payload['url_path']??''),
                'dir'=>$target,
                'owner_id'=>(int)$job->owner_id,
                'container'=>'FILE',
                'created_at'=>time(),
            ]);
            $stagedSource = (string)($job->source_tmp ?? '');
            if ($stagedSource !== '' && is_file($stagedSource)) {
                @unlink($stagedSource);
            }
        } catch (Throwable $error) {
            @rename($target,$source);
            throw $error;
        }
    }

    private function assertAllowedTarget(string $target): void
    {
        $parent = realpath(dirname($target));
        if ($target==='' || $parent===false) throw new RuntimeException('Final video path is invalid.');
        foreach ($this->allowedRoots as $root) {
            $realRoot=realpath($root);
            if ($realRoot!==false && ($parent===$realRoot || str_starts_with($parent,$realRoot.DIRECTORY_SEPARATOR))) return;
        }
        throw new RuntimeException('Final video path is outside allowed storage.');
    }
}
