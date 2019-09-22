<?php

namespace alina\mvc\model;

use alina\message;
use \alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader as Loader;
use \Illuminate\Database\Capsule\Manager as Dal;
use \alina\exceptionValidation;

// Laravel initiation
Loader::init();

class _BaseAlinaModel
{
    #region Required
    public $table;
    public $alias  = '';
    public $pkName = 'id';
    /** @var \Illuminate\Database\Query\Builder $q */
    public    $q;
    public $req          = NULL;
    public $apiOperators = [
        'lt_' => '<',
        'gt_' => '>',
        'eq_' => '=',
        'lk_' => 'LIKE',
    ];
    public $attributes;
    /**
     * @var  \Illuminate\Support\Collection
     */
    public $collection = [];
    #endregion Required

    #region Fields Definitions
public $mode            = 'SELECT';

    #region Identity (AutoIncremental)
    public $isDataFiltered  = FALSE;
    public $isDataValidated = FALSE;
    #endregion Identity (AutoIncremental)

    #region Unique Fields
    public $affectedRowsCount = NULL;
    #endregion Unique Fields
    public $dataArrayIdentity;
    public $matchedUniqueFields = [];
    #emdregion Fields Definitions

    #region Search Parameters
    /**
     * @var $sortDefault array
     *  with structure: [['field1', 'DESC'], ['field2', 'ASC']]
     */
    public $sortDefault       = [];
    public $sortName          = NULL;
    public $sortAsc           = 'ASC';
    public $pageCurrentNumber = 0;
    public $pageSize          = 15;
    public $rowsTotal         = -1;
    #endregion Search Parameters

    #region SELECT
    public $pagesTotal        = 0;
    protected $opts;

    public function __construct($opts = NULL)
    {
        if ($opts) {
            $opts       = \alina\utils\Data::toObject($opts);
            $this->opts = $opts;
            $opts->table ? $this->table = $opts->table : NULL;
        }
        $this->alias      = $this->table;
        $this->attributes = new \stdClass;
    }

    public function raw($expression)
    {
        return Dal::raw($expression);
    }

    public function getFieldsMetaInfo()
    {
        $fields   = $this->fields();
        $pkName   = $this->pkName;
        $identity = $this->fieldsIdentity();
        $unique   = $this->uniqueKeys();

        return [
            'fields'   => $fields,
            'pkName'   => $pkName,
            'identity' => $identity,
            'unique'   => $unique,
        ];
    }
    #endregion SELECT

    #region INSERT or Update

    /**
     * Initial list of fields. @see fieldStructureExample
     */
    public function fields()
    {
        $table  = $this->table;
        $m      = new static(['table' => $table]);
        $q      = $m->q();
        $item   = $q->first();
        $fields = [];
        foreach ($item as $i => $v) {
            $fields[$i] = [];
        }

        return $fields;
    } // Could be 'SELECT', 'UPDATE', 'INSERT', 'DELETE'

    /**
     * @param string $alias
     * @return \Illuminate\Database\Query\Builder
     */
    public function q($alias = NULL)
    {
        /**
         * ATTENTION: Important security fix in order to avoid accident start of a query,
         * while a previous is in progress.
         */
        if (isset($this->q) && !empty($this->q)) {
            $this->q = NULL;
            message::set("ATTENTION! {$this->table} query is redefined!!!");
            //error_log(__FUNCTION__,0);
            //error_log(json_encode(debug_backtrace()[1]['function']),0);
        }

        $this->alias = $alias ? $alias : $this->alias;
        if ($this->mode === 'INSERT' || $this->mode === 'DELETE') {
            $this->q = Dal::table("{$this->table}");
        } else {
            $this->q = Dal::table("{$this->table} AS {$this->alias}");
        }

        return $this->q;
    }

    /**
     * Returns array of arrays.
     * Nested arrays contain list of field names, which are Unique Keys together.
     * Example:
     * [
     *  ['email'],
     *  ['row', 'column'],
     * ]
     */
    public function uniqueKeys()
    {
        return [];
    }

    public function getById($id)
    {
        return $this->getOne([$this->pkName => $id]);
    }

    public function getOne($conditions = [])
    {
        $data = $this->q()->where($conditions)->first();

        $this->attributes = $data;

        return $data;
    }

    public function getAll($conditions = [])
    {
        $this->collection = $this->q()->where($conditions)->get();

        return $this->collection;
    }

    public function insert($data)
    {
        $this->mode       = 'INSERT';
        $pkName           = $this->pkName;
        $data             = \alina\utils\Data::toObject($data);
        $data             = $this->mergeRawObjects($this->getDefaultRawObj(), $data);
        $dataArray        = $this->prepareDbData($data);
        $id               = $this->q()->insertGetId($dataArray, $pkName);
        $this->attributes = $data = \alina\utils\Data::toObject($dataArray);
        $data->{$pkName}  = $this->{$pkName} = $id;
        $this->resetFlags();

        return $data;
    }

    /**
     * Rough merge of simple (stdClass) objects.
     * @throws \Exception
     */
    public function mergeRawObjects()
    {
        $objects = func_get_args();
        $count   = count($objects);

        $res = new \stdClass();
        for ($i = 0; $i <= $count - 1; $i++) {
            $o = \alina\utils\Data::toObject($objects[$i]);
            foreach ($o as $p => $v) {
                $res->{$p} = $v;
            }
        }

        return $res;
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

    public function prepareDbData($data, $addAuditInfo = TRUE)
    {
        $data = \alina\utils\Data::toObject($data);
        $this->applyFilters($data);
        $this->validate($data);

        if ($addAuditInfo) {
            $this->addAuditInfo($data, $this->mode);
        }

        $dataArray = $this->restrictIdentityAutoincrementReadOnlyFields($data);

        return $dataArray;
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
                if ($this->mode === 'INSERT' && isset($params['default'])) {
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
                        $errorIf = (isset($v['errorIf']))
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
                                $vResult = call_user_func($v['f'], $value);;
                            } else {
                                if (is_array($v['f'])) {
                                    $argsAmount = count($v['f']);
                                    switch ($argsAmount) {
                                        case 2:
                                            list($class, $staticMethod) = $v['f'];
                                            $vResult = call_user_func([$class, $staticMethod], $value);
                                            break;
                                    }
                                }
                            }
                        }
                        // ToDo: Maybe more abilities for validation.

                        // Validation Result process.
                        if (in_array($vResult, $errorIf, TRUE)) {
                            $errorIfstr = implode('|||', $errorIf);
                            throw new exceptionValidation("
                            Name:   {$name} ::: 
                            Value:  {$value} ::: 
                            Res:    {$vResult} ::: 
                            ErrorIf:{$errorIfstr} ::: 
                            Message:{$msg}
                            ");
                        }
                    }
                }
            }
        }

        $this->validateUniqueKeys($data);

        $this->isDataValidated = TRUE;

        return $this;
    }

    public function validateUniqueKeys($data)
    {

        if ($this->mode === 'UPDATE') {
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

        $data       = \alina\utils\Data::toObject($data);
        $uniqueKeys = $this->uniqueKeys();

        foreach ($uniqueKeys as $uniqueFields) {
            $conditions = [];
            $uFields    = [];

            if (!is_array($uniqueFields)) {
                $uniqueFields = [$uniqueFields];
            }

            foreach ($uniqueFields as $uf) {
                if (property_exists($data, $uf)) {
                    $conditions[$uf] = $data->{$uf};
                    $uFields[]       = $uf;
                }
                // If $data doesn't contain one of Unique Keys,
                // simply skip this check entirely.
                else {
                    continue 2; // Skips this and previous "foreach".
                }
            }

            // Check if similar model exists.
            $m = new static(['table' => $this->table]);
            /*** @var $q \Illuminate\Database\Query\Builder */
            $q = $m->q();
            $q->where($conditions);
            // If $data already contains a field with Primary Key of a model,
            // we should exclude this model while the check.
            if (property_exists($data, $this->pkName) && !empty($data->{$this->pkName})) {
                $q->where($this->pkName, '!=', $data->{$this->pkName});
            }

            // Check only NOT is_deleted
            if ($m->tableHasField('is_deleted')) {
                $q->where('is_deleted', '!=', 1);
            }

            $aRecord = $q->first();

            if (isset($aRecord) && !empty($aRecord)) {
                $this->matchedUniqueFields = $uFields;

                return $aRecord;
            }
        }

        return FALSE;
    }

    public function tableHasField($fieldName)
    {
        return array_key_exists($fieldName, $this->fields());
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
            $saveMode = $this->mode;
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
     * Is used AFTER filtering and validation.
     * Responsible to create array of field=>value pairs,
     * AND allows only those field names, which are listed in $this->fields() array.
     * Minimizes conflicts.
     * It does NOT change input object.
     * @param \stdClass $data
     * @return array
     */
    public function restrictIdentityAutoincrementReadOnlyFields($data)
    {
        $dataArray = [];
        $fields    = $this->fields();
        foreach ($fields as $name => $params) {
            if (property_exists($data, $name)) {
                if ($this->isFieldIdentity($name)) {
                    $this->dataArrayIdentity[$name] = $data->{$name};
                } else {
                    $dataArray[$name] = $data->{$name};
                }
            }
        }

        return $dataArray;
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
    #endregion INSERT or Update

    #region DELETE

    public function fieldsIdentity()
    {
        return [
            $this->pkName
        ];
    }

    public function resetFlags()
    {
        $this->mode            = 'SELECT';
        $this->isDataFiltered  = FALSE;
        $this->isDataValidated = FALSE;
    }

    public function smartDeleteById($id, $additionalData = NULL)
    {
        if ($this->tableHasField('is_deleted') || (isset($additionalData) && !empty($additionalData))) {
            $pkName = $this->pkName;

            $data = (isset($additionalData) && !empty($additionalData))
                ? \alina\utils\Data::toObject($additionalData)
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
    #endregion DELETE

    #region Transaction.
    // This functionality is moved to @file _backend/alina/mvc/model/_baseAlinaEloquentTransaction.php
    #endregion Transaction.

    #region API, LIMIT, ORDER

    /**
     * Updates record by Primary Key.
     * PK could be passed either in $data object or as the second parameter separately.
     * @param $data array|\stdClass
     * @param null|mixed $id
     * @return mixed
     * @throws \Exception
     * @throws exceptionValidation
     */
    public function updateById($data, $id = NULL)
    {
        $data   = \alina\utils\Data::toObject($data);
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

        $conditions       = [$pkName => $pkValue];
        $this->attributes = $this->update($data, $conditions);
        $this->{$pkName}  = $this->attributes->{$pkName} = $pkValue;
        if ($this->affectedRowsCount != 1) {
            message::set("There are no changes in {$this->table} of ID {$pkValue}");
        }

        return $this->attributes;
    }

    public function update($data, $conditions = [])
    {
        $this->mode = 'UPDATE';
        $pkName     = $this->pkName;
        $data       = \alina\utils\Data::toObject($data);

        //Fix: Special for MS SQL: NO PK ON INSERTS.
        if (property_exists($data, $pkName)) {
            $presavedId = $data->{$pkName};
            //IMPORTANT: unset of $this->id happens in prepareDbData then.
        }

        $dataArray = $this->prepareDbData($data);

        $this->affectedRowsCount =
            $this->q()
                ->where($conditions)
                ->update($dataArray);
        $this->attributes        = \alina\utils\Data::toObject($dataArray);
        //Get ID back
        if (isset($presavedId)) {
            $this->{$pkName} = $this->attributes->{$pkName} = $presavedId;
        }
        $this->resetFlags();

        return $this->attributes;
    }

    public function deleteById($id)
    {
        $pkName  = $this->pkName;
        $pkValue = $id;
        $this->delete([$pkName => $pkValue]);
    }

    public function delete(array $conditions)
    {
        $this->mode = 'DELETE';

        $affectedRowsCount = $this->q()
            ->where($conditions)
            ->delete();

        $this->affectedRowsCount = $affectedRowsCount;
        $this->resetFlags();

        return $this;
    }

    /**
     * Prepare paginated API response.
     * @return array with the next structure
     * [
     *  'total' => int total amount of rows,
     *  'page' => int number of page,
     *  'models' => array of objects which represent database rows,
     * ]
     */
    public function qApiResponsePaginated()
    {
        /** @var $q \Illuminate\Database\Query\Builder object */
        $q = $this->q;

        // COUNT
        $total = $q->count();

        // ORDER
        $this->qApiOrder();

        // LIMIT partial
        $this->qApiLimitOffset();

        // Result
        //fDebug($q->toSql());
        $this->collection = $q->get();

        $page   = $this->pageCurrentNumber;
        $output = ["total" => $total, "page" => $page, "models" => $this->collection];

        //fDebug($output);
        return $output;
    }

    /**
     * Apply ORDER BY to query
     * @param array array $backendSortArray
     * @return \Illuminate\Database\Query\Builder object
     */
    public function qApiOrder($backendSortArray = [])
    {
        /** @var $q \Illuminate\Database\Query\Builder object */
        $q = $this->q;

        // User Defined Sort parameters.
        $sortArray = $this->calcSortNameSortAscData($this->sortName, $this->sortAsc);
        if (empty($sortArray)) {
            $sortArray = $this->sortDefault;
        }

        //Finally if function is called from backend...
        if (isset($backendSortArray) && !empty($backendSortArray)) {
            $sortArray = $backendSortArray;
        }

        //$sortArray = array_merge($sortArray, $this->sortDefault);
        $this->qOrderByArray($sortArray);

        return $q;
    }

    public function calcSortNameSortAscData($sortName, $sortAsc)
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
            $asc         = isset($sa[$i]) ? \alina\utils\Data::getSqlDirection($sa[$i]) : 'ASC';
            $sortArray[] = [$n, $asc];
        }

        return $sortArray;
    }

    /**
     * Apply complex ORDER BY to query
     * @param $orderArray array
     * @return \Illuminate\Database\Query\Builder object
     */
    public function qOrderByArray($orderArray = [])
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
     * @param int|null $backendLimit
     * @param int|null $backendOffset
     * @return \Illuminate\Database\Query\Builder object
     */
    public function qApiLimitOffset($backendLimit = NULL, $backendOffset = NULL)
    {
        /** @var $q \Illuminate\Database\Query\Builder object */
        $q                 = $this->q;
        $pageCurrentNumber = $this->pageCurrentNumber;
        $pageSize          = $this->pageSize;
        $rowsTotal         = $this->rowsTotal;

        if ($rowsTotal <= $pageSize) {
            $pageCurrentNumber = $this->pageCurrentNumber = 1;
        }

        $this->calcPagesTotal($rowsTotal, $pageSize);
        if ($pageCurrentNumber > $this->pagesTotal) {
            $pageCurrentNumber = $this->pageCurrentNumber = 1;
        }

        if (FALSE !== ($offset = $this->calcOffset($pageCurrentNumber, $pageSize))) {
            $q->skip($offset)->take($pageSize);
        }

        //Finally: if LIMIT and OFFSET are passed via back-end...
        if ($backendLimit) {
            $q->take($backendLimit);
        }
        if ($backendOffset) {
            $q->skip($backendOffset);
        }

        return $q;
    }

    public function calcPagesTotal($rowsTotal, $pageSize)
    {
        if ($pageSize <= 0) {
            $pageSize = $rowsTotal;
        }
        $this->pagesTotal = ceil($rowsTotal / $pageSize);

        return $this->pagesTotal;
    }

    public function calcOffset($pageCurrentNumber, $pageSize)
    {
        if (!isset($pageSize) || !isset($pageCurrentNumber) || $pageSize <= 0 || $pageCurrentNumber <= 0) {
            return FALSE;
        }

        $offset = ($pageSize * ($pageCurrentNumber - 1));

        return $offset;
    }

    /**
     * Add Audit Info if possible.
     * @return \Illuminate\Database\Query\Builder object
     */
    public function qApiJoinAuditInfo()
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

    public function referencesTo() { return []; }

    public function getAllWithReferences($conditions = [], $backendSortArray = NULL, $limit = NULL, $offset = NULL)
    {
        //First of all.
        $this->apiUnpackGetParams();

        $q = $this->q();
        $q->select(["{$this->alias}.*"]);
        //API WHERE
        $q->where($conditions);
        $this->qApplyGetSearchParams();
        //ORDER
        $this->qApiOrder($backendSortArray);
        //Has One JOINs.
        $this->qJoinHasOne();
        //COUNT
        $this->rowsTotal = $q->count();
        //LIMIT / OFFSET
        $this->qApiLimitOffset($limit, $offset);
        //Execute query.
        $this->collection = $q->get();

        //Has Many JOINs.
        $this->joinHasMany();

        return $this->collection;
    }

    /**
     * Sets $this->req
     */
    public function apiUnpackGetParams()
    {
        $this->req    = new \stdClass();
        $vocGetSearch = $this->vocGetSearch();

//        error_log('vocGetSearch', 0);
//        error_log(json_encode($vocGetSearch), 0);

        foreach ($vocGetSearch as $short => $full) {
            /*
             * NOTE:
             * It sets Property even if it is equal to empty string ''.
             * Check right in API callbacks, if we need to SELECT models, WHERE the Prop is ''.
             * Otherwise, Front-end is to be adjusted to avoid of sending GET params, when they are not necessary.
             */
            //if (isset($_GET[$short]) && $_GET[$short] !=='') {
            if
            (isset($_GET[$short])) {
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

        //ToDo: Rename to pn.
        if (isset($_GET['p'])) {
            $this->pageCurrentNumber = $_GET['p'];
        }

        return $this->req;
    }
    #endregion API, LIMIT, ORDER

    #region May be Trait.

    public function vocGetSearch()
    {
        $vocGetSearchSpecial = [];
        if (method_exists($this, 'vocGetSearchSpecial')) {
            $vocGetSearchSpecial = $this->vocGetSearchSpecial();
        }

        $vocGetSearch = [
            // Pagination
            'ps' => 'pageSize',
            'p'  => 'page',

            // Sorting functionality
            'sa' => 'sortAsc',
            'sn' => 'sortName',

            // Search
            'q'  => 'search',
        ];

        return array_merge($vocGetSearchSpecial, $vocGetSearch);
    }

    public function vocGetSearchSpecial()
    {
        $fields = $this->fields();
        $fNames = array_keys($fields);
        $res    = [];
        foreach ($fNames as $f) {
            foreach ($_GET as $gF => $gV) {
                if (\alina\utils\Str::endsWith($gF, $f)) {
                    $res[$gF] = $gF;
                }
            }
        }

        return $res;
    }
    #endregion May be Trait.
    ##################################################
    #region relations

    public function qApplyGetSearchParams()
    {
        //ToDo: Check $q, $req emptiness.
        $q   = $this->q;
        $req = $this->req;

        error_log('$req', 0);
        error_log(json_encode($req), 0);

        foreach ($req as $f => $v) {

            $t = $this->alias;

            //The simplest search case.
            if ($this->tableHasField($f)) {
                if (is_array($v)) {
                    $q->whereIn("{$t}.{$f}", $v);
                } else {
                    $q->where("{$t}.{$f}", 'LIKE', "%{$v}%");
                }
            }

            //API GET operators.
            $apiOperators = $this->apiOperators;
            foreach ($apiOperators as $o => $oV) {
                if (\alina\utils\Str::startsWith($f, $o)) {
                    $fName = implode('', explode($o, $f, 2));
                    if ($this->tableHasField($fName)) {
                        switch ($o) {
                            case ('lt_'):
                                $q->where("{$t}.{$fName}", '<', $v);
                                break;
                            case ('gt_'):
                                $q->where("{$t}.{$fName}", '>', $v);
                                break;
                            case ('eq_'):
                                $q->where("{$t}.{$fName}", '=', $v);
                                break;
                            case ('lk_'):
                                $q->where("{$t}.{$fName}", 'LIKE', "%{$v}%");
                                break;
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function qJoinHasOne()
    {
        (new referenceProcessor($this))->joinHasOne();
    }

    public function joinHasMany()
    {
        $forIds        = $this->collection->pluck($this->pkName);
        $qHasManyArray = (new referenceProcessor($this))->joinHasMany([], $forIds);
        foreach ($qHasManyArray as $rName => $q) {
            $qResult = $q->get();
            foreach ($this->collection as $thisModelAttributes) {
                $thisModelAttributes->{$rName} = [];
                foreach ($qResult as $row) {
                    if ($thisModelAttributes->{$this->pkName} === $row->main_id) {
                        $thisModelAttributes->{$rName}[] = $row;
                    }
                }
            }
        }
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
                    'tye'        => ['string', 'number', 'etc'],
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
    #endregion relations
}
