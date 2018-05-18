<?php
namespace myf;

/**
 * Class Redis
 * @package myf
 *
 * Redis(集群)客户端
 *
 * redis因为命令相对复杂, 不适合实现读写分离, 需使用方明确获取master/slave连接
 */
class Redis
{
    private static $masters = [];
    private static $slaves = [];

    // 获取连接
    private static function getConn($name, $isMaster)
    {
        // 如果是集群, 修正isMaster=true
        $config = App::$config['redis'][$name];
        if (!empty($config['isCluster'])) {
            $isMaster = true;
        }

        // isMaster=false, 则优先返回slave
        if (!$isMaster) {
            if (empty(self::$slaves[$name]) && !empty($config['slave'])) {
                $slaveConfig = $config['slave'][array_rand($config['slave'], 1)];
                self::$slaves[$name] = new RedisConn($config, $slaveConfig);
            }
            if (!empty(self::$slaves[$name])) {
                return self::$slaves[$name];
            }
        }

        // isMaster=true或者没有slave, 则返回master
        if (empty(self::$masters[$name]) && !empty($config['master'])) {
            $masterConfig = $config['master'][array_rand($config['master'], 1)];
            self::$masters[$name] = new RedisConn($config, $masterConfig);
        }
        return isset(self::$masters[$name]) ? self::$masters[$name] : null;
    }

    // 获取主库连接
    public static function master($name)
    {
        return self::getConn($name,true);
    }

    // 获取从库连接
    public static function slave($name)
    {
        return self::getConn($name,false);
    }

    // 获取集群连接
    public static function cluster($name)
    {
        return self::getConn($name,true);
    }
}