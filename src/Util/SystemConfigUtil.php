<?php


namespace App\Util;


use Hyperf\DbConnection\Db;

class SystemConfigUtil
{
    private static function table()
    {
        return Db::table('t_system_config');
    }

    private static function cacheKey()
    {
        return '_cache:systemConfig';
    }

    public static function API_HOST()
    {
        return self::get('BACKEND_API_HOST');
    }

    public static function get($key, $default = null)
    {
        $value = redis()->hGet(self::cacheKey(), $key);
        if ($value === false) {
            $value = self::table()->where('config_key', $key)->value('config_value');
            if ($value != null) redis()->hSet(self::cacheKey(), $key, $value);
            else $value = $default;
        }
        return $value;
    }

    public static function set($key, $value)
    {
        $rs = self::table()->where('config_key', $key)->update(['config_value' => $value]);
        if ($rs) redis()->hSet(self::cacheKey(), $key, $value);
        return $rs;
    }

    public static function getMultiple($keyArr)
    {
        $arr = [];
        foreach ($keyArr as $key) {
            $arr[$key] = self::get($key);
        }
        return $arr;
    }

    public static function getAll()
    {
        return self::table()
            ->where('config_sequence', '!=', 0)
            ->orderByDesc('config_sequence')
            ->get()->toArray();
    }

    public static function flushCache()
    {
        $keys = self::table()->pluck('config_key')->toArray();
        if (empty($keys)) return false;
        $rs = redis()->hDel(self::cacheKey(), ...$keys);
        return boolval($rs);
    }

}