<?php
namespace myf;

/**
 * Class App
 * @package myf
 *
 * 加载配置, 完成路由, 唤起controller
 */
class App
{
    public static $config = null;

    // 启动框架
    public static function run($config)
    {
        self::$config = $config;

        self::setDebug();

        spl_autoload_register([get_class(), 'autoload']);

        self::route();
    }

    // 应用类自动加载
    public static function autoload($class)
    {
        $classPath = MYF_ROOT . '/' . str_replace('\\', '/', $class) . '.php';
        require $classPath;
    }

    // 初始化PHP报错配置
    private static function setDebug()
    {
        error_reporting(E_ALL);
        ini_set('log_errors', false);
        if (!empty(self::$config['debug'])) {
            ini_set('display_errors', true);
        } else {
            ini_set('display_errors', false);
        }
    }

    // 路由
    private static function route()
    {
        $isCli = php_sapi_name() == 'cli';

        if ($isCli) {
            global $argv;
            $uri = isset($argv[1]) ? $argv[1] : '';
        } else {
            $uri = strtolower($_SERVER['REQUEST_URI']);
        }

        $r = [];  // controller, action
        $p = []; // param1, param2, ....

        if (array_key_exists($uri, self::$config['route']['static'])) {
            $r = self::$config['route']['static'][$uri];
        } else if (!$isCli) {
            foreach (self::$config['route']['regex'] as $rule) {
                $pattern = '#' . $rule[0] . '#i';
                $params = [];
                if (preg_match($pattern, $uri, $params)) {
                    $r = array_slice($rule, 1, 2);
                    $p = $params;
                    break;
                }
            }
        }

        if (!empty($r)) {
            $controller = '\\' . basename(realpath(APP_ROOT)) . '\\controller\\' . $r[0];
            $action = $r[1];
            if ($isCli) {
                $arguments = array_slice($argv, 2);
            } else {
                $arguments = $p;
            }

            $controller = new $controller();
            call_user_func_array([$controller, $action], $arguments);
        }
    }
}