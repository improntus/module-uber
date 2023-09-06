<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Logger\Handler;

use Monolog\Logger as MonologLogger;
use Magento\Framework\Logger\Handler\Base as BaseHandler;

class CustomHandler extends BaseHandler
{
    protected $loggerType = MonologLogger::INFO;

    protected $fileName = 'var/log/uber/info.log';
}
