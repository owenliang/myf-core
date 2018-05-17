<?php
namespace myf;

/**
 * Class View
 * @package myf
 *
 * 渲染模板文件
 */
class View
{
    private static $filename = '';
    private static $params = [];

    // 加载模板文件
    private static function loadTpl()
    {
        extract(self::$params);
        ob_start();
        require self::$filename;
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    // 渲染模板
    public static function render($tpl, $params = [])
    {
        self::$filename = MYF_ROOT . $tpl . '.php';
        self::$params = $params;
        return self::loadTpl();
    }

    // HTML实体转义方法
    public static function encode($text)
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML401);
    }
}