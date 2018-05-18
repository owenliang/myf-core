<?php
namespace myf;
use myf\exception\MysqlException;

/**
 * Class Mysql
 * @package myf
 *
 * Mysql主从分离客户端
 */
class Mysql
{
    private $config = null;
    private $masterConn = null;
    private $slaveConn = null;

    private static $clients = [];

    // 获取对应的客户端单例
    public static function instance($name)
    {
        if (!isset(self::$clients[$name])) {
            self::$clients[$name] = new self(App::$config['mysql'][$name]);
        }
        return self::$clients[$name];
    }

    // 真实请求时才建立连接
    public function __construct($config)
    {
        $this->config = $config;
    }

    // 建立一个连接
    private function initConn($hostConf)
    {
        return new \PDO(
            "mysql:dbname={$this->config['dbname']};host={$hostConf['host']};port={$hostConf['port']};charset={$this->config['charset']}",
            $this->config['username'],
            $this->config['password']
        );
    }

    // 检查是否处于事务中
    private function inTransaction()
    {
        if (isset($this->masterConn) && $this->masterConn->inTransaction()) {
            return true;
        }
        return false;
    }

    // 获取一个mysql连接
    private function getConn($isMaster)
    {
        if (!$isMaster && !$this->inTransaction()) {
            if (empty($this->slaveConn) && !empty($this->config['slave'])) {
                $slaveConfig = $this->config['slave'][array_rand($this->config['slave'], 1)];
                $this->slaveConn = $this->initConn($slaveConfig);
            }
            if (!empty($this->slaveConn)) {
                return $this->slaveConn;
            }
        }
        // 未配置从库则连主库
        if (empty($this->masterConn) && !empty($this->config['master'])) {
            $masterConfig = $this->config['master'][array_rand($this->config['master'], 1)];
            $this->masterConn = $this->initConn($masterConfig);
        }
        return $this->masterConn;
    }

    // 抛出异常
    private function throwException()
    {
        throw new MysqlException('Mysql Error');
    }

    // 启动事务
    public function begin()
    {
        $conn = $this->getConn(true);
        $result = $conn->beginTransaction();
        if (!$result) {
            $this->throwException();
        }
    }

    // 提交事务
    public function commit()
    {
        $conn = $this->getConn(true);
        $result = $conn->commit();
        if (!$result) {
            $this->throwException();
        }
    }

    // 回滚事务
    public function rollback()
    {
        $conn = $this->getConn(true);
        $result = $conn->rollBack();
        if (!$result) {
            $this->throwException();
        }
    }

    // 预处理sql
    private function prepare($conn, $sql, $params)
    {
        $stmt = $conn->prepare($sql);
        foreach ($params as $name => $value) {
            $dataType =  \PDO::PARAM_STR;
            if (is_bool($value)) {
                $dataType = \PDO::PARAM_BOOL;
            } else if (is_null($value)) {
                $dataType = \PDO::PARAM_NULL;
            } else if (is_int($value)) {
                $dataType = \PDO::PARAM_INT;
            }
            $stmt->bindValue($name, $value, $dataType);
        }
        $result = $stmt->execute();
        if (!$result) {
            $this->throwException();
        }
        return $stmt;
    }

    // 更新/删除操作
    public function exec($sql, $params = [], $options = [])
    {
        $conn = $this->getConn(true);

        $stmt = $this->prepare($conn, $sql, $params);

        return $stmt->rowCount();
    }

    // 查询操作
    // options['forceMaster']: 强制读主库
    // options['getRow']: 只返回一行
    public function query($sql, $params = [], $options = [])
    {
        $conn = $this->getConn(!empty($options['forceMaster']) ? true : false);

        $stmt = $this->prepare($conn, $sql, $params);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if ($rows === false) {
            $this->throwException();
        }
        if (!empty($options['getRow'])) {
            $rows = isset($rows[0]) ? $rows[0] : null;
        }
        return $rows;
    }
}