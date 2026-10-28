<?php

namespace alina\mvc\Controller;

use alina\Message;
use alina\mvc\Model\CurrentUser;
use alina\mvc\Model\file;
use alina\mvc\Model\tale;
use alina\mvc\Model\user;
use alina\mvc\View\html as htmlAlias;
use alina\Utils\Data;
use alina\Utils\FS;
use alina\Utils\Request;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

class FileUpload
{
    protected $resp;
    protected $respv2;
    protected $targetDir     = '';
    protected $max           = 0;
    protected $currentAmount = 0;
    protected $left          = 0;

    public function __construct()
    {
        //AlinaRejectIfNotLoggedIn();
    }

    public function actionIndex()
    {
        $vd = null;

        if (Request::obj()->isPostPutDelete()) {
            $resp = $this->processUpload();

            if ($resp) {
                $vd = $this->respv2;
            }
        }
        AlinaEcho((new htmlAlias())->page($vd));
    }

    public function actionCommon()
    {
        $vd = null;

        if (Request::obj()->isPostPutDelete()) {
            $vd = $this->processUpload();
        }
        AlinaEcho((new htmlAlias())->page($vd));
    }

    public function actionCkEditor()
    {
        $resp = $this->processUpload();
        $vd   = (object)[
            'uploaded'    => $resp->uploaded,
            'fileName'    => $resp->uploaded ? $resp->fileName[0] : '',
            'newFileName' => $resp->uploaded ? $resp->newFileName[0] : '',
            'url'         => $resp->uploaded ? $resp->url[0] : '',
        ];
        AlinaEcho((new htmlAlias())->page($vd));
    }

    public function actionDelete($id)
    {
        $res = false;

        if (Request::obj()->isPostPutDelete()) {
            $m        = new file();
            $deletion = $m->deleteOneAndUnlinkIfNoOtherLinks($id);

            if ($deletion) {
                $res = true;
            }
        }

        if ($res) {
            Message::setSuccess('Deleted');
        }
        else {
            Message::setDanger('Deletion failed');
        }
        AlinaEcho((new htmlAlias())->page());
    }

    public function actionDeleteBulk()
    {
        $deleted = 0;
        $ids = [];

        if (Request::obj()->isPostPutDelete()) {
            $postedIds = Request::obj()->POST->list ?? [];

            if (is_array($postedIds)) {
                $ids = array_values(array_unique(array_map('intval', $postedIds)));

                foreach ($ids as $id) {
                    if ($id > 0 && (new file())->deleteOneAndUnlinkIfNoOtherLinks($id)) {
                        $deleted++;
                    }
                }
            }
        }

        $success = count($ids) > 0 && $deleted === count($ids);
        AlinaResponseSuccess($success ? 1 : 0);

        if ($success) {
            Message::setSuccess('Deleted');
        }
        else {
            Message::setDanger('Deletion failed');
        }

        AlinaEcho((new htmlAlias())->page((object)[
            'requested' => count($ids),
            'deleted'   => $deleted,
        ]));
    }

    public function actionGetFiles($entity_table, $entity_id)
    {
        $m  = new file();
        $vd = $m->getAllWithReferences(
            [
                'entity_table' => $entity_table,
                'entity_id'    => $entity_id,
            ],
            [['order', 'ASC'], ['name_human', 'ASC']],
            1000
        );
        AlinaEcho((new htmlAlias())->page($vd));
    }

    public function actionGetFilesByOwner($ownerId)
    {
        AlinaRejectIfNotAdminOrModerator();
        $ownerId = (int) $ownerId;
        $files = (new file())->q()
            ->where('owner_id', '=', $ownerId)
            ->orderBy('entity_table', 'ASC')
            ->orderBy('root_id', 'DESC')
            ->orderBy('parent_id', 'DESC')
            ->orderBy('entity_id', 'DESC')
            ->orderBy('order', 'ASC')
            ->orderBy('name_human', 'ASC')
            ->get();

        $taleIds = $files
            ->filter(static fn ($file) => $file->entity_table === 'tale')
            ->pluck('entity_id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
        $tales = $taleIds
            ? (new tale())->q()->whereIn('id', $taleIds)->get()->keyBy('id')
            : [];

        $result = $files->map(static function ($file) use ($tales) {
            $sourceType = (string) $file->entity_table;
            $sourcePath = '';
            if ($sourceType === 'tale' && isset($tales[$file->entity_id])) {
                $source = $tales[$file->entity_id];
                $isComment = (int) $source->level > 0;
                $sourceType = $isComment ? 'comment' : 'tale';
                $rootId = $source->root_tale_id ?: $source->id;
                $sourcePath = "/tale/upsert/{$rootId}";
                if ($isComment) {
                    $answerId = $source->answer_to_tale_id ?: $source->id;
                    $sourcePath .= "?highlight={$source->id}&expand={$answerId}";
                }
            }
            elseif ($sourceType === 'user') {
                $sourcePath = "/auth/profile/{$file->entity_id}";
            }

            return (object)[
                'id'            => $file->id,
                'entity_id'     => $file->entity_id,
                'entity_table'  => $file->entity_table,
                'name_fs'       => $file->name_fs,
                'name_human'    => $file->name_human,
                'size'          => $file->size,
                'url_path'      => $file->url_path,
                'container'     => $file->container,
                'root_id'       => $file->root_id,
                'parent_id'     => $file->parent_id,
                'level'         => $file->level,
                'owner_id'      => $file->owner_id,
                'created_at'    => $file->created_at,
                'order'         => $file->order,
                'source_type'   => $sourceType,
                'source_path'   => $sourcePath,
            ];
        })->values()->toArray();

        AlinaEcho((new htmlAlias())->page($result));
    }

    ##################################################
    #region Utils
    protected function processUpload()
    {
        #####
        $stateSuccess = false;
        $this->respv2 = [];
        $this->resp   = (object)[
            'uploaded'    => 0,
            'fileName'    => [],
            'newFileName' => [],
            'url'         => [],
        ];

        #####
        if (CurrentUser::obj()->isLoggedIn()) {
            if (isset($_FILES[ALINA_FILE_UPLOAD_KEY])) {
                $FILE_CONTAINER = $_FILES[ALINA_FILE_UPLOAD_KEY];
                $targetDir      = $this->destinationDir();

                if (! $this->processWatch()) {
                    return $this->resp;
                }
                $counterUploadedFiles = 0;

                foreach ($FILE_CONTAINER["error"] as $i => $error) {
                    if ($error == UPLOAD_ERR_OK) {
                        $sourceFileFullPath  = $FILE_CONTAINER["tmp_name"][$i];
                        $sourceFileCleanName = $FILE_CONTAINER["name"][$i];
                        $newFileCleanName    = md5_file($sourceFileFullPath);
                        $ext                 = FS::fileEXT($sourceFileCleanName);

                        #####
                        if (! $this->isExtAllowed($ext)) {
                            Message::setDanger("%s is not uploaded", [$sourceFileCleanName]);
                            $stateSuccess = false;

                            continue;
                        }
                        #####
                        $this->resp->fileName[]    = $sourceFileCleanName;
                        $this->resp->newFileName[] = $newFileName = "{$newFileCleanName}.{$ext}";
                        $targetFile                = FS::buildPathFromBlocks($targetDir, $newFileName);
                        $muf                       = move_uploaded_file($sourceFileFullPath, $targetFile);

                        if ($muf) {
                            #####
                            if ($this->isImage($targetFile)) {
                                $targetFile = $this->processImageCompression($targetFile);
                            }
                            #####
                            $webPath              = $this->webPath($targetFile);
                            $this->resp->url[]    = $webPath;
                            $this->resp->uploaded = ++$counterUploadedFiles;
                            #####
                            $this->respv2[$i] = (object)[
                                'name_human' => $sourceFileCleanName,
                                'name_fs'    => $newFileName,
                                'url_path'   => $webPath,
                                'dir'        => $targetFile,
                            ];
                            $this->respv2[$i] = Data::mergeObjects($this->respv2[$i], Request::obj()->POST);
                            $this->respv2[$i] = $this->processFileModel($this->respv2[$i]);
                            Data::sanitizeOutputObj($this->respv2[$i]);
                            #####
                        }
                    }
                } //end foreach

                #####
                if ($counterUploadedFiles) {
                    $stateSuccess  = true;
                    $max           = $this->getMax();
                    $currentAmount = $this->getCurrentAmount();
                    $left          = $max == -1 ? 'Unlimited' : $max - $currentAmount;
                    $this->left    = $left;
                    //
                    // Message::setSuccess("Uploaded!");
                    // Message::setInfo("Already uploaded: %s.", [(string) $currentAmount]);
                    // Message::setInfo("Left to upload: %s.", [(string) $left]);

                    $arrTxt = [
                        ___p("Already uploaded: %s.", [(string) $currentAmount]),
                        ___p("Left to upload: %s.", [(string) $left]),
                    ];

                    $txt = implode(PHP_EOL, $arrTxt);

                    (new \alina\mvc\Model\notification())->insert((object)[
                    'to_id'        => CurrentUser::obj()->id(),
                    'from_id'      => CurrentUser::obj()->id(),
                    'txt'          => $txt,
                    'link'         => null, //$url,
                    'id_root'      => null, //$root_tale_id,
                    'id_answer'    => null, //$answer_to_tale_id,
                    'id_highlight' => null, //$ref_id,
                    'tbl'          => null, // $post->ref_table,
                    'bind_tbl'     => null, //'like',
                    'bind_id'      => null, //$mLike->id,
                ]);
                }
            }
        }

        #####
        if (! $stateSuccess) {
            AlinaResponseSuccess(0);
            Message::setDanger('Upload failed');
        }
        else {
            AlinaResponseSuccess(1);
        }

        #####
        return $this->resp;
    }

    protected function processWatch()
    {
        $targetDir = $this->destinationDir();
        $max       = $this->getMax();

        if ($max == -1) {
            return true;
        }
        $currentAmount = $this->getCurrentAmount($targetDir);

        if ($currentAmount >= $max) {
            Message::setDanger("File upload limit exceeded. Already uploaded %s files", [$currentAmount]);

            return false;
        }

        return true;
    }

    public function getMax($uid = null)
    {
        if (empty($uid)) {
            $CU = CurrentUser::obj();
        }
        else {
            $CU        = new user();
            $CU->id    = $uid;
            $CU->alias = "user_{$uid}";
        }
        $cfg = AlinaCfg('watcher/fileUpload/max');
        /*[
            'registered' => 10,
            'admin'      => 0,
            'moderator'  => 1000,
            'privileged' => 0,
        ];*/
        $max = 0;

        if ($CU->hasRole('privileged')) {
            $max = $cfg['privileged'];
        }
        elseif ($CU->hasRole('admin')) {
            $max = $cfg['admin'];
        }
        elseif ($CU->hasRole('moderator')) {
            $max = $cfg['moderator'];
        }
        elseif ($CU->hasRole('registered')) {
            $max = $cfg['registered'];
        }
        $this->max = $max;

        return $this->max;
    }

    protected function getCurrentAmount($targetDir = null)
    {
        if (empty($targetDir)) {
            $targetDir = $this->destinationDir();
        }
        $this->currentAmount = FS::countFilesInDir($targetDir);

        return $this->currentAmount;
    }

    protected function processImageCompression($realPath)
    {
        $manager = new ImageManager(new ImagickDriver());
        $image   = $manager->read($realPath);

        if ($image->width() > 1500) {
            $image->scaleDown(width: 1500);
        }

        $image->save($realPath, 100);

        return $realPath;
    }

    protected function processFileModel($rawObj)
    {
        $mFile = new file();
        $mFile->upsertByUniqueFields($rawObj);

        return $mFile->attributes;
    }

    protected function destinationDir($uid = null)
    {
        if (empty($uid)) {
            $uid = CurrentUser::obj()->id() ?: 0;
        }
        $blocks = [
            AlinaCfg('fileUploadDir'),
            $uid,
        ];
        $res = FS::buildPathFromBlocks($blocks);
        FS::mkChainedDirIfNotExists($res);
        $this->targetDir = $res;

        return $res;
    }

    protected function webPath($filePath)
    {
        $res      = '';
        $filePath = FS::normalizePath($filePath);
        $webPath  = FS::normalizePath(ALINA_WEB_PATH);
        $relPath  = str_replace($webPath, '', $filePath);
        $blocks   = [
            // Request::obj()->DOMAIN,
            $relPath,
        ];
        // $res = '//' . FS::buildPathFromBlocks($blocks);
        $res = FS::buildPathFromBlocks($blocks);
        $res = str_replace('\\', '/', $res);

        return $res;
    }

    protected function extOfImages()
    {
        return [
            'jpg',
            'jpeg',
            'bmp',
            'png',
            'webp',
            'gif',
        ];
    }

    protected function allowedExtensions()
    {
        $arr = [
            'pdf',
            'mp3',
            'wav',
            'ogg',
            'oga',
            'm4a',
            'aac',
            'flac',
            'mp4',
            'webm',
            'mov',
            'm4v',
            'doc',
            'docx',
        ];

        return array_merge($arr, $this->extOfImages());
    }

    protected function isImage($sourcePath)
    {
        return in_array(FS::fileEXT($sourcePath), $this->extOfImages());
    }

    protected function isExtAllowed($ext)
    {
        if (CurrentUser::obj()->isAdminOrModerator()) {
            return true;
        }

        return
            in_array(mb_strtolower($ext), $this->allowedExtensions());
    }

    #endregion Utils
    ##################################################
}
