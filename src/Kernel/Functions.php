<?php

declare(strict_types=1);

/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use GuzzleHttp\Client;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Guzzle\HandlerStackFactory;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;
use Swoole\Server;

function isDebug()
{
    return env('APP_ENV') != 'prod';
}

/**
 * http客户端
 * @return Client
 */
function guzzleHttpClient()
{
    $factory = new HandlerStackFactory();
    $stack = $factory->create();
    return make(Client::class, [
        'config' => [
            'handler' => $stack,
        ],
    ]);
}

/**
 * 获取时间
 * @param $tz
 * @return false|string
 */
function getDateTime($tz = 0)
{
    return date('Y-m-d H:i:s', time() + $tz);
}

/**
 * 获取日期
 * @param null|string $time
 * @param int $tz
 * @return false|string
 */
function getYmd($time = null, $tz = 0)
{
    if ($time != null) {
        return date('Y-m-d', strtotime($time) + $tz);
    }
    return date('Y-m-d', time() + $tz);
}

function getToday($tz = 0)
{
    return date('Y-m-d', time() + $tz);
}

function getYesterday($tz = 0)
{
    return date('Y-m-d', strtotime('-1day') + $tz);
}

function getBetweenTime($date1, $date2 = null)
{
    if ($date2 == null) $arr = ["{$date1} 00:00:00", "{$date1} 23:59:59"];
    else $arr = ["{$date1} 00:00:00", "{$date2} 23:59:59"];
    return $arr;
}

function getBetweenTimestamp($date1, $date2 = null)
{
    $arr = getBetweenTime($date1, $date2);
    return [strtotime($arr[0]), strtotime($arr[1])];
}

/**
 * 获取百分比
 * @param $part
 * @param $all
 * @return string
 */
function getPercentStr($part, $all)
{
    if ($part == 0 || $all == 0) {
        return '0%';
    }
    return number_format($part / $all, 2) * 100 . '%';
}

/**
 * 获取间隔的月份数
 * @param $date1
 * @param $date2
 * @param string $tags
 * @return float|int
 */
function getDiffMonth($date1, $date2, $tags = '-')
{
    $date1 = explode($tags, $date1);
    $date2 = explode($tags, $date2);
    return abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]);
}

/**
 * 将一个字符串部分字符用$re替代隐藏
 * @param string $string 待处理的字符串
 * @param int $start 规定在字符串的何处开始，
 *                   正数 - 在字符串的指定位置开始
 *                   负数 - 在从字符串结尾的指定位置开始
 *                   0 - 在字符串中的第一个字符处开始
 * @param int $length 可选。规定要隐藏的字符串长度。默认是直到字符串的结尾。
 *                    正数 - 从 start 参数所在的位置隐藏
 *                    负数 - 从字符串末端隐藏
 * @param string $re 替代符
 * @return string 处理后的字符串
 */
function hideString($string, $start = 0, $length = 0, $re = '*')
{
    if (empty($string)) {
        return false;
    }
    $strArr = [];
    $mbStrLen = mb_strlen($string);
    while ($mbStrLen) {
        $strArr[] = mb_substr($string, 0, 1, 'utf8');
        $string = mb_substr($string, 1, $mbStrLen, 'utf8');
        $mbStrLen = mb_strlen($string);
    }
    $strLen = count($strArr);
    $begin = $start >= 0 ? $start : ($strLen - abs($start));
    $end = $last = $strLen - 1;
    if ($length > 0) {
        $end = $begin + $length - 1;
    } elseif ($length < 0) {
        $end -= abs($length);
    }
    for ($i = $begin; $i <= $end; ++$i) {
        $strArr[$i] = $re;
    }
    if ($begin >= $end || $begin >= $last || $end > $last) {
        return false;
    }
    return implode('', $strArr);
}

if (!function_exists('di')) {
    /**
     * Finds an entry of the container by its identifier and returns it.
     * @param null|mixed $id
     * @return mixed|\Psr\Container\ContainerInterface
     */
    function di($id = null)
    {
        $container = ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }
        return $container;
    }
}

if (!function_exists('format_money')) {
    function format_money($num, $scale = 2)
    {
        $scale = pow(10, $scale);
        return floor($num * $scale) / $scale;
    }
}

if (!function_exists('is_assoc')) {
    function is_assoc(array $arr)
    {
        return array_values($arr) !== $arr;
    }
}

if (!function_exists('swServer')) {
    function swServer(): Server
    {
        return di(Server::class);
    }
}

if (!function_exists('redis')) {
    /**
     * Get redis connection pool.
     * @param string $poolName
     * @return Redis
     */
    function redis(string $poolName = 'default')
    {
        /** @var RedisFactory $factory */
        $factory = di(RedisFactory::class);
        return $factory->get($poolName);
    }
}

if (!function_exists('format_throwable')) {
    /**
     * Format a throwable to string.
     * @param Throwable $throwable
     * @return string
     */
    function format_throwable(Throwable $throwable): string
    {
        return di()->get(FormatterInterface::class)->format($throwable);
    }
}
