<?php
include 'YodaoSqlBuilder.php';
/**
 * Yodao
 *
 * @author Wayne You<lokiwuxi@gmail.com>
 */
interface YodaoLogWriter 
{
    public function log($msg, $level = '');
}

class YodaoLogWriterDefault implements YodaoLogWriter
{
    public function log($msg, $level = '')
    {
        echo "[$level]",$msg;
    }
}

class YodaoException extends Exception
{
}

class Yodao
{
    use YodaoSqlBuilder;

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
    private $_logWriter;

    public function __construct($dsn, $username = '', $password = '', array $driverOptions = [], YodaoLogWriter $logWriter = null)
    {
        $this->_dsn = $dsn;
        $this->_username = $username;
        $this->_password = $password;
        $this->_driverType = $this->_parseDriverType($dsn);
        $this->_driverOptions = $driverOptions;
        $this->_logWriter = $logWriter ?: new YodaoLogWriterDefault();
    }

    private function _initPdo()
    {
        try {
            if (!$this->_dbh || !$this->_checkConnectionAvailable()) {
                $this->_dbh = new PDO(
                    $this->_dsn, $this->_username,
                    $this->_password, $this->_driverOptions
                );
            }
        } catch (Exception $e) {
            $this->_logWriter->log($e->getMessage());
            $this->_checkDNS($this->_dsn);
        }
    }

    private function _checkConnectionAvailable()
    {
        $status = $this->_dbh->getAttribute(PDO::ATTR_SERVER_INFO);
        switch ($this->_driverType) {
            case self::DRIVER_MYSQL:
                if ($status == 'MySQL server has gone away') {
                    return false;
                }
                break;
            default:
                break;
        }
        return true;
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
            throw new YodaoException('Please specify a table');
        }
    }

    public function setTable($table)
    {
        $this->_table = $table;
    }

    public function __call($method, $args)
    {
        $this->_initPdo();
        if (!method_exists($this->_dbh, $method)) {
            throw new YodaoException("method '$method' not found");
        }
        $result = call_user_func_array(array($this->_dbh, $method), $args);
        return $result;
    }

    private function _checkDNS($dsn)
    {
        $host = $dsn;
        $host = substr($host, strpos($host, 'host='));
        $host = substr($host, strlen('host='));
        $host = substr($host, 0, strpos($host, ';'));
        $t = microtime(true);
        $ip = gethostbyname($host);
        $d = round((microtime(true) - $t)*1000);
        $this->_logWriter->log(sprintf("[%s]name resolve(%s->%s):%fms", $dsn, $host, $ip, $d));
    } 

    private function _parseDriverType($dsn)
    {
        $dsn = strtolower(trim($dsn));
        if (0 === strpos($dsn, 'mysql:')) {
            return self::DRIVER_MYSQL;
        }
        return null;
    }

}
