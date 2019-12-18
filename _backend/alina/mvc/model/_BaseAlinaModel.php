<?php

namespace alina\mvc\model;

use alina\message;
use alina\utils\Data;
use alina\utils\Str;
use \alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader as Loader;
use Exception;
use \Illuminate\Database\Capsule\Manager as Dal;
use \alina\exceptionValidation;
use Illuminate\Database\Query\Builder as BuilderAlias;
use Illuminate\Support\Collection as CollectionAlias;

// Laravel initiation
Loader::init();

class _BaseAlinaModel
{
    #region Required
    public    $table;
    public    $alias  = '';
    public    $pkName = 'id';
    public    $id     = NULL;
    protected $opts;
    public    $dataArrayIdentity;
    #endregion Required
    ##################################################
    #region Request
    /** @var BuilderAlias $q */
    public $q;
    public $o_GET        = NULL;
    public $apiOperators = [
        'lt_' => '<',
        'gt_' => '>',
        'eq_' => '=',
        'lk_' => 'LIKE',
    ];
    #endregion Request
    ##################################################
    #region Response
    /**@var  \stdClass */
    public $attributes;
    /**@var  CollectionAlias */
    public $collection = [];
    public $rowsTotal  = -1;
    public $pagesTotal = 0;
    #endregion Response
    ##################################################
    #region Flags, CHeck-Points
    public $mode                = 'SELECT';// Could be 'SELECT', 'UPDATE', 'INSERT', 'DELETE'
    public $isDataFiltered      = FALSE;
    public $isDataValidated     = FALSE;
    public $affectedRowsCount   = NULL;
    public $matchedUniqueFields = [];
    public $matchedConditions   = [];
    #emdregion Flags, CHeck-Points
    ##################################################
    #region Search Parameters

    /**
     * @var $sortDefault array
     * [['field1', 'DESC'], ['field2', 'ASC']]
     */
    public $sortDefault       = [];
    public $sortName          = NULL;
    public $sortAsc           = 'ASC';
    public $pageCurrentNumber = 0;
    public $pageSize          = 15;
    #endregion Search Parameters
    ##################################################
    #region Constructor
    public function __construct($opts = NULL)
    {
        $this->attributes = (object)[];
        $this->setPkValue(NULL, $this->attributes);
        if ($opts) {
            $opts       = Data::toObject($opts);
            $this->opts = $opts;
            if (isset($opts->table)) {
                $this->table = $opts->table;
            }
        }
        $this->alias = $this->table;
        $this->buildDefaultData();
    }
    #endregion Constructor
    ##################################################
    #region SELECT
    public function getById($id)
    {
        return $this->getOne([$this->pkName => $id]);
    }

    public function getOne($conditions = [])
    {
        $data = $this->q()->where($conditions)->first();
        if (empty($data)) {
            $data = (object)[];
        }
        if (isset($data->{$this->pkName})) {
            $this->setPkValue($data->{$this->pkName}, $data);
        }
        $this->attributes = Data::mergeObjects($this->attributes, $data);

        return $this;
    }

    public function getAll($conditions = [])
    {
        $this->collection = $this->q()->where($conditions)->get()->keyBy($this->pkName);

        return $this->collection;
    }

    protected function getModelByUniqueKeys($data)
    {

        $data       = Data::toObject($data);
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
            /*** @var $q BuilderAlias */
            $q = $m->q();
            $q->where($conditions);

            /*
             * If $data already contains a field with Primary Key of a model,
             * we should exclude this model while the check.
             */
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
                $this->matchedConditions   = $conditions;
                $this->attributes          = $aRecord;
                $this->setPkValue($aRecord->{$this->pkName}, $this->attributes);

                return $aRecord;
            }
        }

        return FALSE;
    }

    #endregion SELECT
    ##################################################
    #region UPSERT
    public function upsert($data)
    {
        $data = Data::toObject($data);
        if (isset($data->{$this->pkName}) && !empty($data->{$this->pkName})) {
            $this->updateById($data);
        } else {
            $this->insert($data);
        }

        return $this;
    }

    public function upsertByUniqueFields($data)
    {
        $aRecord = $this->getModelByUniqueKeys($data);
        if ($aRecord) {
            $conditions = $this->matchedConditions;
            $this->update($data, $conditions);
        } else {
            $this->insert($data);
        }

        return $this;
    }
    #endregion UPSERT
    ##################################################
    #region INSERT
    public function insert($data)
    {
        $this->mode = 'INSERT';
        $pkName     = $this->pkName;
        $data       = Data::toObject($data);
        $data       = Data::mergeObjects($this->buildDefaultData(), $data);
        $dataArray  = $this->prepareDbData($data);
        #####
        if (method_exists($this, 'hookRightBeforeSave')) {
            $this->hookRightBeforeSave($dataArray);
        }
        #####
        $id               = $this->q()->insertGetId($dataArray, $pkName);
        $this->attributes = $data = Data::toObject($dataArray);
        $this->setPkValue($id, $this->attributes);
        $this->setPkValue($id, $data);
        #####
        if (method_exists($this, 'hookRightAfterSave')) {
            $this->hookRightAfterSave($data);
        }
        #####
        $this->resetFlags();

        return $data;
    }
    #endregion INSERT
    ##################################################
    #region UPDATE
    /**
     * Updates record by Primary Key.
     * PK could be passed either in $data object or as the second parameter separately.
     * @param $data array|\stdClass
     * @param null|mixed $id
     * @return mixed
     * @throws Exception
     * @throws exceptionValidation
     */
    public function updateById($data, $id = NULL)
    {
        $data   = Data::toObject($data);
        $pkName = $this->pkName;
        if (isset($id) && !empty($id)) {
            $pkValue = $id;
        } else {
            if (isset($data->{$pkName}) && !empty($data->{$pkName})) {
                $pkValue = $data->{$pkName};
            } else {
                if (isset($this->id) && !empty($this->id)) {
                    $pkValue = $this->id;
                }
            }
        }

        if (!isset($pkValue) || empty($pkValue)) {
            $table = $this->table;
            throw new exceptionValidation("Cannot UPDATE row in table {$table}. Primary Key is not set.");
        }

        $conditions = [$pkName => $pkValue];
        $this->update($data, $conditions);
        $this->attributes = Data::mergeObjects($this->attributes, $data);
        $this->{$pkName}  = $this->attributes->{$pkName} = $data->{$pkName} = $pkValue;
        $this->id         = $pkValue;

        return $this;
    }

    public function update($data, $conditions = [])
    {
        $this->mode = 'UPDATE';
        $pkName     = $this->pkName;
        $data       = Data::toObject($data);
        $dataArray  = $this->prepareDbData($data);
        ##################################################
        if (method_exists($this, 'hookRightBeforeSave')) {
            $this->hookRightBeforeSave($dataArray);
        }
        ##################################################
        $this->affectedRowsCount =
            $this->q()
                ->where($conditions)
                ->update($dataArray);
        if ($this->affectedRowsCount == 1) {
            $this->attributes = Data::mergeObjects($this->attributes, Data::toObject($dataArray));
            if (isset($this->attributes->{$this->pkName})) {
                $this->setPkValue($this->attributes->{$this->pkName});
            }
        }
        ##################################################
        if (method_exists($this, 'hookRightAfterSave')) {
            $this->hookRightAfterSave($data);
        }
        ##################################################
        $this->resetFlags();

        return $this;
    }
    #endregion UPDATE
    ##################################################
    #region DELETE
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

    public function deleteById($id)
    {
        $pkName  = $this->pkName;
        $pkValue = $id;
        $this->delete([$pkName => $pkValue]);
    }

    public function smartDeleteById($id, $additionalData = NULL)
    {
        if ($this->tableHasField('is_deleted') || (isset($additionalData) && !empty($additionalData))) {
            $pkName = $this->pkName;

            $data = (isset($additionalData) && !empty($additionalData))
                ? Data::toObject($additionalData)
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
    ##################################################
    #region API, LIMIT, ORDER

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
        /** @var $q BuilderAlias object */
        $q = $this->q;

        // COUNT
        $total = $q->count();

        // ORDER
        $this->qApiOrder();

        // LIMIT partial
        $this->qApiLimitOffset();

        // Result
        //fDebug($q->toSql());
        $this->collection = $q->get()->keyBy($this->pkName);

        $page   = $this->pageCurrentNumber;
        $output = ["total" => $total, "page" => $page, "models" => $this->collection];

        //fDebug($output);
        return $output;
    }

    /**
     * Apply ORDER BY to query
     * @param array array $backendSortArray
     * @return BuilderAlias object
     */
    protected function qApiOrder($backendSortArray = [])
    {
        /** @var $q BuilderAlias object */
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

    /**
     * Apply complex ORDER BY to query
     * @param $orderArray array
     * @return BuilderAlias object
     */
    protected function qOrderByArray($orderArray = [])
    {
        /** @var $q BuilderAlias object */
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
     * @return BuilderAlias object
     */
    protected function qApiLimitOffset($backendLimit = NULL, $backendOffset = NULL)
    {
        /** @var $q BuilderAlias object */
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

    /**
     * Add Audit Info if possible.
     * @return BuilderAlias object
     */
    protected function qApiJoinAuditInfo()
    {
        /** @var $q BuilderAlias object */
        $q          = $this->q;
        $thisFields = $this->fields();
        $alias      = $this->alias;

        // Join Creator if possible
        if (array_key_exists('created_by', $thisFields)) {
            $q->addSelect([
                'pc.first_name as created_first_name',
                'pc.last_name as created_last_name',
            ]);
            $q->leftJoin('person as pc', 'pc.person_id', '=', "{$alias}.created_by");
        }

        if (array_key_exists('modified_by', $thisFields)) {
            $q->addSelect([
                'pm.first_name as modified_first_name',
                'pm.last_name as modified_last_name',
            ]);
            $q->leftJoin('person as pm', 'pm.person_id', '=', "{$alias}.modified_by");
        }

        return $q;
    }

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
        $this->collection = $q->get()->keyBy($this->pkName);

        //Has Many JOINs.
        $this->joinHasMany();

        return $this->collection;
    }

    //ToDo: see also $this->getOne VERY similar logic
    public function getOneWithReferences($conditions = [])
    {
        $attributes = $this->getAllWithReferences($conditions, [], 1)->first();
        if (empty($attributes)) {
            $attributes = (object)[];
        }
        if (isset($attributes->{$this->pkName})) {
            $this->setPkValue($attributes->{$this->pkName});
        }
        $this->attributes = Data::mergeObjects($this->attributes, $attributes);

        return $this->attributes;
    }

    /**
     * Sets $this->o_GET
     */
    protected function apiUnpackGetParams()
    {
        $this->o_GET  = new \stdClass();
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
                $this->o_GET->{$full} = $_GET[$short];
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

        return $this->o_GET;
    }
    #endregion API, LIMIT, ORDER
    ##################################################
    #region FILTER, VALIDATE
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

                #####
                //ToDO: Really necessary?
                if ($this->mode === 'INSERT' && empty($data->{$name}) && isset($params['default'])) {
                    $data->{$name} = $params['default'];
                    continue;
                }
                #####

                $value = $data->{$name};
                ##################################################
                if (Str::ifContains($name, 'date_int_')) {
                    try {
                        $data->{$name} = (new \alina\utils\DateTime($data->{$name}))->getTimestamp();
                    } catch (Exception $e) {
                    }
                    //ToDO: make beautiful
                    //...do nothing
                }
                ##################################################
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
                            #####
                            // throw new exceptionValidation("
                            // {$msg}
                            // Field:   {$name} ;
                            // Entered:  {$value} ;
                            // Res:    {$vResult} ;
                            // ErrorIf:{$errorIfstr} ;
                            // ");
                            #####
                            throw new exceptionValidation(
                                "{$msg} (field:$name)"
                            );
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
            $fields = strtoupper(implode(', ', $this->matchedUniqueFields));
            $table  = strtoupper($this->table);
            throw new exceptionValidation("{$table} with such {$fields} already exists");
        }

        return $this;
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
    protected function restrictIdentityAutoincrementReadOnlyFields($data)
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

    #endregion FILTER, VALIDATE
    ##################################################
    #region Helpers

    protected function calcPagesTotal($rowsTotal, $pageSize)
    {
        if ($pageSize <= 0) {
            $pageSize = $rowsTotal;
        }
        $this->pagesTotal = ceil($rowsTotal / $pageSize);

        return $this->pagesTotal;
    }

    protected function calcOffset($pageCurrentNumber, $pageSize)
    {
        if (!isset($pageSize) || !isset($pageCurrentNumber) || $pageSize <= 0 || $pageCurrentNumber <= 0) {
            return FALSE;
        }

        $offset = ($pageSize * ($pageCurrentNumber - 1));

        return $offset;
    }

    protected function calcSortNameSortAscData($sortName, $sortAsc)
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
            $asc         = isset($sa[$i]) ? Data::getSqlDirection($sa[$i]) : 'ASC';
            $sortArray[] = [$n, $asc];
        }

        return $sortArray;
    }

    protected function resetFlags()
    {
        $this->mode                = 'SELECT';
        $this->isDataFiltered      = FALSE;
        $this->isDataValidated     = FALSE;
        $this->matchedUniqueFields = [];
        $this->matchedConditions   = [];
    }

    /**
     * Creates $defaultRawObj with default values for DB.
     * @return \stdClass object $defaultRawObj.
     */
    protected function buildDefaultData()
    {
        $fields        = $this->fields();
        $defaultRawObj = new \stdClass();
        foreach ($fields as $f => $props) {
            if (array_key_exists('default', $props)) {
                $defaultRawObj->$f = $props['default'];
            } else {
                $defaultRawObj->$f = NULL;
            }
        }
        $this->attributes = $defaultRawObj;

        return $defaultRawObj;
    }

    protected function prepareDbData($data, $addAuditInfo = TRUE)
    {
        $data = Data::toObject($data);
        $this->applyFilters($data);
        $this->validate($data);

        if ($addAuditInfo) {
            $this->addAuditInfo($data, $this->mode);
        }

        $dataArray = $this->restrictIdentityAutoincrementReadOnlyFields($data);

        return $dataArray;
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
    protected function addAuditInfo(\stdClass $data, $saveMode = NULL)
    {

        if ($saveMode === NULL) {
            $saveMode = $this->mode;
        }

        $user_id = AlinaCurrentUserId();
        $now     = AlinaGetNowInDbFormat();

        $data->modified_by   = $user_id;
        $data->modified_date = $now;

        if ($saveMode === 'INSERT') {
            $data->created_by   = $user_id;
            $data->created_date = $now;
        }

        return NULL;
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
     * Returns array of arrays.
     * Nested arrays contain list of field names, which are Unique Keys together.
     * Example:
     * [
     *  ['email'],
     *  ['row', 'column'],
     * ]
     */
    protected function uniqueKeys()
    {
        return [];
    }

    protected function fieldsIdentity()
    {
        return [
            $this->pkName,
        ];
    }

    /**
     * @param string $alias
     * @return BuilderAlias
     */
    public function q($alias = NULL)
    {
        /**
         * ATTENTION: Important security fix in order to avoid accident start of a query,
         * while a previous is in progress.
         */
        if (isset($this->q) && !empty($this->q)) {
            $this->q = NULL;
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
     * Initial list of fields. @see fieldStructureExample
     */
    public function fields()
    {
        $fields = [];
        $items  = [];
        $items  = Dal::table('information_schema.columns')
            ->select('column_name')
            ->where('table_name', '=', $this->table)
            ->where('table_schema', '=', AlinaCFG('db/database'))
            ->pluck('column_name');
        foreach ($items as $v) {
            $fields[$v] = [];
        }

        return $fields;
        ##################################################
        // Previous approach
        // $table  = $this->table;
        // $m      = new static(['table' => $table]);
        // $q      = $m->q();
        // $item   = $q->first();
        // foreach ($item as $i => $v) {
        //     $fields[$i] = [];
        // }
        // return $fields;
        ##################################################
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

    public function raw($expression)
    {
        return Dal::raw($expression);
    }

    protected function vocGetSearch()
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

    protected function vocGetSearchSpecial()
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

    protected function qApplyGetSearchParams()
    {
        //ToDo: Check $q, $this->o_GET emptiness.
        $q = $this->q;
        foreach ($this->o_GET as $f => $v) {

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

    public function g($f)
    {
        if (isset($this->attributes->{$f})) {
            return $this->attributes->{$f};
        }

        return FALSE;
    }

    protected function setPkValue($id, \stdClass $data = NULL)
    {
        $this->{$this->pkName} = $id;
        $this->id              = $id;
        if ($data) {
            $data->{$this->pkName} = $id;
        }

        return $this;
    }

    #endregion Helpers
    ##################################################
    #region relations

    protected function qJoinHasOne()
    {
        (new referenceProcessor($this))->joinHasOne();
    }

    protected function joinHasMany()
    {
        $forIds        = $this->collection->pluck($this->pkName);
        $qHasManyArray = (new referenceProcessor($this))->joinHasMany([], $forIds);
        foreach ($qHasManyArray as $rName => $q) {
            //ToDO: Hardcoded id
            $qResult = $q->get()->keyBy('child_id');
            foreach ($this->collection as $thisModelAttributes) {
                $thisModelAttributes->{$rName} = [];
                foreach ($qResult as $keyBy => $row) {
                    if ($thisModelAttributes->{$this->pkName} === $row->main_id) {
                        $thisModelAttributes->{$rName}[$keyBy] = $row;
                    }
                }
            }
        }
    }

    ##################################################
    protected function referencesSources()
    {
        return [];
    }

    public function getReferencesSources()
    {
        $sources = [];
        if (method_exists($this, 'referencesSources')) {
            $referencesSources = $this->referencesSources();
            foreach ($referencesSources as $rName => $sourceConfig) {
                if (isset($sourceConfig['model'])) {
                    $model                   = $sourceConfig['model'];
                    $keyBy                   = $sourceConfig['keyBy'];
                    $human_name              = $sourceConfig['human_name'];
                    $m                       = modelNamesResolver::getModelObject($model);
                    $dataSource              = $m
                        ->q()
                        ->addSelect($keyBy)
                        ->addSelect($human_name)
                        ->orderBy($keyBy, 'ASC')
                        ->get()
                        ->keyBy($keyBy)
                        ->toArray();
                    $sources[$rName]['list'] = $dataSource;
                    $sources[$rName]         = array_merge($sources[$rName], $sourceConfig);
                }
            }
        }

        return $sources;
    }
    ##################################################

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
                    'type'       => ['string', 'number', 'etc'],
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

    protected function referencesTo() { return []; }
    #endregion relations
    ##################################################
}
