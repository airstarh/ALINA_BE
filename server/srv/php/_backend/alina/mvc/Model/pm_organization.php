<?php

namespace alina\mvc\Model;

use alina\Utils\Data;

class pm_organization extends _BaseAlinaModel
{
    public $table        = 'pm_organization';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'          => [],
            'name_human'  => [
                'default' => 'Организация',
            ],
            'manager_id'  => [],
            'created_at'  => [],
            'created_by'  => [],
            'modified_at' => [],
            'modified_by' => [],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            ##### field #####
            'manager_id'     => [
                'has'        => 'one',
                'multiple'   => false,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'user',
                    'childPk'        => 'id',
                    'childHumanName' => ['firstname', 'lastname', 'mail'],
                    'masterChildPk'  => 'manager_id',
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'user AS manager', 'manager.id', '=', "{$this->alias}.manager_id"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'manager.firstname AS manager.firstname',
                            'manager.lastname AS manager.lastname',
                            'manager.mail AS manager.mail',
                            'manager.emblem AS manager.emblem',
                        ],
                    ],
                ],
            ],
            ##### field #####
            '_pm_department' => [
                'has'        => 'many',
                'multiple'   => true,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'pm_department',
                    'childPk'        => 'id',
                    'childGlueKey'   => 'pm_organization_id',
                    'childHumanName' => ['name_human'],
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_department AS child', 'child.pm_organization_id', '=', "{$this->alias}.{$this->pkName}"],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'child.*',
                            'child.id AS child_id',
                            "{$this->alias}.{$this->pkName} AS main_id",
                        ],
                    ],
                ],
            ],
            ##### field #####
            '_pm_project'    => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_department AS pm_department', 'pm_department.pm_organization_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'pm_project AS pm_project', 'pm_project.pm_department_id', '=', 'pm_department.id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_project.id AS _pm_project_id',
                            'pm_project.name_human AS _pm_project_name_human',
                            'pm_project.price_multiplier AS _pm_project_price_multiplier',
                            'pm_project.pm_department_id AS _pm_project_pm_department_id',
                            "{$this->alias}.{$this->pkName} AS main_id",
                        ],
                    ],
                ],
            ],
            ##### field #####
            '_pm_task'       => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_department AS pm_department', 'pm_department.pm_organization_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'pm_project AS pm_project', 'pm_project.pm_department_id', '=', 'pm_department.id'],
                    ['join', 'pm_task AS pm_task', 'pm_task.pm_project_id', '=', 'pm_project.id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_task.id AS _pm_task_id',
                            'pm_task.name_human AS _pm_task_name_human',
                            'pm_task.pm_project_id AS _pm_task_pm_project_id',
                            "{$this->alias}.{$this->pkName} AS main_id",
                        ],
                    ],
                ],
            ],
            ##### field #####
            '_pm_subtask'    => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'pm_department AS pm_department', 'pm_department.pm_organization_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'pm_project AS pm_project', 'pm_project.pm_department_id', '=', 'pm_department.id'],
                    ['join', 'pm_task AS pm_task', 'pm_task.pm_project_id', '=', 'pm_project.id'],
                    ['join', 'pm_subtask AS pm_subtask', 'pm_subtask.pm_task_id', '=', 'pm_task.id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect',
                        [
                            'pm_subtask.id AS _pm_subtask_id',
                            'pm_subtask.name_human AS _pm_subtask_name_human',
                            'pm_subtask.pm_task_id AS _pm_subtask_pm_task_id',
                            "{$this->alias}.{$this->pkName} AS main_id",
                        ],
                    ],
                ],
            ],
            ##### field #####
        ];
    }


    public function hookRightAfterSave($data)
    {
        //ToDo: Security
        if (!AlinaAccessIfAdmin() || AlinaAccessIfModerator()) {
            return $this;
        }
        _baseAlinaEloquentTransaction::begin();
        $refCfg = $this->referencesTo();

        ##########
        #region FOREACH Ref CONFIG
        foreach ($refCfg as $refName => $cfg) {
            ##################################################
            #region manyThrough
            if (
                (isset($cfg['has']) && $cfg['has'] === 'manyThrough')
            ) {
                if (isset($cfg['apply'])) {
                    if (isset($data->{$refName}) && !empty($data->{$refName})) {
                        ####################
                        # Definitions
                        $glueTable    = $cfg['apply']['glueTable'];
                        $glueMasterPk = $cfg['apply']['glueMasterPk'];
                        $glueChildPk  = $cfg['apply']['glueChildPk'];
                        $pkValue      = $this->attributes->{$this->pkName};
                        $mGlueTable   = modelNamesResolver::getModelObject($glueTable);
                        ####################
                        # Preparation
                        $arrPostedChildIds = $data->{$refName} ?? [];
                        $ids               = [];
                        foreach ($arrPostedChildIds as $v) {
                            if (is_object($v)) {
                                $id = $v->id;
                            } elseif (is_array($v)) {
                                $id = $v['id'];
                            } else {
                                $id = $v;
                            }
                            $ids[] = $id;
                        }
                        $arrNewChildPkValues = Data::deleteEmptyProps($ids);
                        ####################
                        # DELETE
                        $q = $mGlueTable->q(-1);
                        $q->where($glueMasterPk, '=', $pkValue);
                        $q->whereNotIn($glueChildPk, $arrNewChildPkValues);
                        $q->delete();
                        ####################
                        # SELECT
                        $q = $mGlueTable->q();
                        $q->select($glueChildPk);
                        $q->where($glueMasterPk, '=', $pkValue);
                        $currChildIds = $q->pluck($glueChildPk)->toArray();
                        ####################
                        # INSERT
                        foreach ($arrNewChildPkValues as $newChildId) {
                            if (!in_array($newChildId, $currChildIds)) {
                                $mGlueTable->insert([
                                    $glueMasterPk => $pkValue,
                                    $glueChildPk  => $newChildId,
                                ]);
                            }
                        }
                    }
                }
            }
            #region manyThrough
            ##################################################

            ##################################################
            #region many
            if (
                (isset($cfg['has']) && $cfg['has'] === 'many')
            ) {
                if (isset($cfg['apply'])) {
                    if (isset($data->{$refName}) && !empty($data->{$refName})) {
                        ####################
                        # Definitions
                        $childTable   = $cfg['apply']['childTable'];
                        $childPk      = $cfg['apply']['childPk'];
                        $childGlueKey = $cfg['apply']['childGlueKey'];
                        $pkValue      = $this->attributes->{$this->pkName};
                        $mChildTable  = modelNamesResolver::getModelObject($childTable);
                        ####################
                        # Preparation
                        $arrPostedChildIds = $data->{$refName} ?? [];
                        $ids               = [];
                        foreach ($arrPostedChildIds as $v) {
                            if (is_object($v)) {
                                $id = $v->id;
                            } elseif (is_array($v)) {
                                $id = $v['id'];
                            } else {
                                $id = $v;
                            }
                            $ids[] = $id;
                        }
                        $arrNewChildPkValues = Data::deleteEmptyProps($ids);
                        ####################
                        # DELETE
                        $q = $mChildTable->q(-1);
                        $q->where($childGlueKey, '=', $pkValue);
                        $q->update([$childGlueKey => null]);
                        ####################
                        # INSERT
                        $q = $mChildTable->q(-1);
                        $q->whereIn($childPk, $arrNewChildPkValues);
                        $q->update([$childGlueKey => $pkValue]);
                    }
                }
            }
            #endregion many
            ##################################################
        }
        #endregion FOREACH Ref CONFIG
        ##########
        _baseAlinaEloquentTransaction::commit();

        return $this;
    }

    ### method ###
}
