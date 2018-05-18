<?php
namespace myf;

/**
 * Class Http
 * @package myf
 *
 * HTTP客户端
 */
class Http
{
    const CURL_DEFAULT_TIMEOUT = 1;
    const CURL_DEFAULT_CONNECT_TIMEOUT = 1;

    public static function post($uri, $getArgs = [], $postArgs = [], $headers = [])
    {
        return self::request('POST', $uri, $getArgs, $postArgs, $headers);
    }

    public static function get($uri, $getArgs = [], $postArgs = [], $headers = [])
    {
        return self::request('GET', $uri, $getArgs, $postArgs, $headers);
    }

    private static function ucHeaders($headers)
    {
        $ucHeaders = [];
        foreach ($headers as $key => $value) {
            $ucHeaders[ucwords($key, '-')] = $value;
        }
        return $ucHeaders;
    }

    private static function buildHeaders($headers, $innerHeaders)
    {
        $headers = self::ucHeaders($headers);
        $innerHeaders = self::ucHeaders($innerHeaders);
        $allHeaders = array_merge($innerHeaders, $headers); // 用户header > 内部header
        $ret = [];
        array_walk($allHeaders, function ($value, $key) use (&$ret) { $ret[] =  "$key: $value"; } );
        return $ret;
    }

    private static function handlePostArgs($curl, $postArgs, &$innerHeaders)
    {
        // RAW POST
        if (!is_array($postArgs)) {
            $body = $postArgs;
        } else {
            // 文件表单检查
            $isMultiForm = false;
            array_walk($postArgs, function ($value, $key) use (&$isMultiForm) {
                $isMultiForm = $isMultiForm || ($value instanceof \CURLFile);
            });

            if (!$isMultiForm) { // x-www表单
                $body = http_build_query($postArgs);
            } else { // form-data文件表单
                $body = $postArgs;
            }
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        if (!is_array($postArgs)) {
            $innerHeaders['Content-Type'] = "Content-Type: application/octet-stream";
        }
    }

    private static function request($method, $uri, $getArgs = [], $postArgs = [], $headers = [])
    {
        $timeout = !empty(App::$config['http']['timeout']) ? intval(App::$config['http']['timeout']) : self::CURL_DEFAULT_TIMEOUT;
        $connectTimeout = !empty(App::$config['http']['connectTimeout']) ? App::$config['http']['connectTimeout'] : self::CURL_DEFAULT_CONNECT_TIMEOUT;

        $curl = curl_init();

        $url = $uri;
        if (!empty($getArgs)) {
            $url .= '?' . http_build_query($getArgs);
        }
        curl_setopt($curl, CURLOPT_URL, $url);  // URI
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);   // 返回应答体
        curl_setopt($curl, CURLOPT_HEADER, false);  // 不返回应答头
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false); // 不跟随Location
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 不做CA校验
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 不做证书域与请求DOMAIN一致性
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 请求超时
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connectTimeout); // 连接超时
        curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE); // 追溯记录发送出去的header
        curl_setopt($curl, CURLOPT_USERAGENT, 'myf'); // UA

        $innerHeaders = []; // 框架生成的header, 优先级低于传参

        if (!empty($postArgs)) {    // HTTP BODY部分
            self::handlePostArgs($curl, $postArgs, $innerHeaders);
        }

        // HTTP METHOD
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        // HTTP HEADER
        $headers = self::buildHeaders($headers, $innerHeaders);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $errno = curl_errno($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        return [
            'errno' => $errno,
            'response' => $response,
            'info' => $info,
        ];
    }
}