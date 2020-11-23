<?php


namespace App\Util;

use App\Constants\ErrorCode;
use App\Exception\BusinessException;

class VerifyCodeUtil
{
    const prefix = '_verifyCode:';

    public static function random($length = 6)
    {
        return $length == 6 ? mt_rand(100000, 999999) : mt_rand(1000, 9999);
    }

    /**
     * 获取验证码
     * @param $type
     * @param $action
     * @param $key
     * @return bool|string
     */
    static function get($type, $action, $key)
    {
        return redis()->get(self::prefix . "$type:$action:$key");
    }

    /**
     * 设置验证码
     * @param $type
     * @param $action
     * @param $key
     * @param $code
     * @param int $ttl
     * @return bool
     */
    static function set($type, $action, $key, $code, $ttl = 300)
    {
        return redis()->set(self::prefix . "$type:$action:$key", $code, $ttl);
    }

    /**
     * 获取验证码TTL
     * @param $type
     * @param $action
     * @param $key
     * @return int
     */
    static function getTTL($type, $action, $key)
    {
        return redis()->ttl(self::prefix . "$type:$action:$key");
    }

    /**
     * 删除验证码
     * @param $type
     * @param $action
     * @param $key
     * @return int
     */
    static function del($type, $action, $key)
    {
        return redis()->del(self::prefix . "$type:$action:$key");
    }

    /**
     * 检查验证码
     * @param $type
     * @param $action
     * @param $key
     * @param $code
     */
    static function check($type, $action, $key, $code)
    {
        if (!self::isVerifyDebug()) {
            if ($code != self::get($type, $action, $key)) throw new BusinessException(ErrorCode::VERIFY_CODE_ERROR);
            self::del($type, $action, $key);
        }
    }

    /**
     * 是否验证码调试
     * @return bool
     */
    static function isVerifyDebug()
    {
        return env('VERIFY_CODE_DEBUG', 0) == 1;
    }
}