<?php


namespace App\Middleware;


use App\Exception\JwtException;
use App\Util\JwtUtil;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtAuthMiddleware implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isDebug() && !empty($info = env('JWT_DEBUG_INFO'))) {
            $explode = explode('|', trim($info, '|'));
            $info = [];
            foreach ($explode as $value) {
                $kv = explode('=', $value);
                if (count($kv) == 2) $info[$kv[0]] = $kv[1];
            }
        } else {
            $token = $request->getHeaderLine('authorization');
            if (strlen($token) > 1) {
                $arr = explode('Bearer ', $token);
                $token = $arr[1] ?? '';
            } elseif (isset($request->getParsedBody()['token'])) {
                $token = $request->getParsedBody()['token'];
            } elseif (isset($request->getQueryParams()['token'])) {
                $token = $request->getQueryParams()['token'];
            } else throw new JwtException('Token was empty or format error', 401);
            $info = JwtUtil::parseToken($token);
        }
        $request = $request->withAttribute('jwtInfo', $info);
        Context::set(ServerRequestInterface::class, $request);
        return $handler->handle($request);
    }
}