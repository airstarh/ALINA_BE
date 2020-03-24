<?php

namespace alina\mvc\model;

use alina\utils\Data;
use Illuminate\Database\Capsule\Manager as Dal;
use Illuminate\Database\Query\Builder as BuilderAlias;

class tale extends _BaseAlinaModel
{
    public $table = 'tale';

    public function fields()
    {
        return [
            'id'                => [],
            'owner_id'          => [
                'default' => CurrentUser::obj()->id,
            ],
            'header'            => [
                'filters' => [
                    ['\alina\utils\Data', 'filterVarStripTags'],
                ],
            ],
            'body'              => [
                'filters' => [
                    ['\alina\utils\Data', 'filterVarStrHtml'],
                ],
            ],
            'body_txt'          => [
                'filters' => [
                    ['\alina\utils\Data', 'filterVarStripTags'],
                ],
            ],
            'created_at'        => [
                'default' => ALINA_TIME,
            ],
            'modified_at'       => [
                'default' => ALINA_TIME,
            ],
            'publish_at'        => [
                'default' => ALINA_TIME,
            ],
            'is_submitted'      => [
                'default' => 0,
            ],
            'root_tale_id'      => [
                'default' => NULL,
            ],
            'answer_to_tale_id' => [
                'default' => NULL,
            ],
            'type'              => [
                'default' => 'POST',
            ],
            'level'             => [
                'default' => 0,
            ],
        ];
    }

    ##################################################
    public function referencesTo()
    {
        return [
            'owner' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'user AS owner', 'owner.id', '=', "{$this->alias}.owner_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', [
                        'owner.id AS owner_id',
                        'owner.firstname AS owner_firstname',
                        'owner.lastname AS owner_lastname',
                        'owner.emblem AS owner_emblem',
                    ]],
                ],
            ],
            'tag'   => [
                'has'        => 'manyThrough',
                'joins'      => [
                    ['leftJoin', 'tag_to_entity AS glue', 'glue.entity_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['leftJoin', 'tag AS child', 'child.id', '=', 'glue.tag_id'],
                ],
                'conditions' => [
                    ['where', 'glue.entity_table', '=', $this->table],
                ],
                'addSelects' => [
                    ['addSelect', ['child.name AS tag_name', 'child.id AS child_id', 'glue.id AS ref_id', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],
                'orders'     => [
                    ['orderBy', 'child.name', 'ASC'],
                ],
            ],
            // 'comments' => [
            //     'has'        => 'many',
            //     'joins'      => [
            //         ['join', 'tale AS child', 'child.answer_to_tale_id', '=', "{$this->alias}.{$this->pkName}"],
            //     ],
            //     'conditions' => [
            //     ],
            //     'orders'     => [
            //         ['orderBy', 'child.publish_at', 'ASC'],
            //     ],
            //     'addSelects' => [
            //         ['addSelect', ['child.*', 'child.id AS child_id', "{$this->alias}.{$this->pkName} AS main_id"]],
            //     ],
            // ],
        ];
    }

    ##################################################
    public function hookGetWithReferences($q)
    {
        //ToDo: Cross DataBase.
        /** @var $q BuilderAlias object */
        $q->addSelect(Dal::raw("(SELECT COUNT(*) FROM tale AS tale1 WHERE tale1.answer_to_tale_id = {$this->alias}.{$this->pkName}) AS count_answer_to_tale_id"));
        $q->addSelect(Dal::raw("(SELECT COUNT(*) FROM tale AS tale2 WHERE tale2.root_tale_id = {$this->alias}.{$this->pkName}) AS count_root_tale_id"));
    }

    ##################################################
    public function hookRightBeforeSave(&$dataArray)
    {
        if (array_key_exists('body', $dataArray)) {
            $dataArray['body_txt'] = \alina\utils\Data::filterVarStripTags($dataArray['body']);
        }

        return $this;
    }
    ##################################################
}
