<?php
namespace alina\mvc\model;

use \alina\vendorExtend\illuminate\alinaLaravelCapsule as Dal;

// Laravel initiation
\alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader::init();

class _baseAlinaEloquentModel
{
    #region Required
    public $table;
    public $alias = '';
    public $pkName = 'id';

    /** @var \Illuminate\Database\Query\Builder $q */
    public $q;

    /**
     * @param string $alias
     * @return \Illuminate\Database\Query\Builder
     */
    public function q($alias = NULL)
    {
        if ($alias) {
            $table       = "{$this->table} AS $alias";
            $this->alias = $alias;
        } else {
            $table = $this->alias = $this->table;
        }

        $this->q = Dal::table($table);

        return $this->q;
    }

    /**
     * Initial list of fields. @see fieldStructureExample
     */
    public function fields()
    {
        return [];
    }

    public function fieldStructureExample()
    {
        $className             = 'ValidatorClassName';
        $staticMethod          = 'staticMethodName';
        $externalScopeVariable = 'someValue';

        return
            [
                'fieldName' => [
                    'default'    => 'field default value on INSERT',
                    'filters'    => [
                        // Could be a closure, string with function name or an array
                        [$className, $staticMethod],
                        function ($value) use ($externalScopeVariable) {
                            return trim($value);
                        },
                        'trim',
                    ],
                    'validators' => [

                        [
                            // 'f' - Could be a closure, string with function name or an array
                            'f'       => 'strlen',
                            'errorIf' => [FALSE, 0],
                            'msg'     => 'Please, fill Name',
                        ],
                    ],

                ],
            ];
    }

    /**
     * Returns array of arrays.
     * Nested arrays contain list of field names, which are Unique Keys together.
     * Example:
     * [
     *  ['email'],
     *  ['row', 'column'],
     *  [$this->pkName] (not necessary)
     * ]
     */
    public function uniqueKeys()
    {
        return [];
    }
    #endregion Required

    #region Search Parameters
    public $GET = NULL;

    public function vocGetParams()
    {
        return [
            // Pagination
            'ps'    => 'pageSize',
            'p'     => 'page',

            // Sorting functionality
            'sa'    => 'sortAsc',
            'sn'    => 'sortName',

            // Search
            'q'     => 'search',
        ];
    }

    public function unpackGetParams()
    {
        $this->GET    = new \stdClass();
        $vocGetSearch = $this->vocGetParams();
        foreach ($vocGetSearch as $short => $full) {
            /*
             * NOTE:
             * It sets Property even if it is equal to empty string ''.
             * Check right in API callbacks, if we need to SELECT models, WHERE the Prop is ''.
             * Otherwise, Front-end is to be adjusted to avoid of sending GET params, when they are not necessary.
             */
            //if (isset($_GET[$short]) && $_GET[$short] !=='') {
            if (isset($_GET[$short])) {
                $this->GET->{$full} = $_GET[$short];
            }
        }
        // Additional Specail Condition $this->sortName, $this->sortAsc
        if (isset($_GET['sa'])) $this->sortAsc = $_GET['sa'];
        if (isset($_GET['sn'])) $this->sortName = $_GET['sn'];

        // Additional Specail Comdition $this->pageSize, $this->page
        if (isset($_GET['ps'])) $this->pageSize = $_GET['ps'];
        if (isset($_GET['p'])) $this->page = $_GET['p'];

        return $this->GET;
    }
    #endregion Search Parameters

    #region SELECT

    public $attributes;

    /**
     * @property  array
     * Contains array of Objects received from DB.
     */
    public $collection = [];

    public function getOne($conditions = [])
    {
        $data = $this->q()->where($conditions)->first();

        $this->attributes = $data;

        return $data;
    }

    public function getById($id)
    {
        return $this->getOne([$this->pkName => $id]);
    }

    public function getAll($conditions = [])
    {
        $this->collection = $this->q()->where($conditions)->get();

        return $this->collection;
    }
    #endregion SELECT

    #region INSERT or Update
    public $saveMode        = NULL; // Could be 'UPDATE' or 'INSERT'
    public $isDataFiltered  = FALSE;
    public $isDataValidated = FALSE;

    public function insert($data)
    {
        $this->saveMode  = 'INSERT';
        $data            = toObject($data);
        $dataArray       = $this->prepareDbData($data);
        $pkName          = $this->pkName;
        $id              = $this->q()->insertGetId($dataArray, $pkName);
        $data->{$pkName} = $id;
        $this->resetFlags();

        $this->attributes = $data;

        return $data;
    }

    public $affectedRowsCount = NULL;

    public function update($data, $conditions = [])
    {
        $this->saveMode = 'UPDATE';
        $data           = toObject($data);
        $dataArray      = $this->prepareDbData($data);

        $affectedRowsCount       = $this->q()
            ->where($conditions)
            ->update($dataArray);
        $this->affectedRowsCount = $affectedRowsCount;
        $this->resetFlags();
        $this->attributes = $data;

        return $data;
    }


    /**
     * Updates record by Primary Key.
     * PK could be passed either in $data object or as the second parameter separately.
     */
    public function updateById($data, $id = NULL)
    {
        $data   = toObject($data);
        $pkName = $this->pkName;
        if (isset($id) && !empty($id)) {
            $pkValue = $id;
        } else {
            if (isset($data->{$pkName}) && !empty($data->{$pkName})) {
                $pkValue = $data->{$pkName};
                unset($data->{$pkName});
            }
        }

        if (!isset($pkValue) || empty($pkValue)) {
            $table = $this->table;
            throw new \ErrorException("Cannot UPDATE row in table {$table}. Primary Key is not set.");
        }

        $conditions = [$pkName => $pkValue];

        return $this->update($data, $conditions);
    }

    public function prepareDbData($data)
    {
        $data = toObject($data);
        $this->applyFilters($data);
        $this->validate($data);
        $dataArray = $this->bindModel($data);

        return $dataArray;
    }

    /**
     * Is used AFTER filtering and validation.
     * Responsible to create array of field=>value pairs,
     * AND allows only those field names, which are listed in $this->fields() array.
     * Minimizes conflicts.
     * @param \stdClass $data
     * @return array
     */
    public function bindModel($data)
    {
        $dataArray = [];
        $fields    = $this->fields();
        foreach ($fields as $name => $params) {
            if (property_exists($data, $name)) {
                $dataArray[$name] = $data->{$name};
            }
        }

        return $dataArray;
    }

    /**
     * Filter received $data according $this fields params.
     */
    public function applyFilters(\stdClass $data)
    {

        if ($this->isDataFiltered) return $this;

        $fields = $this->fields();
        foreach ($fields as $name => $params) {
            if (property_exists($data, $name)) {
                $value = $data->{$name};
                if (isset($params['filters']) && !empty($params['filters'])) {
                    foreach ($params['filters'] as $filter) {

                        // The simplest filter
                        if (is_string($filter) && function_exists($filter)) {
                            $data->{$name} = $filter($value);
                        } else if ($filter instanceof \Closure) {
                            $data->{$name} = call_user_func($filter, $data->{$name});;
                        } else if (is_array($filter)) {
                            $argsAmount = count($filter);
                            switch ($argsAmount) {
                                case 2:
                                    list($obj, $method) = $filter;
                                    $data->{$name} = call_user_func([$obj, $method], $data->{$name});
                                    break;
                            }
                        }

                        // ToDo: Maybe more abilities for filter.
                    }
                }
            } else if ($this->saveMode === 'INSERT' && isset($params['default'])) {
                $data->{$name} = $params['default'];
            }
        }

        $this->isDataFiltered = TRUE;

        return $this;
    }

    public function validate(\stdClass $data)
    {

        if ($this->isDataValidated) return $this;

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
                            $vResult = call_user_func($v['f'], $data->{$name});;
                        } else if (is_array($v['f'])) {
                            $argsAmount = count($v['f']);
                            switch ($argsAmount) {
                                case 2:
                                    list($class, $staticMethod) = $v['f'];
                                    $vResult = call_user_func([$class, $staticMethod], $data->{$name});
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

        $this->validateUniqueKeys($data);

        $this->isDataValidated = TRUE;

        return $this;
    }

    public $matchedUniqueFields = [];

    public function validateUniqueKeys($data)
    {

        if ($this->saveMode === 'UPDATE') {
            return $this;
        }

        if ($this->getModelByUniqueKeys($data)) {
            $fields = implode(', ', $this->matchedUniqueFields);
            $table  = $this->table;
            throw new \ErrorException("Fields: {$fields} must be unique in table {$table}");
        }

        return $this;
    }

    public function getModelByUniqueKeys($data)
    {

        $data       = toObject($data);
        $uniqueKeys = $this->uniqueKeys();

        foreach ($uniqueKeys as $uniqueFields) {
            $conditions = [];
            $fields     = [];

            if (!is_array($uniqueFields)) $uniqueFields = [$uniqueFields];

            foreach ($uniqueFields as $uf) {
                if (property_exists($data, $uf)) {
                    $conditions[$uf] = $data->{$uf};
                    $fields[]        = $uf;
                }
                // If $data doesn't contain one of Unique Keys,
                // simply skip this check entirely.
                else {
                    continue 2; // Skips this and previous "foreach".
                }
            }

            // Check if similar model exists.
            $m = new static();
            /*** @var $q \Illuminate\Database\Query\Builder */
            $q = $m->q();
            $q->where($conditions);
            // If $data already contains a field with Primary Key of a model,
            // we should exclude this model while the check.
            if (property_exists($data, $this->pkName) && $data->{$this->pkName} !== '') {
                $q->where($this->pkName, '!=', $data->{$this->pkName});
            }

            // Check only NOT is_deleted
            if ($m->tableHasField('is_deleted')) {
                $q->where('is_deleted', '!=', 1);
            }

            $aRecord = $q->first();

            if (isset($aRecord) && !empty($aRecord)) {
                $this->matchedUniqueFields = $fields;

                return $aRecord;
            }
        }

        return FALSE;
    }

    public function tableHasField($fieldName)
    {
        $fields = $this->fields();
        $fields = array_keys($fields);

        return in_array($fieldName, $fields);
    }

    public function resetFlags()
    {
        $this->saveMode        = NULL;
        $this->isDataFiltered  = FALSE;
        $this->isDataValidated = FALSE;

    }
    #endregion INSERT or Update

    #region DELETE
    public function deleteById($id)
    {
        $pkName  = $this->pkName;
        $pkValue = $id;
        $this->delete([$pkName => $pkValue]);
    }

    public function delete(array $conditions)
    {
        $affectedRowsCount = $this->q()
            ->where($conditions)
            ->delete();

        $this->affectedRowsCount = $affectedRowsCount;
    }
    #endregion DELETE

    #region API, LIMIT, ORDER
    /**
     * @var $sortDefault array
     *  with structure: [['field1', 'DESC'], ['field2', 'ASC']]
     */
    public $sortDefault = [];
    public $sortName    = NULL;
    public $sortAsc     = 'ASC';
    public $page        = -1;
    public $pageSize    = 15;

    /**
     * Prepare paginated API response.
     * @param $q \Illuminate\Database\Query\Builder object
     * @return array with the next structure
     * [
     *  'total' => int total amount of rows,
     *  'page' => int number of page,
     *  'models' => array of objects which represent database rows,
     * ]
     */
    public function apiResponsePaginated($q)
    {


        // COUNT
        $total = $q->count();

        // ORDER
        $this->apiOrder($q);

        // LIMIT partial
        $this->apiLimitOffset($q);

        // Result
        //fDebug($q->toSql());
        $this->collection = $q->get();

        $page   = $this->page;
        $output = ["total" => $total, "page" => $page, "models" => $this->collection];

        //fDebug($output);
        return $output;
    }

    /**
     * Apply ORDER BY to query
     * @param $q \Illuminate\Database\Query\Builder object
     * @return \Illuminate\Database\Query\Builder object
     */
    public function apiOrder($q)
    {
        $sortName      = $this->sortName;
        $this->sortAsc = (!isset($this->sortAsc) || filter_var($this->sortAsc, FILTER_VALIDATE_BOOLEAN) == TRUE) ? 'ASC' : 'DESC';
        $sortAsc       = $this->sortAsc;

        if ($sortName) {
            $q->orderBy($sortName, $sortAsc);

            // ToDo: Comment "return" to support more flexible Sort-Options!
            //return $q;
        }

        // Default sorting.
        $sortDefault = $this->sortDefault;
        if (isset($sortDefault) && !empty($sortDefault) && is_array($sortDefault)) {
            foreach ($sortDefault as $orderBy) {
                list($field, $direction) = $orderBy;
                if ($field !== $sortName)
                    $q->orderBy($field, $direction);
            }
        }

        return $q;
    }

    /**
     * Apply LIMIT/OFFSET to a query
     * @param $q \Illuminate\Database\Query\Builder object
     *  with structure: [['field1', 'DESC'], ['field2', 'ASC']]
     * @param $q \Illuminate\Database\Query\Builder
     * @return \Illuminate\Database\Query\Builder object
     */
    public function apiLimitOffset($q)
    {
        $page     = $this->page;
        $pageSize = $this->pageSize;

        if (FALSE !== ($offset = $this->apiGetRowOffset($page, $pageSize))) {
            $q->skip($offset)->take($pageSize);
        }

        return $q;
    }

    public function apiGetRowOffset($page, $pageSize)
    {
        if (!isset($pageSize) || !isset($page) || !$pageSize || !$page)
            return FALSE;
        //don't do paging if pageSize is set to -1
        if ($pageSize == -1 || $page == -1)
            return FALSE;

        $offset = ($pageSize * ($page - 1));

        return $offset;
    }

    /**
     * Creates $defaultRawObj with default values for DB.
     * @return \stdClass object $defaultRawObj.
     */
    public function getDefaultRawObj()
    {
        $fields        = $this->fields();
        $defaultRawObj = new \stdClass();
        foreach ($fields as $f => $props) {
            $defaultRawObj->$f = NULL;
            if (array_key_exists('default', $props)) {
                $defaultRawObj->$f = $props['default'];
            }
        }

        return $defaultRawObj;
    }
    #endregion API, LIMIT, ORDER

    #region May be Trait.

    /**
     * Rough merge of simple (\stdClass) objects.
     */
    public function merge()
    {
        $objects = func_get_args();
        $count   = count($objects);

        $res = new \stdClass();
        for ($i = 0; $i <= $count - 1; $i++) {
            $o = toObject($objects[$i]);
            foreach ($o as $p => $v) {
                $res->{$p} = $v;
            }
        }

        return $res;
    }
    #endregion May be Trait.
}