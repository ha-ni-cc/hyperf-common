<?php


namespace App\Util;


use Hyperf\HttpServer\Request;

class IPUtil
{

    public static function getClientIP()
    {
        /** @var Request $request */
        $request = di(Request::class);
        if (!empty($request->getHeaderLine('x-real-ip'))) {
            $ip = $request->getHeaderLine('x-real-ip');
        } elseif (!empty($request->getHeaderLine('x-forwarded-for'))) {
            $ips = explode(',', $request->getHeaderLine('x-forwarded-for'));
            $ip = $ips[0] ?? 'unknown';
        } elseif (!empty($request->server('remote_addr'))) {
            $ip = $request->server('remote_addr');
        } else $ip = 'unknown';
        return $ip;
    }

    public static function getLocation($ip, $lang = 'CN')
    {
        $db = __DIR__ . '/../Kernel/IPDb/ipipfree.ipdb';
        $city = new \ipip\db\City($db);
        return $city->find($ip, $lang);
    }

    public static function getLocationAsStr($ip, $lang = 'CN')
    {
        $location = self::getLocation($ip, $lang);
        if (!is_array($location)) return '未知地址';
        $str = '';
        foreach (array_unique($location) as $v) {
            $str .= $v;
        }
        return $str;
    }
}