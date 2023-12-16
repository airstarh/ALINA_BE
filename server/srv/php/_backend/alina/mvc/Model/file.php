<?php

namespace alina\mvc\Model;

use alina\Message;
use alina\Utils\Request;

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
            'root_id'      => [
                'default' => NULL,
            ],
            'parent_id'    => [
                'default' => NULL,
            ],
            'level'        => [
                'default' => 1,
            ],
            'owner_id'     => [
                'default' => CurrentUser::obj()->id(),
            ],
            'created_at'   => [
                'default' => ALINA_TIME,
            ],
            'order'        => [
                'default' => 0,
            ],
        ];
    }

    #####
    public function uniqueKeys()
    {
        return [
            ['name_fs', 'owner_id'],
        ];
    }

    #####
    public function bizDelete($id)
    {
        $this->getById($id);
        if ($this->attributes->name_fs) {
            if (AlinaAccessIfAdminOrModeratorOrOwner($this->attributes->owner_id)) {
                $deletion = unlink($this->attributes->dir);
                if ($deletion) {
                    $this->deleteById($id);

                    return TRUE;
                }
            }
        }

        return FALSE;
    }
    #####
}
