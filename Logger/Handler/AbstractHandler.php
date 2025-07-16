<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Level;

class AbstractHandler extends Base
{
    /**
     * @var array
     */
    protected $loggerTypes = [
        Level::Debug,
        Level::Info,
        Level::Notice,
        Level::Warning,
        Level::Error,
        Level::Critical,
        Level::Alert,
        Level::Emergency,
    ];
}
