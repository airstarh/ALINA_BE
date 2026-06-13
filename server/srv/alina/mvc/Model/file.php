<?php

namespace alina\mvc\Model;

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
    public function bizDelete($id)
    {
        $this->getById($id);

        $fList = (new static())->getAll(
            [
                ['name_human', '=', $this->attributes->name_human],
                ['owner_id', '=', $this->attributes->owner_id],
            ],
            null,
            2
        );

        $countLinksToThisFile = count($fList);

        if ($this->attributes->name_fs) {
            if (AlinaAccessIfAdminOrModeratorOrOwner($this->attributes->owner_id)) {
                $path = $this->attributes->dir;

                if (file_exists($path)) {
                    if (is_file($path)) {
                        if ($countLinksToThisFile === 1) {
                            unlink($path);
                        }
                    }
                }

                $this->deleteById($id);

                return true;
            }
        }

        return false;
    }
    #####
}
