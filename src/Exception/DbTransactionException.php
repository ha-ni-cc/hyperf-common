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

namespace App\Exception;

use App\Constants\ErrorCode;
use App\Util\LogUtil;
use Hyperf\Server\Exception\ServerException;

class DbTransactionException extends ServerException
{
    public function __construct(\Throwable $e)
    {
        if ($e instanceof BusinessException) {
            $message = $e->getMessage();
            if (is_int($message)) {
                $code = $message;
                $message = ErrorCode::getMessage($message);
            } else {
                $code = $e->getCode();
                $message = $e->getMessage();
            }
        } else {
            $code = ErrorCode::SERVER_ERROR;
            $message = isDebug() ? $e->getMessage() : ErrorCode::getMessage($code);
            LogUtil::get()->error(__CLASS__, ['errMsg' => $e->getMessage(), 'errTrace' => $e->getTrace()]);
            LogUtil::stdout()->error(__CLASS__ . " {$e->getMessage()} {$e->getTraceAsString()}");
        }
        parent::__construct($message, (int)$code);
    }
}
