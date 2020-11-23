<?php


namespace App\Util;


class ParamsMD5Util
{
    public static function sign(array $arr, string $key, &$sign)
    {
        if (isset($arr['sign'])) unset($arr['sign']);
        ksort($arr);
        $params = '';
        foreach ($arr as $k => $v) {
            $params .= $k . '=' . $v . '&';
        }
        $params = rtrim($params, '&');
        $sign = md5($params . $key);
        return $params . '直接拼接上密钥';
    }
}