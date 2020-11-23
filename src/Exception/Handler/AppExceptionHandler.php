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

namespace App\Exception\Handler;

use App\Exception\BusinessException;
use App\Exception\DbTransactionException;
use App\Exception\JwtException;
use App\Exception\ParamException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $response = $response->withHeader('Server', 'Hyperf');
        if ($throwable instanceof ParamException) {
            return $response->withStatus(400)
                ->withHeader('Content-Type', 'text/html;charset=utf-8')
                ->withBody(new SwooleStream($throwable->getMessage()));
        } elseif ($throwable instanceof JwtException) {
            return $response->withStatus(401)
                ->withHeader('Content-Type', 'text/html;charset=utf-8')
                ->withBody(new SwooleStream($throwable->getMessage()));
        } elseif ($throwable instanceof BusinessException || $throwable instanceof DbTransactionException) {
            return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->withBody(new SwooleStream(json_encode(['ret' => 0, 'code' => $throwable->getCode(), 'msg' => $throwable->getMessage(), 'data' => null])));
        } elseif ($throwable instanceof DbTransactionException) {
            return $response->withStatus($throwable->getCode())
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->withBody(new SwooleStream($throwable->getMessage()));
        } elseif ($throwable instanceof HttpException) {
            return $response->withStatus($throwable->getStatusCode())
                ->withHeader('Content-Type', 'text/html;charset=utf-8')
                ->withBody(new SwooleStream($throwable->getMessage()));
        } else {
            $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
            $this->logger->error($throwable->getTraceAsString());
            return $response->withStatus(500)
                ->withHeader('Content-Type', 'text/html;charset=utf-8')
                ->withBody(new SwooleStream('Internal Server Error. Message: ' . $throwable->getMessage()));
        }
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
