<?php


namespace App\Util;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;

class LogUtil
{
    /**
     * @param string $name
     * @param string $group
     * @return LoggerInterface
     */
    public static function get($name = 'app', $group = 'default'): LoggerInterface
    {
        /** @var LoggerFactory $logger */
        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class);
        return $logger->get($name, $group);
    }

    public static function stdout()
    {
        /** @var StdoutLoggerInterface $logger */
        $logger = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        return $logger;
    }
}