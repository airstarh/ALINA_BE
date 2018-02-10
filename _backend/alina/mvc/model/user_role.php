<?php

namespace alina\mvc\model;

class user_role extends _BaseAlinaModel
{
    public $table = 'user_role';

    public function fields()
    {
        return [
            'id'      => [],
            'user_id' => [],
            'role_id' => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['user_id', 'role_id']
        ];
    }

    public function referencesTo()
    {
        return [
            'user' => [
                'has'             => 1,
                'mChildren'       => 'user',
                'mChildrenAlias'  => 'user',
                'refKeys'         => [
                    'refParentField'   => 'user_id',
                    'refChildrenField' => 'id',
                ],
                'childrenColumns' => ['firstname', 'lastname'],
            ],
            'role' => [
                'has'             => 1,
                'mChildren'       => 'role',
                'mChildrenAlias'  => 'role',
                'refKeys'         => [
                    'refParentField'   => 'role_id',
                    'refChildrenField' => 'id',
                ],
                'childrenColumns' => ['name', 'description'],
            ]
        ];
    }
}