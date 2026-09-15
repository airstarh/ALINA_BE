<?php

namespace alina\mvc\Model;

use alina\GlobalRequestStorage;

class file extends _BaseAlinaModel
{
    public $table       = 'file';
    public $sortDefault = [['name_human', 'ASC']];

    public function fields()
    {
        return [
            'id'           => [],
            'entity_id'    => [],
            'entity_table' => [],
            'name_fs'      => [],
            'name_human'   => [],
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
            'created_at' => [
                'default' => ALINA_TIME,
            ],
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

    public function bizDelete($id)
    {
        $this->getById($id);

        if (! AlinaAccessIfAdminOrModeratorOrOwner($this->attributes->owner_id)) {
            return 0;
        }

        $fList = (new static())->getAll(
            [
                ['name_fs', '=', $this->attributes->name_fs],
                ['owner_id', '=', $this->attributes->owner_id],
            ],
            null,
            2
        );

        $countLinksToThisFile = count($fList);

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
    #####
}
