<?php


namespace App\Util;

use App\Exception\JwtException;

class JwtUtil
{

    /**
     * @param array $info
     * @return string
     */
    public static function createToken(array $info)
    {
        $payload = [
            'iss' => env('APP_NAME'),
            'iat' => time(),
            'exp' => time() + env('JWT_EXPIRE'),
            'ip' => IPUtil::getClientIP(),
            'info' => json_encode($info)
        ];
        $key = env('JWT_SECRET', 'JWT_SECRET');
        return \Firebase\JWT\JWT::encode($payload, $key, 'HS256');
    }

    /**
     * @param $jwt
     * @return array
     */
    public static function parseToken($jwt)
    {
        if (strlen($jwt) < 1) throw new JwtException('Token was empty or format error');
        $key = env('JWT_SECRET', 'JWT_SECRET');
        try {
            $decoded = \Firebase\JWT\JWT::decode($jwt, $key, ['HS256']);
            if (env('JWT_CHECK_IP', 0) == 1 && $decoded->ip != IPUtil::getClientIP()) throw new JwtException('IP mismatch', 401);
            $info = json_decode($decoded->info, true);
            if (empty($info) || !is_array($info)) throw new JwtException('Parse token error', 401);
            return $info;
        } catch (\Throwable $e) {
            throw new JwtException($e->getMessage(), 401);
        }
    }

    /**
     * @param $scope
     * @param $id
     * @param array $info
     * @return array
     */
    public static function storeToken($scope, $id, array $info)
    {
        if (isset($info['ip'])) unset($info['ip']);
        if (isset($info['time'])) unset($info['time']);
        $accessToken = self::createToken($info);
        $nowTime = time();
        $tokenInfo['access_token'] = $accessToken;
        $tokenInfo['expires_in'] = $nowTime + env('JWT_EXPIRE', 7200);
        $refreshToken = md5($accessToken . env('JWT_SECRET'));
        $tokenInfo['refresh_token'] = $refreshToken;
        $jwtRefresh = env('JWT_REFRESH', 604800);
        $tokenInfo['refresh_expires_in'] = $nowTime + $jwtRefresh;
        $tokenInfo['scope'] = $scope;
        $tokenInfo['id'] = $id;
        $info['ip'] = IPUtil::getClientIP();
        $info['time'] = date('Y-m-d H:i:s', $nowTime);
        $key = "_refreshToken:{$scope}:{$id}";
        $redis = redis();
        $redis->del($key);
        $redis->hSet($key, $refreshToken, json_encode($info));
        $redis->expire($key, intval($jwtRefresh));
        return $tokenInfo;
    }

    /**
     * @param $scope
     * @param $id
     * @param $refreshToken
     * @return array
     */
    public static function refreshToken($scope, $id, $refreshToken)
    {
        $key = "_refreshToken:{$scope}:{$id}";
        $info = redis()->hGet($key, $refreshToken);
        if (empty($info)) throw new JwtException('Refresh token expired', 401);
        $info = json_decode(strval($info), true);
        if (env('JWT_CHECK_IP') == 1 && $info['ip'] != IPUtil::getClientIP()) throw new JwtException('IP mismatch', 401);
        return self::storeToken($scope, $id, $info);
    }
}