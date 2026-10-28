<?php

namespace alina\mvc\Model;

use alina\GlobalRequestStorage;

class file extends _BaseAlinaModel
{
    public $table       = 'file';
    public $sortDefault = [['created_at', 'DESC'], ['id', 'DESC']];
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'           => [],
            'entity_id'    => [],
            'entity_table' => [],
            'name_fs'      => [],
            'name_human'   => [],
            'size'         => [],
            'url_path'     => [],
            'dir'          => [],
            'container'    => [
                'default' => 'FILE',
            ],
            'root_id' => [
                'default' => null,
            ],
            'parent_id' => [
                'default' => null,
            ],
            'level' => [
                'default' => 1,
            ],
            'owner_id' => [
                'default' => CurrentUser::obj()->id(),
            ],
            'created_at' => [],
            'order' => [
                'default' => 0,
            ],
        ];
    }

    #####
    public function uniqueKeys()
    {
        return [
            ['name_fs', 'owner_id', 'entity_id', 'entity_table'],
        ];
    }

    public function hookRightBeforeSave(&$dataArray)
    {
        $size = array_key_exists('size', $dataArray)
            ? $dataArray['size']
            : ($this->attributes->size ?? null);

        if ($size !== null && $size !== '') {
            return;
        }

        $path = $dataArray['dir'] ?? ($this->attributes->dir ?? null);

        if (! is_string($path) || ! is_file($path) || ! is_readable($path)) {
            return;
        }

        clearstatcache(true, $path);
        $size = filesize($path);

        if ($size !== false) {
            $dataArray['size'] = $size;
        }
    }

    #####
    public function delete(array $conditions)
    {
        $this->mode = self::MODE_DELETE;

        $list = $this
            ->q()
            ->where($conditions)
            ->get()
        ;

        foreach ($list as $f) {
            $path = $f->dir;

            if (file_exists($path)) {
                if (is_file($path)) {
                    unlink($path);
                }
            }

            (new static())->q()->where([['id', '=', $f->id]])->delete();
            $this->state_AFFECTED_ROWS++;
        }

        $this->resetFlags();

        return $this->state_AFFECTED_ROWS;
    }

    public function deleteOneAndUnlinkIfNoOtherLinks($id)
    {
        $this->getById($id);

        if (! AlinaAccessIfAdminOrModeratorOrOwner($this->attributes->owner_id)) {
            return 0;
        }

        $list = (new static())->getAll(
            [
                ['name_fs', '=', $this->attributes->name_fs],
                ['owner_id', '=', $this->attributes->owner_id],
            ],
            null,
            2
        );

        $countLinksToThisFile = count($list);

        $path = $this->attributes->dir;

        if ($countLinksToThisFile === 1) {
            if (file_exists($path)) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        (new static())->q()->where([['id','=', $id]])->delete();
        $this->state_AFFECTED_ROWS++;

        return true;
    }
}
