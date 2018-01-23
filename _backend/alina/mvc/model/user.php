<?php

namespace alina\mvc\model;

class user extends _baseAlinaEloquentModel
{
    public $table = 'user';

    public function fields()
    {
        return [
            'id'        => [],
            'mail'      => [],
            'firstname' => [],
            'lastname'  => [],
            'active'    => [],
            'verified'  => [],
            'created'   => [],
            'lastenter' => [],
            'picture'   => [],
            'timezone'  => [],
            'password'  => [],
        ];
    }

    public function uniqueKeys()
    {
        return [
            ['mail']
        ];
    }

    public function referencesTo()
    {
        return [
            'timezone' => [
                'has'             => 1,
                'mChildren'       => 'timezone',
                'mChildrenAlias'  => 'timezone',
                'refKeys'         => [
                    'refParentField'   => 'timezone',
                    'refChildrenField' => 'id',
                ],
                'childrenColumns' => ['name'],
            ],
            'tag'      => [
                'has'             => 'manyThrough',
                'mChildren'       => 'tag',
                'mGlue'           => 'tag_to_entity',
                'refKeys'         => [
                    'pkNameOfParentInGlue' => 'entity_id',
                    'pkNameOfChildInGlue'  => 'tag_id',
                ],
                'conditions'      => [
                    ['where', 'glue.entity_table', '=', 'user']
                ],
                'childrenColumns' => ['name'],
            ],

            'role' => [
                'has'             => 'manyThrough',
                'mChildren'       => 'role',
                'mGlue'           => 'user_role',
                'refKeys'         => [
                    'pkNameOfParentInGlue' => 'user_id',
                    'pkNameOfChildInGlue'  => 'role_id',
                ],
                'conditions'      => [],
                'childrenColumns' => ['name', 'description'],
            ]];
    }
}