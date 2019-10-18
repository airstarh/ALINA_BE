<?php

namespace alina\mvc\model\eloquent;


trait trait_validation
{

    public $isDataFiltered      = FALSE;
    public $isValidated         = FALSE;
    public $matchedUniqueFields = [];
    public $matchedUniqueValues = [];

    public function fields()
    {
        return [];
    }

    public function uniqueKeys()
    {
        return [];
    }

    public function prepareModel()
    {
        $this->applyFilters();
        $this->validate();

        return $this;
    }

    public function applyFilters()
    {
        $data = \alina\utils\Data::toObject($this->attributes);
        if ($this->isDataFiltered) return $this;

        $fields = $this->fields();
        foreach ($fields as $name => $params) {
            if (property_exists($data, $name)) {
                $value = $data->{$name};
                if (isset($params['filters']) && !empty($params['filters'])) {
                    foreach ($params['filters'] as $filter) {

                        // The simplest filter
                        if (is_string($filter) && function_exists($filter)) {
                            $this->{$name} = $filter($value);
                        } else if ($filter instanceof \Closure) {
                            $this->{$name} = call_user_func($filter, $value);;
                        } else if (is_array($filter)) {
                            $argsAmount = count($filter);
                            switch ($argsAmount) {
                                case 2:
                                    list($obj, $method) = $filter;
                                    $this->{$name} = call_user_func([$obj, $method], $value);
                                    break;
                            }
                        }

                        // ToDo: Maybe more abilities for filter.
                    }
                }
            } else if ($this->isNew() && isset($params['default'])) {
                $this->{$name} = $params['default'];
            }
        }

        $this->isDataFiltered = TRUE;

        return $this;
    }

    public function validate()
    {

        if ($this->isValidated) return $this;

        $data   = \alina\utils\Data::toObject($this->attributes);
        $fields = $this->fields();

        foreach ($fields as $name => $params) {
            if (property_exists($data, $name)) {
                $value = $data->{$name};
                if (isset($params['validators']) && !empty($params['validators'])) {
                    foreach ($params['validators'] as $v) {

                        $vResult = TRUE;
                        $errorIf = (isset($v['errorIf']) && !empty($v['errorIf']))
                            ? $v['errorIf']
                            : [FALSE];
                        $msg     = (isset($v['msg']) && !empty($v['msg']))
                            ? $v['msg']
                            : "Validation failed. Field:{$name}. Value: {$value}";

                        // The simplest validator
                        if (is_string($v['f']) && function_exists($v['f'])) {
                            $vResult = $v['f']($value);
                        } else if ($v['f'] instanceof \Closure) {
                            $vResult = call_user_func($v['f'], $value);;
                        } else if (is_array($v['f'])) {
                            $argsAmount = count($v['f']);
                            switch ($argsAmount) {
                                case 2:
                                    list($class, $staticMethod) = $v['f'];
                                    $vResult = call_user_func([$class, $staticMethod], $value);
                                    break;
                            }
                        }
                        // ToDo: Maybe more abilities for validation.

                        // Validation Result process.
                        if (in_array($vResult, $errorIf)) {
                            throw new \ErrorException($msg);
                        }
                    }
                }
            }
        }

        $this->validateUniqueKeys();

        $this->isValidated = TRUE;

        return $this;
    }

    public function validateUniqueKeys()
    {

        //if ($this->saveMode === 'UPDATE') {
        if (!$this->isNew()) {
            return $this;
        }

        if ($this->getModelByUniqueKeys()) {
            $fields = implode(', ', $this->matchedUniqueFields);
            $values = implode(', ', $this->matchedUniqueValues);
            $table  = $this->table;
            throw new \ErrorException("Fields: {$fields} must be unique {$table}. Values: {$values}");
        }

        return $this;
    }

    public function getModelByUniqueKeys()
    {

        $data       = \alina\utils\Data::toObject($this->attributes);
        $uniqueKeys = $this->uniqueKeys();

        foreach ($uniqueKeys as $uniqueFields) {
            $conditions    = [];
            $matchedFields = [];
            $matchedValues = [];

            if (!is_array($uniqueFields)) $uniqueFields = [$uniqueFields];

            foreach ($uniqueFields as $uf) {
                if (property_exists($data, $uf)) {
                    $conditions[$uf] = $data->{$uf};
                    $matchedFields[] = $uf;
                    $matchedValues[] = $data->{$uf};
                }
                // If $data doesn't contain one of Unique Keys,
                // simply skip this check entirely.
                else {
                    continue 2; // Skips this and previous "foreach".
                }
            }

            /*** @var $q \Illuminate\Database\Query\Builder */
            $q = new static;
            $q = $q->where($conditions);
            // If $data already contains a field with Primary Key of a model,
            // we should exclude this model while the check.
            if (!$this->isNew()) {
                $q = $q->where($this->primaryKey, '!=', $this->{$this->primaryKey});
            }

            // Check only NOT is_deleted
            /*if ($m->tableHasField('is_deleted')) {
                $q->where('is_deleted', '!=', 1);
            }*/

            $aRecord = $q->first();

            if (isset($aRecord) && !empty($aRecord)) {
                $this->matchedUniqueFields = $matchedFields;
                $this->matchedUniqueValues = $matchedValues;

                return $aRecord;
            }
        }

        return FALSE;
    }

    public function isNew()
    {
        return empty($this->{$this->primaryKey});
    }
}
