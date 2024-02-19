<?php

namespace alina\mvc\Model;

use alina\Utils\Data;

class pm_organization extends _BaseAlinaModel
{
    use pm_trait;

    public $table        = 'pm_organization';
    public $addAuditInfo = true;

    public function fields()
    {
        return [
            'id'          => [],
            'name_human'  => [
                'required' => true,
                'default'  => 'Организация',
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
        return array_merge([],
            $this->manager_id(),
            $this->_pm_department(),
            $this->_pm_project(),
            ###$this->_pm_task(),
            $this->_pm_subtask(),
            $this->created_by(),
            $this->modified_by(),
        );
    }


    public function hookRightAfterSave($data)
    {
        //ToDo: Security
        if (!AlinaAccessIfAdmin() && !AlinaAccessIfModerator()) {
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
                            }
                            elseif (is_array($v)) {
                                $id = $v['id'];
                            }
                            else {
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
                            }
                            elseif (is_array($v)) {
                                $id = $v['id'];
                            }
                            else {
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
