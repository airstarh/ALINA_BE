<?php

namespace alina\mvc\Model;

use alina\Utils\Data;
use Illuminate\Database\Capsule\Manager as Dal;
use Illuminate\Database\Query\Builder as BuilderAlias;

class tale extends _BaseAlinaModel
{
    public $flagAuditInfoLog = false;
    public $table          = 'tale';
    public $ownerId_root   = NULL;
    public $ownerId_answer = NULL;
    public $sortDefault    = [['publish_at', 'DESC']];

    public function fields()
    {
        $pFields = parent::fields();
        $fields  = [
            'id'                       => [],
            'owner_id'                 => [
                'default' => CurrentUser::obj()->id(),
            ],
            'header'                   => [
                'filters' => [
                    ['\alina\utils\Data', 'filterVarStripTags'],
                ],
            ],
            'body'                     => [
                'filters' => [
                    ['\alina\utils\Data', 'filterVarStrHtml'],
                ],
            ],
            'body_txt'                 => [
                'filters' => [
                    ['\alina\utils\Data', 'filterVarStripTags'],
                ],
            ],
            'body_free'                => [
            ],
            'iframe'                   => [
                'default' => NULL,
            ],
            'created_at'               => [
                'default' => ALINA_TIME,
            ],
            'modified_at'              => [
                'default' => ALINA_TIME,
            ],
            'publish_at'               => [
                'default' => ALINA_TIME,
            ],
            'is_submitted'             => [
                'default' => 0,
            ],
            'root_tale_id'             => [
                'default' => NULL,
            ],
            'answer_to_tale_id'        => [
                'default' => NULL,
            ],
            'type'                     => [
                'default' => 'POST',
            ],
            'level'                    => [
                'default' => 0,
            ],
            'is_adult_denied'          => [
                'default' => 0,
            ],
            'is_draft'                 => [
                'default' => 0,
            ],
            'is_adv'                   => [
                'default' => 0,
            ],
            'is_comment_denied'        => [
                'default' => 0,
            ],
            'is_sticked'               => [
                'default' => 0,
            ],
            'is_sticked_on_home'       => [ //ToDo: Seems not in use
                'default' => 0,
            ],
            'is_header_hidden'         => [
                'default' => 0,
            ],
            'is_date_hidden'           => [
                'default' => 0,
            ],
            'is_avatar_hidden'         => [
                'default' => 0,
            ],
            'is_social_sharing_hidden' => [
                'default' => 0,
            ],
            'is_for_registered'        => [
                'default' => 0,
            ],
            'is_comment_for_owner'     => [
                'default' => 0,
            ],
            'seo_index'                => [
                'default' => 0.2,
            ],
            'geo_latitude'             => [
                'default' => 0,
            ],
            'geo_longitude'            => [
                'default' => 0,
            ],
            'geo_map_type'             => [
                'default' => 'hybrid',
            ],
            'geo_zoom'                 => [
                'default' => 10,
            ],
            'geo_is_map_shown'         => [
                'default' => 0,
            ],
        ];

        return array_merge($pFields, $fields);
    }

    ##################################################
    public function referencesTo()
    {
        return [
            'owner'        => [
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
            'router_alias' => [
                'has'        => 'one',
                'joins'      => [
                    //['leftJoin', 'router_alias AS rs', 'rs.table_id', '=', "{$this->alias}.id"],
                    ['leftJoin', 'router_alias AS rs', function ($join) {
                        $join->on('rs.table_id', '=', "{$this->alias}.id");
                        $join->on('rs.table', '=', Dal::raw("'tale'"));
                    }],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect',
                        ['rs.alias AS router_alias', 'rs.id AS router_alias_id'],
                    ],
                ],
            ],
            'tag'          => [
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
            'body'         => [
                'type' => 'textarea',
            ],
            'body_txt'     => [
                'type' => 'readonly',
            ],
        ];
    }

    ##################################################
    public function hookGetWithReferences($q)
    {
        $uid = CurrentUser::obj()->id() ?: -1;
        //ToDo: Cross DataBase.
        /** @var $q BuilderAlias object */
        $q->addSelect(Dal::raw("(SELECT COUNT(*) FROM tale AS tale1 WHERE tale1.answer_to_tale_id = {$this->alias}.{$this->pkName} AND tale1.created_at > {$this->alias}.created_at) AS count_answer_to_tale_id"));
        $q->addSelect(Dal::raw("(SELECT COUNT(*) FROM tale AS tale2 WHERE tale2.root_tale_id = {$this->alias}.{$this->pkName} AND tale2.created_at > {$this->alias}.created_at) AS count_root_tale_id"));
        $q->addSelect(Dal::raw("
            (SELECT COUNT(*) 
                FROM lk AS lk 
                WHERE 
                        lk.ref_id = {$this->alias}.{$this->pkName}
                    AND lk.ref_table = 'tale' 
                    AND lk.created_at > {$this->alias}.created_at
            ) AS count_like
        "));
        $q->addSelect(Dal::raw("
            (SELECT COUNT(*)
                FROM lk AS user_liked
                WHERE
                        user_liked.ref_id = {$this->alias}.{$this->pkName}
                    AND user_liked.ref_table = 'tale'
                    AND user_liked.created_at > {$this->alias}.created_at
                    AND user_liked.user_id =  {$uid}
            ) AS current_user_liked
        "));
        $q->addSelect(Dal::raw("
            (SELECT COUNT(*)
                FROM `file` AS files_attached
                WHERE
                        files_attached.entity_id = {$this->alias}.{$this->pkName}
                    AND files_attached.entity_table = 'tale'
            ) AS count_files
        "));
    }

    ##################################################
    public function hookRightBeforeSave(&$dataArray)
    {
        if (array_key_exists('body', $dataArray)) {
            $dataArray['body_txt'] = \alina\Utils\Data::filterVarStripTags($dataArray['body']);
        }
        #####
        #region Double check parents
        $root_tale_id      = NULL;
        $answer_to_tale_id = NULL;
        $level             = 0;
        $type              = 'POST';
        if (array_key_exists('answer_to_tale_id', $dataArray)) {
            #####
            # ANSWER p1 - parent-1
            if (!empty($dataArray['answer_to_tale_id'])) {
                $p1_id   = $dataArray['answer_to_tale_id'];
                $p1      = new tale();
                $p1Q     = $p1->q();
                $p1Attrs = $p1Q->select(['id', 'root_tale_id', 'answer_to_tale_id', 'owner_id'])->where([['id', $p1_id]])->first();
                #####
                # ROOT p2 - parent-2
                if (isset($p1Attrs->id) && !empty($p1Attrs->id)) {
                    $root_tale_id         = $p1Attrs->id;
                    $answer_to_tale_id    = $p1Attrs->id;
                    $level                = 1;
                    $type                 = 'COMMENT';
                    $this->ownerId_answer = $p1Attrs->owner_id;
                    if (isset($p1Attrs->answer_to_tale_id) && !empty($p1Attrs->answer_to_tale_id)) {
                        $p2_id   = $p1Attrs->answer_to_tale_id;
                        $p2      = new tale();
                        $p2Q     = $p2->q();
                        $p2Attrs = $p2Q->select(['id', 'root_tale_id', 'answer_to_tale_id', 'owner_id'])->where([['id', $p2_id]])->first();
                        if (isset($p2Attrs->id) && !empty($p2Attrs->id)) {
                            $root_tale_id       = $p2Attrs->id;
                            $level              = 2;
                            $type               = 'COMMENT';
                            $this->ownerId_root = $p2Attrs->owner_id;
                        }
                    }
                }
            }
        }
        $dataArray['root_tale_id']      = $root_tale_id;
        $dataArray['answer_to_tale_id'] = $answer_to_tale_id;
        $dataArray['level']             = $level;
        $dataArray['type']              = $type;
        #endregion Double check parents
        #####
        #####
        return $this;
    }
    ##################################################
    #region Helpers
    public function getChainOfParents($id)
    {
        $chainOfParents = $this
            ->q('current')
            ->select([
                'current.id AS id',
                'current.owner_id AS owner_id',
                'root.id AS root_tale_id',
                'root.owner_id AS root_owner_id',
                'answer.id AS answer_to_tale_id',
                'answer.owner_id AS answer_owner_id',
            ])
            ->where('current.id', '=', $id)
            ->leftJoin('tale AS answer', 'answer.id', '=', 'current.answer_to_tale_id')
            ->leftJoin('tale AS root', 'root.id', '=', 'current.root_tale_id')
            ->first()
        ;

        return $chainOfParents;
    }
    #endregion Helpers
    ##################################################
}
