<?php

namespace App\Util;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\WebSocketServer\Sender;

class WebSocketUtil
{
    const wsPoolName = 'subscribe';
    const wsFd = '_ws:fd';
    const wsDevice = '_ws:device';
    const wsUser = '_ws:user';
    const wsAction = '_ws:action';
    const wsActionFd = self::wsAction . '_fd';
    const wsPublish = '_ws:publish';

    /**
     * 成功消息
     * @param $action
     * @param null $data
     * @param int $code
     * @param string $msg
     * @return array
     */
    public static function successMsg($action, $data = null, int $code = 1, $msg = 'ok')
    {
        return ['action' => $action, 'code' => $code, 'msg' => $msg, 'data' => $data];
    }

    /**
     * 失败消息
     * @param $msg
     * @param string $action
     * @param int $code
     * @param null $data
     * @return array
     */
    public static function failMsg($msg, $action = 'ws.fail', int $code = 0, $data = null)
    {
        return ['action' => $action, 'code' => $code, 'msg' => $msg, 'data' => $data];
    }

    /**
     * 清空连接
     */
    public static function flushConnection()
    {
        $keys = redis(self::wsPoolName)->keys('_ws:*');
        if ($keys) redis(self::wsPoolName)->del(...$keys);
    }

    /**
     * 清理离线fd
     */
    public static function clearOfflineFd()
    {
        $fds = redis(self::wsPoolName)->hKeys(self::wsFd);
        foreach ($fds as $fd) {
            if (!self::status($fd)) {
                self::delFd($fd);
                /** @var StdoutLoggerInterface $logger */
                $logger = di(StdoutLoggerInterface::class);
                $logger->info(__CLASS__ . ' 已清理离线的fd：' . $fd);
            }
        }
    }

    /**
     * 通过fd获取用户id
     * @param $fd
     * @param bool $throwError
     * @return string
     */
    public static function getUser($fd, $throwError = true)
    {
        $userId = redis(self::wsPoolName)->hGet(self::wsFd, (string)$fd);
        if ($throwError && empty($userId)) throw new \Exception('USER_CONNECTION_NOT_FOUND', 500);
        return $userId;
    }

    /**
     * 获取在线的用户id
     * @return array
     */
    public static function getOnlineUserIds()
    {
        return array_unique(array_values(redis(self::wsPoolName)->hGetAll(self::wsFd)));
    }

    /**
     * 设置用户id对应fd
     * @param $userId
     * @param $fd
     * @return bool|int
     */
    public static function setUser($userId, $fd)
    {
        return redis(self::wsPoolName)->sAdd(self::wsUser . ':' . $userId, $fd);
    }

    /**
     * 通过用户id获取fd
     * @param $userId
     * @return array
     */
    public static function getFd($userId)
    {
        return redis(self::wsPoolName)->sMembers(self::wsUser . ':' . $userId);
    }

    /**
     * 设置fd对应用户id
     * @param $fd
     * @param $userId
     * @return bool|int
     */
    public static function setFd($fd, $userId)
    {
        return redis(self::wsPoolName)->hSet(self::wsFd, (string)$fd, $userId);
    }

    /**
     * 删除fd
     * @param $fd
     */
    public static function delFd($fd)
    {
        self::delDevice($fd);
        self::delAction($fd);
        $userId = redis(self::wsPoolName)->hGet(self::wsFd, (string)$fd);
        redis(self::wsPoolName)->sRem(self::wsUser . ':' . $userId, $fd);
        redis(self::wsPoolName)->hDel(self::wsFd, (string)$fd);
    }

    /**
     * 获取fd总数
     * @return bool|int
     */
    public static function getFdCount()
    {
        return redis(self::wsPoolName)->hLen(self::wsFd);
    }

    /**
     * 设置fd的设备信息
     * @param $fd
     * @param $info
     * @return bool|int
     */
    public static function setDevice($fd, $info)
    {
        return redis(self::wsPoolName)->hSet(self::wsDevice, (string)$fd, $info);
    }

    /**
     * 获取fd的设备信息
     * @param mixed ...$fds
     * @return array
     */
    public static function getDevice(...$fds)
    {
        return array_values(redis(self::wsPoolName)->hMGet(self::wsDevice, ...$fds));
    }

    /**
     * 删除fd的设备信息
     * @param $fd
     * @return bool|int
     */
    public static function delDevice($fd)
    {
        return redis(self::wsPoolName)->hDel(self::wsDevice, $fd);
    }

    /**
     * 获取action的fd
     * @param $action
     * @return array
     */
    public static function getActionFd($action)
    {
        return redis(self::wsPoolName)->sMembers(self::wsAction . ':' . $action);
    }

    /**
     * 添加action
     * @param $fd
     * @param array $actions
     */
    public static function addAction($fd, array $actions)
    {
        foreach ($actions as $action) {
            redis(self::wsPoolName)->sAdd(self::wsAction . ':' . $action, $fd);
        }
        redis(self::wsPoolName)->sAdd(self::wsActionFd . ':' . $fd, ...$actions);
    }

    /**
     * 删除action
     * @param $fd
     * @param array|null $actions
     */
    public static function delAction($fd, ?array $actions = null)
    {
        if (empty($actions)) {
            $actions = redis(self::wsPoolName)->sMembers(self::wsActionFd . ':' . $fd);
            redis(self::wsPoolName)->del(self::wsActionFd . ':' . $fd);
        } else {
            redis(self::wsPoolName)->sRem(self::wsActionFd . ':' . $fd, ...$actions);
        }
        foreach ($actions as $action) {
            redis(self::wsPoolName)->sRem(self::wsAction . ':' . $action, (string)$fd);
        }
    }

    /**
     * 通过action进行广播
     * @param $action
     * @param $data
     */
    public static function actionBroadcast(string $action, string $data)
    {
        $fds = self::getActionFd($action);
        shuffle($fds);
        foreach ($fds as $fd) {
            go(function () use ($fd, $action, $data) {
                if (self::status($fd))
                    self::push((int)$fd, $data);
                else
                    self::delFd($fd);
            });
        }
    }

    /**
     * 发布消息
     * @param array $messageArr
     * @return int
     */
    public static function publish(array $messageArr)
    {
        $message = json_encode($messageArr, JSON_UNESCAPED_UNICODE);
        return redis(self::wsPoolName)->publish(self::wsPublish, $message);
    }

    /**
     * 推送消息
     * @param int $fd
     * @param $data
     * @param int $opcode
     * @param bool $finish
     */
    public static function push(int $fd, string $data, int $opcode = 1, bool $finish = true)
    {
        if (self::status($fd)) {
            /** @var Sender $sender */
            $sender = di(Sender::class);
            $sender->push($fd, $data, $opcode, $finish);
        } else self::delFd($fd);
    }

    /**
     * 获取连接状态
     * @param int $fd
     * @return bool
     */
    public static function status(int $fd)
    {
        if ($fd == 0) return false;
        /** @var Sender $sender */
        $sender = di(Sender::class);
        return $sender->check($fd);
    }

    /**
     * 断开连接
     * @param int $fd
     * @return bool
     */
    public static function disconnect(int $fd)
    {
        /** @var Sender $sender */
        $sender = di(Sender::class);
        return $sender->disconnect($fd);
    }

    /**
     * 发布推送
     * @param $uid
     * @param array $data
     * @param bool $close
     * @return int
     */
    public static function publishPush($uid, array $data, bool $close = false)
    {
        return self::publish([
            'invoke' => 'push',
            'uid' => $uid,
            'data' => $data,
            'closeFd' => $close
        ]);
    }

    /**
     * 发布广播
     * @param string $action
     * @param array $data
     * @return int
     */
    public static function publishBroadcast(string $action, array $data)
    {
        return self::publish([
            'invoke' => 'broadcast',
            'data' => $data,
        ]);
    }
}