<?php

namespace alina\mvc\model;

use \alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader as Loader;
use \Illuminate\Database\Capsule\Manager as Dal;
use \alina\exceptionValidation;

// Laravel initiation
Loader::init();

class _baseAlinaEloquentModel
{
    #region Required
    public $table;
    public $pkName = 'id';
    /** @var \Illuminate\Database\Query\Builder $q */
    public $q;
    public $alias = '';

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

    public function raw($expression)
    {
        return Dal::raw($expression);
    }

    /**
     * Initial list of fields. @see fieldStructureExample
     */
    public function fields()
    {
        return [];
    }

    public function fieldsIdentity()
    {
        return [
            $this->pkName
        ];
    }

    /**
     * Detects if a Field is AutoIncremental.
     * @param $fieldName string
     * @return bool
     */
    public function isFieldIdentity($fieldName)
    {
        $fieldsIdentity = $this->fieldsIdentity();

        return in_array($fieldName, $fieldsIdentity);
    }

    /**
     * Just For Example!!!
     */
    protected function fieldStructureExample()
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
                        'trim'
                    ],
                    'validators' => [
                        [
                            // 'f' - Could be a closure, string with function name or an array
                            'f'       => 'strlen',
                            'errorIf' => [FALSE, 0],
                            'msg'     => 'Please, fill Name'
                        ]
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
    public $req = NULL;

    public function vocGetSearch()
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

            // For Marketing API
            'mid'   => 'marketingId',
            'scd'   => 'startCreatedDate',
            'ecd'   => 'endCreatedDate',
            'doct'  => 'docType',
            'sdd'   => 'startDistributedDate',
            'edd'   => 'endDistributedDate',
            'ds'    => 'docStatus',
            'srd'   => 'startRequiredDate',
            'erd'   => 'endRequiredDate',
            'ct'    => 'clientType',
            'distt' => 'distributionType',

            // For File API
            'kid'   => 'keyId',
            'ft'    => 'fileType',
            'ofid'  => 'openFileId',

            // For Message API
            // 'kid' => 'keyId', // FYI: Same is for Fle.
            'rt'    => 'refType', // ToDo: Reasonable to merge with 'ft'.

            //For Task API.
            'tdt'   => 'taskDefType',
            'tid'   => 'taskId',
        ];
    }

    public function unpackGetParams()
    {
        $this->req    = new \stdClass();
        $vocGetSearch = $this->vocGetSearch();
        foreach ($vocGetSearch as $short => $full) {
            /*
             * NOTE:
             * It sets Property even if it is equal to empty string ''.
             * Check right in API callbacks, if we need to SELECT models, WHERE the Prop is ''.
             * Otherwise, Front-end is to be adjusted to avoid of sending GET params, when they are not necessary.
             */
            //if (isset($_GET[$short]) && $_GET[$short] !=='') {
            if (isset($_GET[$short])) {
                $this->req->{$full} = $_GET[$short];
            }
        }
        // Additional Special Condition $this->sortName, $this->sortAsc
        if (isset($_GET['sa'])) {
            $this->sortAsc = $_GET['sa'];
        }
        if (isset($_GET['sn'])) {
            $this->sortName = $_GET['sn'];
        }

        // Additional Special Condition $this->pageSize, $this->page
        if (isset($_GET['ps'])) {
            $this->pageSize = $_GET['ps'];
        }
        if (isset($_GET['p'])) {
            $this->page = $_GET['p'];
        }

        return $this->req;
    }
    #endregion Search Parameters

    #region SELECT

    public $attributes;
    /**
     * @var  \Illuminate\Support\Collection */
    public $collection = [];

    public function getOne($conditions = [])
    {
        $data = $this->q()->where($conditions)->first();

        $this->attributes = $data;

        //error_log(__FUNCTION__,0);
        //error_log(json_encode($data),0);

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

    /**
     * @param bool $refName
     * @return \Illuminate\Database\Query\Builder
     * @throws \ErrorException
     */
    public function qRefHasOne($refName = FALSE)
    {
        $q = $this->q;

        $references = $this->referencesTo();

        foreach ($references as $rName => $rConf) {
            if ($rConf['has'] === 1) {
                if ($refName && $rName !== $refName) {
                    continue;
                }
                (new qReference($this, $rConf))->qHasOne();
            }
        }

        return $q;
    }

    /**
     * @param $refName
     * @return bool|\Illuminate\Database\Query\Builder[]
     * @throws \ErrorException
     */
    public function qRefHasMany($refName = FALSE)
    {
        $references = $this->referencesTo();
        $qRs         = [];

        foreach ($references as $rName => $rConf) {
            if ($rConf['has'] === 'many') {
                if ($refName && $rName !== $refName) {
                    continue;
                }

                $qR = (new qReference($this, $rConf))->qHasManyThrough();

                $forIds = [];
                if ($this->collection) {
                    $forIds = $this->collection->pluck($this->pkName);
                }
                if ($this->attributes) {
                    $forIds[] = $this->attributes->{$this->pkName};
                }
                if (!empty($forIds)) {
                    $qR->whereIn("glue.{$rConf['refKeys']['pkNameOfParentInGlue']}", $forIds);
                }

                $qRs[$rName] = $qR;
            }
        }

        return $qRs;
    }

    #endregion SELECT

    #region INSERT or Update
    public $saveMode        = NULL; // Could be 'UPDATE' or 'INSERT'
    public $isDataFiltered  = FALSE;
    public $isDataValidated = FALSE;

    public function insert($data)
    {
        //error_log("insert {$this->table}");
        $this->saveMode  = 'INSERT';
        $data            = toObject($data);
        $data            = $this->mergeRawObjects($this->getDefaultRawObj(), $data);
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
        //error_log(__FUNCTION__ . " {$this->table}");
        $this->saveMode = 'UPDATE';
        $data           = toObject($data);

        //Fix: Special for MS SQL: NO PK ON INSERTS.
        if (property_exists($data, $this->pkName)) {
            $presavedId = $data->{$this->pkName};
        }

        $dataArray = $this->prepareDbData($data);

        $affectedRowsCount       = $this->q()
            ->where($conditions)
            ->update($dataArray);
        $this->affectedRowsCount = $affectedRowsCount;
        $this->resetFlags();
        $this->attributes = $data;

        //Get ID back
        if (isset($presavedId)) {
            $data->{$this->pkName} = $presavedId;
        }

        return $data;
    }

    /**
     * Updates record by Primary Key.
     * PK could be passed either in $data object or as the second parameter separately.
     * @param $data array
     * @param null|mixed $id
     * @return mixed
     * @throws \Exception
     * @throws exceptionValidation
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
            throw new exceptionValidation("Cannot UPDATE row in table {$table}. Primary Key is not set.");
        }

        $conditions = [$pkName => $pkValue];

        return $this->update($data, $conditions);
    }

    public function prepareDbData($data, $addAuditInfo = TRUE)
    {
        $data = toObject($data);
        $this->applyFilters($data);
        $this->validate($data);

        if ($addAuditInfo) {
            $this->addAuditInfo($data, $this->saveMode);
        }

        $dataArray = $this->restrictRedundantData($data);

        return $dataArray;
    }

    /**
     * Is used AFTER filtering and validation.
     * Responsible to create array of field=>value pairs,
     * AND allows only those field names, which are listed in $this->fields() array.
     * Minimizes conflicts.
     * It does NOT change input object.
     * @param \stdClass $data
     * @return array
     */
    public function restrictRedundantData($data)
    {
        $dataArray = [];
        $fields    = $this->fields();
        foreach ($fields as $name => $params) {
            if (property_exists($data, $name)) {
                if (!$this->isFieldIdentity($name)) {
                    $dataArray[$name] = $data->{$name};
                }
            }
        }

        return $dataArray;
    }

    /**
     * Add Audit Information to $data object.
     * @param \stdClass $data
     * @param string|null $saveMode
     * @return null
     */
    function addAuditInfo(\stdClass $data, $saveMode = NULL)
    {

        if ($saveMode === NULL) {
            $saveMode = $this->saveMode;
        }

        $user_id = getCurrentUserId();
        $now     = getNow();

        $data->modified_by   = $user_id;
        $data->modified_date = $now;

        if ($saveMode === 'INSERT') {
            $data->created_by   = $user_id;
            $data->created_date = $now;
        }

        return NULL;
    }

    /**
     * Filter received $data according $this fields params.
     * @param \stdClass $data
     * @return $this
     */
    public function applyFilters(\stdClass $data)
    {

        if ($this->isDataFiltered) {
            return $this;
        }

        $fields = $this->fields();
        foreach ($fields as $name => $params) {
            if (property_exists($data, $name)) {
                $value = $data->{$name};
                if (isset($params['filters']) && !empty($params['filters'])) {
                    foreach ($params['filters'] as $filter) {

                        // The simplest filter
                        if (is_string($filter) && function_exists($filter)) {
                            $data->{$name} = $filter($value);
                        } else {
                            if ($filter instanceof \Closure) {
                                $data->{$name} = call_user_func($filter, $data->{$name});;
                            } else {
                                if (is_array($filter)) {
                                    $argsAmount = count($filter);
                                    switch ($argsAmount) {
                                        case 2:
                                            list($obj, $method) = $filter;
                                            $data->{$name} = call_user_func([$obj, $method], $data->{$name});
                                            break;
                                    }
                                }
                            }
                        }
                        // ToDo: Maybe more abilities for filter.
                    }
                }
            } else {
                if ($this->saveMode === 'INSERT' && isset($params['default'])) {
                    $data->{$name} = $params['default'];
                }
            }
        }

        $this->isDataFiltered = TRUE;

        return $this;
    }

    public function validate(\stdClass $data)
    {

        if ($this->isDataValidated) {
            return $this;
        }

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
                        } else {
                            if ($v['f'] instanceof \Closure) {
                                $vResult = call_user_func($v['f'], $data->{$name});;
                            } else {
                                if (is_array($v['f'])) {
                                    $argsAmount = count($v['f']);
                                    switch ($argsAmount) {
                                        case 2:
                                            list($class, $staticMethod) = $v['f'];
                                            $vResult = call_user_func([$class, $staticMethod], $data->{$name});
                                            break;
                                    }
                                }
                            }
                        }
                        // ToDo: Maybe more abilities for validation.

                        // Validation Result process.
                        if (in_array($vResult, $errorIf)) {
                            throw new exceptionValidation($msg);
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
            throw new exceptionValidation("Fields: {$fields} must be unique in table {$table}");
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

            if (!is_array($uniqueFields)) {
                $uniqueFields = [$uniqueFields];
            }

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
    public function smartDeleteById($id, $additionalData = NULL)
    {
        if ($this->tableHasField('is_deleted') || (isset($additionalData) && !empty($additionalData))) {
            $pkName = $this->pkName;

            $data = (isset($additionalData) && !empty($additionalData))
                ? toObject($additionalData)
                : new \stdClass();
            // Even if there is no is_deleted in this->fields, it does not brings error
            // due to $this->bindModel functionality.
            $data->is_deleted = 1;
            $data->{$pkName}  = $id;

            // When $data contains Primary Key, there is ni necessity to set it as the second parameter.
            $this->updateById($data);
        } else {
            // If table does not participate in Audit process,
            // simply DELETE row from database.
            $this->deleteById($id);
        }
    }

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

        return $this;
    }
    #endregion DELETE

    #region Transaction.
    // This functionality is moved to @file api/integration/illuminate-extend/class.Transaction.php
    #endregion Transaction.

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
     * @return array with the next structure
     * [
     *  'total' => int total amount of rows,
     *  'page' => int number of page,
     *  'models' => array of objects which represent database rows,
     * ]
     */
    public function apiResponsePaginated()
    {
        /** @var $q \Illuminate\Database\Query\Builder object */
        $q = $this->q;

        // COUNT
        $total = $q->count();

        // ORDER
        $this->apiOrder();

        // LIMIT partial
        $this->apiLimitOffset();

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
     * @return \Illuminate\Database\Query\Builder object
     */
    public function apiOrder()
    {
        /** @var $q \Illuminate\Database\Query\Builder object */
        $q = $this->q;

        // User Defined Sort parameters.
        $sortArray = $this->apiProcessSortNameSortAscData($this->sortName, $this->sortAsc);
        if (empty($sortArray)) {
            $sortArray = $this->sortDefault;
        }

        //$sortArray = array_merge($sortArray, $this->sortDefault);
        $this->orderByArray($sortArray);

        return $q;
    }

    public function apiProcessSortNameSortAscData($sortName, $sortAsc)
    {

        if (empty($sortName)) {
            return NULL;
        }

        // Sorting functionality
        // Pre-saving backward compatibility.

        $sn = explode(',', $sortName);
        $sa = explode(',', $sortAsc);

        $sortArray = [];
        foreach ($sn as $i => $n) {
            $asc         = isset($sa[$i]) ? getSqlDirection($sa[$i]) : 'ASC';
            $sortArray[] = [$n, $asc];
        }

        return $sortArray;
    }

    /**
     * Apply complex ORDER BY to query
     * @param $orderArray array
     * @return \Illuminate\Database\Query\Builder object
     */
    public function orderByArray($orderArray = [])
    {
        /** @var $q \Illuminate\Database\Query\Builder object */
        $q = $this->q;

        if (empty($orderArray)) {
            return $q;
        }

        if (is_string($orderArray)) {
            $orderArray = [[$orderArray, 'ASC']];
        }

        foreach ($orderArray as $orderBy) {
            if (count($orderBy) !== 2) {
                //ToDo: Validate all necessary parameters.
                continue;
            }

            list($field, $direction) = $orderBy;
            $q->orderBy($field, $direction);
        }

        return $q;
    }

    /**
     * Apply LIMIT/OFFSET to a query
     * @return \Illuminate\Database\Query\Builder object
     */
    public function apiLimitOffset()
    {
        /** @var $q \Illuminate\Database\Query\Builder object */
        $q        = $this->q;
        $page     = $this->page;
        $pageSize = $this->pageSize;

        if (FALSE !== ($offset = $this->apiGetRowOffset($page, $pageSize))) {
            $q->skip($offset)->take($pageSize);
        }

        return $q;
    }

    public function apiGetRowOffset($page, $pageSize)
    {
        if (!isset($pageSize) || !isset($page) || !$pageSize || !$page) {
            return FALSE;
        }
        //don't do paging if pageSize is set to -1
        if ($pageSize == -1 || $page == -1) {
            return FALSE;
        }

        $offset = ($pageSize * ($page - 1));

        return $offset;
    }

    /**
     * Add Audit Info if possible.
     * @return \Illuminate\Database\Query\Builder object
     */
    public function apiJoinAuditInfo()
    {
        /** @var $q \Illuminate\Database\Query\Builder object */
        $q          = $this->q;
        $thisFields = $this->fields();
        $alias      = $this->alias;

        // Join Creator if possible
        if (array_key_exists('created_by', $thisFields)) {
            $q->addSelect([
                'pc.first_name as created_first_name',
                'pc.last_name as created_last_name'
            ]);
            $q->leftJoin('person as pc', 'pc.person_id', '=', "{$alias}.created_by");
        }

        if (array_key_exists('modified_by', $thisFields)) {
            $q->addSelect([
                'pm.first_name as modified_first_name',
                'pm.last_name as modified_last_name'
            ]);
            $q->leftJoin('person as pm', 'pm.person_id', '=', "{$alias}.modified_by");
        }

        return $q;
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
            //$defaultRawObj->$f = null; //ToDo: It affects Primary Key!!!
            if (array_key_exists('default', $props)) {
                $defaultRawObj->$f = $props['default'];
            }
        }

        return $defaultRawObj;
    }
    #endregion API, LIMIT, ORDER

    #region May be Trait.

    /**
     * Rough merge of simple (stdClass) objects.
     * @throws \Exception
     */
    public function mergeRawObjects()
    {
        //error_log('merge', 0);
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

    /**
     * In order to unify source data for methods.
     * Converts source data to array of objects,
     * where each object is an appropriate DB row.
     * @param $d
     * @return array
     */
    public function toArrayOfObjects($d)
    {
        if (empty($d)) {
            $d = $this->collection;
        }
        if (empty($d)) {
            $d = $this->attributes;
        }
        if (empty($d)) {
            $d = [];
        }
        if (!is_array($d)) {
            $d = [$d];
        }

        return $d;
    }
    #endregion May be Trait.

    #region relations

    public function referencesTo()
    {
        return [];
    }

    public function isFieldReference($fName)
    {
        $references = $this->referencesTo();

        return array_key_exists($fName, $references) && $references[$fName]['has'] === 1;
    }
    #endregion relations
}