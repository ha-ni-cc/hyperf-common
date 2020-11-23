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

use Hyperf\Server\Exception\ServerException;
use App\Constants\ErrorCode;
use Throwable;

class BusinessException extends ServerException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (is_int($message)) {
            $code = $message;
            $message = ErrorCode::getMessage($message);
        }

        parent::__construct($message, $code, $previous);
    }
}
