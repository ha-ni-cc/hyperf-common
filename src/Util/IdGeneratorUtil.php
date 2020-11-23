<?php


namespace App\Util;


class IdGeneratorUtil
{
    static function OrderSn($prefix = '')
    {
        return $prefix . date('ymdHis') . mt_rand(60000, 99999);
    }
}