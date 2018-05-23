<?php
namespace myf;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

/**
 * Class Elasticsearch
 * @package myf
 *
 * Elasticsearch客户端
 *
 * 配置参数见：Elasticsearch\Connections::performRequest()与GuzzleHttp\Ring\Client::__invoke
 *
 */
class Elasticsearch
{
    private $config = null;
    private $conn = null;

    private static $clients = [];

    // 获取对应的客户端单例
    public static function instance($name)
    {
        if (!isset(self::$clients[$name])) {
            self::$clients[$name] = new self(App::$config['elasticsearch'][$name]);
        }
        return self::$clients[$name];
    }

    // 真实请求时才建立连接
    public function __construct($config)
    {
        $this->config = $config;
    }

    // 获取连接
    private function getConn()
    {
        if (empty($this->conn)) {
            $this->conn = ClientBuilder::fromConfig($this->config);
        }
        return $this->conn;
    }

    // 请求转发
    public function __call($name, $arguments)
    {
        $conn = $this->getConn();
        return call_user_func_array([$conn, $name], $arguments);
    }
}