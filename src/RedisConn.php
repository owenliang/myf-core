<?php
namespace myf;

use myf\exception\MysqlException;

/**
 * Class RedisConn
 * @package myf
 *
 * Redis连接对象封装
 */
class RedisConn
{
    const REDIS_DEFAULT_DBINDEX = 0; // 默认库下标
    const REDIS_DEFAULT_TIMEOUT = 2; // 默认超时
    const REDIS_DEFAULT_READ_TIMEOUT = 2; // 默认读超时

    private $dbConfig = []; // redis数据库配置
    private $hostConfig = [];   // 节点地址
    private $conn = null;   // redis/redisCluster对象

    // 抛出异常
    private function throwException()
    {
        throw new MysqlException('Redis Error');
    }

    public function __construct($dbConfig, $hostConfig)
    {
        $this->dbConfig = $dbConfig;
        $this->hostConfig = $hostConfig;
    }

    // 懒惰建立物理连接
    private function getConn()
    {
        if (empty($this->conn)) {
            $dbIndex = !empty($this->dbConfig['dbIndex']) ? $this->dbConfig['dbIndex'] : self::REDIS_DEFAULT_DBINDEX;
            $timeout = !empty($this->dbConfig['timeout']) ? $this->dbConfig['timeout'] : self::REDIS_DEFAULT_TIMEOUT;
            $readTimeout = !empty($this->dbConfig['readTimeout']) ? $this->dbConfig['readTimeout'] : self::REDIS_DEFAULT_READ_TIMEOUT;

            if (empty($this->dbConfig['isCluster'])) { // 单点redis
                $this->conn = new \Redis();
                if (!$this->conn->connect($this->hostConfig['host'], $this->hostConfig['port'], $timeout, NULL, 100, $readTimeout)) {
                    $this->throwException();
                }
            } else { // Cluster传Host列表
                $seeds = array_map(function ($node) { return $node['host'] . ':' . $node['port']; }, $this->dbConfig['master']);
                $this->conn = new \RedisCluster(NULL, $seeds, $timeout, $readTimeout);
                // 设置读操作随机走主库/从库
                // $this->conn->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE_SLAVES);
            }

            // 认证
            if (!empty($this->dbConfig['password']) && !$this->conn->auth($this->dbConfig['password'])) {
                $this->throwException();
            }
            // 换库
            if ($dbIndex != 0 && !$this->conn->select($dbIndex)) {
                $this->throwException();
            }
        }
        return $this->conn;
    }

    // 调用转发
    public function __call($name, $arguments)
    {
        $conn = $this->getConn();
        return call_user_func_array([$conn, $name], $arguments);
    }
}