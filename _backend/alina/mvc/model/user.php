<?php

namespace alina\mvc\model;

use alina\Message;
use alina\mvc\model\tale as taleAlias;
use alina\utils\Data;
use alina\utils\DateTime;
use alina\utils\Str;
use alina\utils\Sys;
use Illuminate\Database\Capsule\Manager as Dal;
use Illuminate\Database\Query\Builder as BuilderAlias;

class user extends _BaseAlinaModel
{
    public $table = 'user';

    public function fields()
    {
        $fDefault = parent::fields();
        //$fDefault = [];
        $fCustom = [
            'id'             => [],
            'mail'           => [
                'filters'    => [
                    // Could be a closure, string with function name or an array
                    'trim',
                    function ($v) {
                        return mb_strtolower($v);
                    },
                ],
                'validators' => [
                    [
                        'f'       => 'strlen',
                        'errorIf' => [FALSE, 0],
                        'msg'     => 'Email is required!',
                    ],
                    [
                        // 'f' - Could be a closure, string with function name or an array
                        'f'       =>
                            function ($v) {
                                return filter_var($v, FILTER_VALIDATE_EMAIL);
                            },
                        'errorIf' => [FALSE, 0],
                        'msg'     => 'Invalid Email Address!',
                    ],
                ],
            ],
            'password'       => [
                'filters'    => [
                    // Could be a closure, string with function name or an array
                    'trim',
                    ['\alina\utils\Data', 'filterVarStripTags'],
                ],
                'validators' => [
                    [
                        // 'f' - Could be a closure, string with function name or an array
                        'f'       => 'strlen',
                        'errorIf' => [FALSE, 0],
                        'msg'     => 'Password cannot be empty',
                    ],
                    [
                        // 'f' - Could be a closure, string with function name or an array
                        'f'       => function ($v) {
                            return Str::lessThan($v, 8);
                        },
                        'errorIf' => [TRUE],
                        'msg'     => 'Password length cannot be less than 8 symbols',
                    ],
                ],
            ],
            #####
            'firstname'      => [
                'filters'    => [
                    ['\alina\utils\Data', 'filterVarStripTags'],
                ],
                'validators' => [
                    // [
                    //     'f'       => 'strlen',
                    //     'errorIf' => [FALSE, 0],
                    //     'msg'     => 'First Name is required!',
                    // ],
                ],
            ],
            'lastname'       => [
                'filters' => [
                    ['\alina\utils\Data', 'filterVarStripTags'],
                ],
            ],
            'emblem'         => [],
            'birth'          => [
                'filters' => [
                    //['alina\\utils\\Data', 'filterVarInteger'],
                    function ($v) {
                        if (empty($v)) {
                            return 0;
                        }
                        if (is_numeric($v)) {
                            return $v;
                        }

                        return (new DateTime($v))->getTimestamp();
                    },
                ],
            ],
            'language'       => [
                'filters' => [
                    ['\alina\utils\Data', 'filterVarStripTags'],
                ],
                'default' => Sys::getUserLanguage(),
            ],
            'about_myself'   => [
                'default' => '',
                'filters' => [
                    ['\alina\utils\Data', 'filterVarStrHtml'],
                ],
            ],
            #####
            'is_verified'    => [
                'default' => 0,
            ],
            'banned_till'    => [
                'default' => 0,
            ],
            'created_at'     => [
                'default' => ALINA_TIME,
            ],
            'is_deleted'     => [
                'default' => 0,
            ],
            #####
            'reset_code'     => [],
            'reset_required' => [],
        ];
        $fRes    = array_merge($fDefault, $fCustom);

        return $fRes;
    }

    ##################################################
    #region References
    public function uniqueKeys()
    {
        return [
            ['mail'],
        ];
    }

    public function referencesTo()
    {
        return [
            'rbac_user_role'  => [ // Glue Table
                'has'        => 'manyThrough',
                'multiple'   => TRUE,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'rbac_role',
                    'childPk'        => 'id',
                    'childHumanName' => ['name'],
                    'glueTable'      => 'rbac_user_role',
                    'gluePk'         => 'id',
                    'glueMasterPk'   => 'user_id',
                    'glueChildPk'    => 'role_id',
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'rbac_user_role AS glue', 'glue.user_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'rbac_role AS child', 'child.id', '=', 'glue.role_id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.*', 'child.id AS child_id', 'glue.id AS ref_id', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],
            ],
            'rbac_permission' => [
                'has'        => 'manyThrough',
                ##############################
                # for Edit Form
                # ToDo ...
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'rbac_user_role AS glue', 'glue.user_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'rbac_role_permission AS glue2', 'glue2.role_id', '=', 'glue.role_id'],
                    ['join', 'rbac_permission AS child', 'child.id', '=', 'glue2.permission_id'],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['child.*', 'child.id AS child_id', 'glue.id AS ref_id', 'glue2.id AS ref_id2', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],
            ],
            'timezone'        => [
                'has'        => 'one',
                'multiple'   => FALSE,
                ##############################
                # for Apply dependencies
                'apply'      => [
                    'childTable'     => 'timezone',
                    'childPk'        => 'id',
                    'childHumanName' => ['name'],
                    'masterChildPk'  => 'timezone',
                ],
                ##############################
                # for Select With References
                'joins'      => [
                    ['leftJoin', 'timezone AS timezone', 'timezone.id', '=', "{$this->alias}.timezone"],
                ],
                'conditions' => [],
                'addSelects' => [
                    ['addSelect', ['timezone.name AS timezone_name']],
                ],
            ],
            'file'            => [
                'has'        => 'many',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'file AS child', 'child.entity_id', '=', "{$this->alias}.{$this->pkName}"],
                ],
                'conditions' => [
                    ['where', 'child.entity_table', '=', $this->table],
                ],
                'addSelects' => [
                    ['addSelect', ['child.*', 'child.id AS child_id', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],
            ],
            'tag'             => [
                'has'        => 'manyThrough',
                ##############################
                # for Select With References
                'joins'      => [
                    ['join', 'tag_to_entity AS glue', 'glue.entity_id', '=', "{$this->alias}.{$this->pkName}"],
                    ['join', 'tag AS child', 'child.id', '=', 'glue.tag_id'],
                ],
                'conditions' => [
                    ['where', 'glue.entity_table', '=', $this->table],
                ],
                'addSelects' => [
                    ['addSelect', ['child.*', 'child.id AS child_id', 'glue.id AS ref_id', "{$this->alias}.{$this->pkName} AS main_id"]],
                ],
                'orders'     => [
                    ['orderBy', 'child.name', 'ASC'],
                ],
            ],
            'about_myself'    => [
                ##############################
                # for Edit Form
                'type' => 'textarea',
            ],
        ];
    }

    public function hookRightBeforeSave(&$dataArray)
    {
        if (isset($dataArray['password'])) {
            if (!Data::isValidMd5($dataArray['password'])) {
                $dataArray['password'] = md5($dataArray['password']);
            }
        }

        return $this;
    }

    #####
    public function hookRightAfterSave($data)
    {
        _baseAlinaEloquentTransaction::begin();
        //ToDo: Security
        if (!AlinaAccessIfAdmin()) {
            return $this;
        }
        $refCfg = $this->referencesTo();
        foreach ($refCfg as $refName => $cfg) {
            if (isset($cfg['multiple']) && $cfg['multiple']) {
                if (isset($cfg['apply'])) {
                    if (isset($data->{$refName}) && !empty($data->{$refName})) {
                        ####################
                        # Definitions
                        $glueTable           = $cfg['apply']['glueTable'];
                        $glueMasterPk        = $cfg['apply']['glueMasterPk'];
                        $pkMasterValue       = $this->attributes->{$this->pkName};
                        $glueChildPk         = $cfg['apply']['glueChildPk'];
                        $mGlueTable          = modelNamesResolver::getModelObject($glueTable);
                        $arrNewChildPkValues = [];
                        ####################
                        # Preparation
                        // ToDo: Simplify to clear empty values.
                        $arrPostedChildIds = $data->{$refName} ?? [];
                        if (isset($arrPostedChildIds) && count($arrPostedChildIds) > 0) {
                            foreach ($arrPostedChildIds as $postedChildId) {
                                if (!empty($postedChildId)) {
                                    $arrNewChildPkValues[] = $postedChildId;
                                }
                            }
                        }
                        ####################
                        # DELETE
                        $q = $mGlueTable->q();
                        $q->where($glueMasterPk, '=', $pkMasterValue);
                        $q->whereNotIn($glueChildPk, $arrNewChildPkValues);
                        $q->delete();
                        ####################
                        # SELECT
                        $q = $mGlueTable->q();
                        $q->select($glueChildPk);
                        $q->where($glueMasterPk, '=', $pkMasterValue);
                        $currChildIds = $q->pluck($glueChildPk)->toArray();
                        ####################
                        # INSERT
                        foreach ($arrNewChildPkValues as $newChildId) {
                            if (!in_array($newChildId, $currChildIds)) {
                                $mGlueTable->insert([
                                    $glueMasterPk => $pkMasterValue,
                                    $glueChildPk  => $newChildId,
                                ]);
                            }
                        }
                    }
                }
            }
        }
        _baseAlinaEloquentTransaction::begin();

        return $this;
    }

    public function hookGetWithReferences($q)
    {
        //ToDo: Cross DataBase.
        /** @var $q BuilderAlias object */
        $q->addSelect(Dal::raw("(SELECT COUNT(*) FROM notification AS n WHERE n.to_id = {$this->alias}.{$this->pkName} AND n.is_shown = 0) AS count_notifications"));
    }

    #endregion References
    ##################################################
    #region RBAC
    public function hasRole($role)
    {
        if (!isset($this->attributes->rbac_user_role)) {
            if (isset($this->id)) {
                $this->getOneWithReferences([
                    "{$this->alias}.{$this->pkName}" => $this->id,
                ]);
            }
        }
        if (isset($this->attributes->rbac_user_role)) {
            $roles = $this->attributes->rbac_user_role;
            foreach ($roles as $r) {
                if (strtoupper($r->name) === strtoupper($role)) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    public function hasPerm($perm)
    {
        if (!isset($this->attributes->rbac_permission)) {
            if (isset($this->id)) {
                $this->getOneWithReferences([
                    "{$this->alias}.{$this->pkName}" => $this->id,
                ]);
            }
        }
        if (isset($this->attributes->rbac_permission)) {
            $perms = $this->attributes->rbac_permission;
            foreach ($perms as $p) {
                if (strtoupper($p->name) === strtoupper($perm)) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    #endregion RBAC
    ##################################################
    public function bizDelete($id)
    {
        $vd = (object)[];
        if (AlinaAccessIfAdminOrModeratorOrOwner($id)) {
            _baseAlinaEloquentTransaction::begin();
            $vd->notifications = (new notification())
                ->q(-1)
                ->where(function ($q) use ($id) {
                    /** @var $q BuilderAlias object */
                    $q
                        ->where('to_id', '=', $id)
                        ->orWhere('from_id', '=', $id);
                })
                ->delete();
            $vd->likes         = (new \alina\mvc\model\like())
                ->q(-1)
                ->where('user_id', '=', $id)
                ->delete();
            $vd->tales         = (new taleAlias())->delete(['owner_id' => $id,]);
            $vd->rbac_roles    = (new rbac_user_role())->delete(['user_id' => $id,]);
            $vd->login         = (new login())->delete(['user_id' => $id,]);
            $vd->users         = (new user())->deleteById($id);
            _baseAlinaEloquentTransaction::commit();
        }

        return $vd;
    }
    ##################################################
}
