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

namespace App\Controller;

use App\Constants\DeviceType;
use App\Exception\JwtException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use WhichBrowser\Parser;

abstract class AbstractController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;

    /**
     * 返回成功
     * @param null $data
     * @param int $code
     * @param string $msg
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function success($data = null, int $code = 1, $msg = 'SUCCESS')
    {
        return $this->response->json(['ret' => 1, 'code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 返回失败
     * @param $msg
     * @param int $code
     * @param null $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function fail($msg, int $code = 0, $data = null)
    {
        return $this->response->json(['ret' => 0, 'code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 获取jwtInfo
     * @param string $key
     * @return mixed|null
     */
    public function getJwtInfo($key = '')
    {
        $info = $this->request->getAttribute('jwtInfo');
        if (empty($info)) throw new JwtException('Forgot to use middleware cause jwtInfo empty', 401);
        if ($key == '') return $info;
        return $info[$key] ?? null;
    }

    /**
     * 获取page
     * @return int
     */
    public function getPage(): int
    {
        return intval($this->request->query('page', 1));
    }

    /**
     * 获取offset
     * @return int
     */
    public function getOffset(): int
    {
        return intval($this->request->query('offset', 0));
    }

    /**
     * 获取limit
     * @param int $maxLimit
     * @return int
     */
    public function getLimit(int $maxLimit = 1000): int
    {
        $limit = intval($this->request->query('limit', 10));
        return $limit > $maxLimit ? $maxLimit : $limit;
    }

    /**
     * 获取search
     * @return string|null
     */
    public function getSearch(): ?string
    {
        return $this->request->query('search', '');
    }

    /**
     * 获取RequestToken
     * @return bool|string
     */
    public function getRequestToken()
    {
        $token = $this->request->input('token');
        if (!empty($token)) return urldecode($token);
        throw new JwtException('Token was empty or format error', 401);
    }

    /**
     * 获取BearerToken
     * @return bool|string
     */
    public function getBearerToken()
    {
        $token = $this->request->header('authorization', '');
        if (strlen($token) > 0) {
            $arr = explode('Bearer ', $token);
            $token = $arr[1] ?? '';
            if (strlen($token) > 0) return $token;
        }
        throw new JwtException('Token was empty or format error', 401);
    }

    public function getUserAgent()
    {
        return $this->request->getHeaderLine('user-agent');
    }

    public function getDeviceType($ua)
    {
        if (true == preg_match("/.+Windows.+/", $ua)) {
            return DeviceType::PC;
        } elseif (true == preg_match("/.+Macintosh.+/", $ua)) {
            return DeviceType::PC;
        } elseif (true == preg_match("/.+iPad.+/", $ua)) {
            return DeviceType::IOS;
        } elseif (true == preg_match("/.+iPhone.+/", $ua)) {
            return DeviceType::IOS;
        } elseif (true == preg_match("/.+Android.+/", $ua)) {
            return DeviceType::ANDROID;
        } else return DeviceType::UNKNOWN;
    }

    public function getDeviceBrowserInfo($ua)
    {
        $result = new Parser($ua);
        return $result->browser->toString();
    }
}
