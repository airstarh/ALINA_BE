<?php

namespace alina\mvc\model;


class referenceProcessor
{
    /** @var \Illuminate\Database\Query\Builder $q */
    public $q;
    /** @var \Illuminate\Database\Query\Builder[] $qArray */
    public $qArray = [];
    /** @var \alina\mvc\model\_BaseAlinaModel */
    public $model;
    /** @var array */
    public $forIds = [];

    public function __construct($modelMain)
    {
        $this->model = $modelMain;
    }

    public function joinHasOne($refNames = [])
    {
        $m       = $this->model;
        $q           = $this->model->q;
        $referencesTo = $m->referencesTo();

        foreach ($referencesTo as $rName => $rConfig) {

            if (!empty($refNames)) {
                if (!in_array($rName, $refNames)) {
                    continue;
                }
            }

            switch ($rConfig['has']) {
                case 'one':
                case 1:
                    $this->q              = $q;
                    $this->qArray[$rName] = $this->applyRefConfigToQuery($rConfig);

                    if (!empty($this->forIds)) {
                        $this->qArray[$rName]->whereIn("{$m->alias}.{$m->pkName}", $this->forIds);
                    }
                    $this->q = NULL;
                    break;
            }
        }

    }

    public function joinHasMany($refNames = [], $forIds = [])
    {
        $this->forIds = $forIds;

        /** @var $m \alina\mvc\model\_BaseAlinaModel */

        /**
         * ATTENTION.
         * Due to $m->referencesTo() could have dynamic values,
         * such as $m->alias,
         * it is highly recommended to iterate $m->referencesTo() AFTER $m->q() has been executed.
         */

        if (is_string($refNames)) {
            $refNames = [$refNames];
        }

        $model        = $this->model;
        $mClassName   = get_class($model);
        $m            = new $mClassName();
        $m->alias     = 'main';
        $referencesTo = $m->referencesTo();
        foreach ($referencesTo as $rName => $rConfig) {

            if (!empty($refNames)) {
                if (!in_array($rName, $refNames)) {
                    continue;
                }
            }

            switch ($rConfig['has']) {
                case 'many':
                case 'manyThrough':
                    $this->q              = $m->q();
                    $this->qArray[$rName] = $this->applyRefConfigToQuery($rConfig);

                    if (!empty($this->forIds)) {
                        $this->qArray[$rName]->whereIn("{$m->alias}.{$m->pkName}", $this->forIds);
                    }

                    $this->q = NULL;
                    break;
            }
        }

        return $this->qArray;
    }

    #region protected
    protected function applyRefConfigToQuery($refConfig)
    {
        if (isset($refConfig['joins'])) {
            $this->applyQueryOperations($refConfig['joins']);
        }
        if (isset($refConfig['conditions'])) {
            $this->applyQueryOperations($refConfig['conditions']);
        }
        if (isset($refConfig['addSelects'])) {
            $this->applyQueryOperations($refConfig['addSelects']);
        }
        if (isset($refConfig['orders'])) {
            $this->applyQueryOperations($refConfig['orders']);
        }

        return $this->q;
    }

    protected function applyQueryOperations(array $operations)
    {
        $q = $this->q;
        foreach ($operations as $operation) {
            $method = array_shift($operation);
            call_user_func_array([$q, $method], $operation);
        }
    }
    #endregion protected
}