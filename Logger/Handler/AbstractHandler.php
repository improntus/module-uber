<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class AbstractHandler extends Base
{
    /**
     * @var array
     */
    protected $loggerTypes = [
        Logger::DEBUG,
        Logger::INFO,
        Logger::NOTICE,
        Logger::WARNING,
        Logger::ERROR,
        Logger::CRITICAL,
        Logger::ALERT,
        Logger::EMERGENCY,
    ];

    /**
     * @param array $record
     * @return bool
     */
    public function isHandling(array $record): bool
    {
        return in_array($record['level'], $this->loggerTypes);
    }
}
