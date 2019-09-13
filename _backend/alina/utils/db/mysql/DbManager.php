<?php

namespace alina\utils\db\mysql;

use Exception;
use PDO;

/**
 * @property PDO pdo
 */
class DbManager
{
    #region Connector
    protected $pdo;
    protected $arrTransaction       = [];
    protected $isInTransaction      = FALSE;
    protected $flagForceTransaction = FALSE;
    #region CREDENTIALS
    protected $host = '';
    protected $port = '3306';
    protected $db   = 'stage001';
    protected $user = 'sixtyandme';
    protected $pass = 'NgLh590g1';

    #endregion CREDENTIALS

    public function connect($forceNew = FALSE)
    {
        if ($this->isInTransaction) {
            return $this;
        }
        if ($forceNew
            || !isset($this->pdo)
            || empty($this->pdo)
            || !($this->pdo instanceof PDO)
        ) {
            $this->pdo = new PDO("mysql:dbname={$this->db};host={$this->host};port={$this->port}", $this->user, $this->pass, [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $this;
    }
    #endregion Connector

    #region Transaction
    protected function TransactionStart()
    {
        $this->connect();
        if ($this->isInTransaction) {
            return $this;
        }
        $this->pdo->beginTransaction();
        $this->arrTransaction[] = 1;
        $this->isInTransaction  = TRUE;

        return $this;
    }

    protected function TransactionCommit()
    {
        try {
            array_pop($this->arrTransaction);
            if (count($this->arrTransaction) > 0) {
                return $this;
            }

            $this->pdo->commit();
            $this->isInTransaction = FALSE;
        } catch (Exception $e) {
            $this->TransactionRollBack($e);
        }

        return $this;
    }

    protected function TransactionRollBack($e)
    {
        $this->pdo->rollBack();
        throw $e;
    }

    #endregion Transaction

    #region Queries
    #region Generic Execution
    protected function qExecGetStatement($sql, $arrParams = NULL, $arrTypes = NULL)
    {
        $this->connect();
        $pdo  = $this->pdo;
        $stmt = $pdo->prepare($sql);
        if (isset($arrParams)) {
            foreach ($arrParams as $key => $value) {
                if ($arrTypes && is_array($arrTypes) && array_key_exists($key, $arrTypes)) {
                    $stmt->bindValue($key, $value, $arrTypes[$key]);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
        }
        $stmt->execute();

        return $stmt;
    }

    public function qExecGetAffectedRows($sql, $arrParams = NULL, $arrTypes = NULL)
    {
        if ($this->flagForceTransaction) {
            $this->TransactionStart();
        }
        $stmt = $this->qExecGetStatement($sql, $arrParams, $arrTypes);
        $rows = $stmt->rowCount();
        if ($this->flagForceTransaction) {
            $this->TransactionCommit();
        }

        return $rows;
    }

    public function qExecFetchAll($sql, $arrParams = NULL, $arrTypes = NULL)
    {
        if ($this->flagForceTransaction) {
            $this->TransactionStart();
        }
        $stmt = $this->qExecGetStatement($sql, $arrParams, $arrTypes);
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        if ($this->flagForceTransaction) {
            $this->TransactionCommit();
        }

        return $data;
    }

    public function qExecFetchColumn($column, $sql, $arrParams = NULL, $arrTypes = NULL)
    {
        if ($this->flagForceTransaction) {
            $this->TransactionStart();
        }
        $stmt = $this->qExecGetStatement($sql, $arrParams, $arrTypes);
        $data = $stmt->fetchColumn($column);
        if ($this->flagForceTransaction) {
            $this->TransactionCommit();
        }

        return $data;
    }

    public function qExecPluck($sql, $arrParams = NULL, $arrTypes = NULL)
    {
        if ($this->flagForceTransaction) {
            $this->TransactionStart();
        }
        $stmt = $this->qExecGetStatement($sql, $arrParams, $arrTypes);
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($this->flagForceTransaction) {
            $this->TransactionCommit();
        }

        return $data;
    }
    #endregion Generic Execution
    #region Special Queries
    public function qsGetTableFields($table)
    {
        $o      = (object)[
            'tableName' => $table,
            'db'        => $this->db,
        ];
        $sqlTpl = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/AllTableFields.sql';
        $sql    = template($sqlTpl, $o);
        $d      = $this->qExecPluck($sql);

        return $d;
    }
    #endregion Special Queries
    #endregion Queries

    #reion Utils
    public function change($p, $v)
    {
        $this->{$p} = $v;

        return $this;
    }
    #endreion Utils
}
