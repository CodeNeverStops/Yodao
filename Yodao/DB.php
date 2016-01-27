<?php
/**
 * @author Wayne You<lokiwuxi@gmail.com>
 */
namespace Yodao;

class DB
{

    const DRIVER_MYSQL = 'mysql';
    const DRIVER_PGSQL = 'pgsql';

    const TYPE_SELECT = 'SELECT ';
    const TYPE_INSERT = 'INSERT ';
    const TYPE_UPDATE = 'UPDATE ';
    const TYPE_DELETE = 'DELETE ';
    const TYPE_UNKNOWN = 'UNKNOWN';

    protected $_table = null;

    private $_dbh;
    private $_dsn;
    private $_username;
    private $_password;
    private $_driverType;
    private $_driverOptions;
    private $_stmt;
    private $_builder;

    public function __construct($dsn, $username = '', $password = '', array $driverOptions = [])
    {
        $this->_dsn = $dsn;
        $this->_username = $username;
        $this->_password = $password;
        $this->_driverType = $this->_parseDriverType($dsn);
        $this->_driverOptions = $driverOptions;
        $this->_builder = $this->_getBuilder($this->_driverType);
    }

    private function _initPdo()
    {
        try {
            if (!$this->_dbh) {
                $this->_dbh = new \PDO(
                    $this->_dsn, $this->_username,
                    $this->_password, $this->_driverOptions
                );
            }
        } catch (DBException $e) {
            throw new DBException($e->getMessage());
        }
    }

    public function prepare($sql, array $driverOptions = [])
    {
        $this->_initPdo();
        $stmt = $this->_dbh->prepare($sql, $driverOptions);
        return $stmt;
    }

    private function _checkTable()
    {
        if (empty($this->_table)) {
            throw new DBException('Please specify a table');
        }
    }

    public function table($table)
    {
        $this->_table = $table;
        return $this;
    }

    public function __call($method, $args)
    {
        $this->_initPdo();
        if (!method_exists($this->_dbh, $method)) {
            throw new DBException("method '$method' not found");
        }
        $result = call_user_func_array(array($this->_dbh, $method), $args);
        return $result;
    }

    private function _parseDriverType($dsn)
    {
        $dsn = strtolower(trim($dsn));
        if (0 === strpos($dsn, 'mysql:')) {
            return self::DRIVER_MYSQL;
        }
        return null;
    }

    private function _getBuilder($type)
    {
        switch ($type) {
        case self::DRIVER_MYSQL:
            $builder = new Builders\MysqlBuilder();
            break;
        case self::DRIVER_PGSQL:
            break;
        }
        return $builder;
    }

    public function count($where = '', array $whereData = [])
    {
        $ret = $this->_execute('count', func_get_args());
        return $ret[0]['total'];
    }

    public function select($fields, $where = '', array $whereData = [], $orderBy = null, $limit = null, $offset = null)
    {
        $ret = $this->_execute('select', func_get_args());
        return $ret;
    }

    public function selectOne($fields, $where = '', array $whereData = [], $orderBy = null)
    {
        $ret = $this->_execute('selectOne', func_get_args());
        return $ret[0];
    }

    public function insert(array $fieldsMap)
    {
        $ret = $this->_execute('insert', func_get_args());
        return $ret ? $this->_dbh->lastInsertId() : false;
    }

    public function insertOrUpdate(array $insertFields, array $updateFields)
    {
        $ret = $this->_execute('insertOrUpdate', func_get_args());
        return $ret ? $this->rowCount() : false;
    }

    public function update(array $fieldsMap, $where, array $whereData)
    {
        $ret = $this->_execute('update', func_get_args());
        return $ret;
    }

    public function delete($where, array $whereData)
    {
        $ret = $this->_execute('delete', func_get_args());
        return $ret;
    }

    public function insertFromSelect(array $fieldsMap, $where, array $whereData, $fromTable = null, $limit = null, $offset = null)
    {
        $ret = $this->_execute('insertFromSelect', func_get_args());
        return $ret;
    }

    public function insertMulti(array $rows, array $fixFields = [])
    {
        $ret = $this->_execute('insertMulti', func_get_args());
        return $ret;
    }

    public function rowCount()
    {
        return $this->_stmt->rowCount();
    }

    protected function _execute($method, $args)
    {
        $build = $this->_builder->table($this->_table);
        $buildRet = call_user_func_array([$build, $method], $args);
        if (false === $buildRet) {
            return false;
        }
        $ret = $this->_executeSql($buildRet['sql'], $buildRet['bindingMap'], $buildRet['whereMap']);
        return $ret;
    }

    private function _executeSql($sql, array $fieldsMap = [], array $whereData = [])
    {
        $this->_checkTable();
        $type = $this->_checkType($sql);
        $this->_stmt = $stmt = $this->prepare($sql);
        if ($fieldsMap) {
            foreach ($fieldsMap as $field => $v) {
                $stmt->bindValue(":$field", $v);
            }
        }
        if ($whereData) {
            foreach ($whereData as $k=>$v) {
                $stmt->bindValue(":$k", $v);
            }
        }
        if (false === $stmt->execute()) {
            throw new DBException("execute sql failed. sql: $sql");
        }
        if (0 === strpos($sql, self::TYPE_SELECT)) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        return true;
    }

    protected function _checkType($sql)
    {
        $type = substr($sql, 0, 7);
        if (in_array(
            $type, 
            [
                self::TYPE_SELECT,
                self::TYPE_INSERT,
                self::TYPE_UPDATE,
                self::TYPE_DELETE
            ]
        )) {
            return $type;
        }
        return self::TYPE_UNKNOWN;
    }
}
