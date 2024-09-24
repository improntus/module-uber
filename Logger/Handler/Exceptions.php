<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Logger\Handler;

use Exception;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;

class Exceptions extends AbstractHandler
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::ERROR;

    /**
     * @var array
     */
    protected $loggerTypes = [
        Logger::ERROR,
        Logger::CRITICAL,
    ];

    /**
     * @var string
     */
    protected $fileName = '/var/log/uber/exception.log';

    /**
     * @param DriverInterface $filesystem
     * @param string|null $filePath
     * @param string|null $fileName
     * @throws Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        ?string         $filePath = null,
        ?string         $fileName = null
    ) {
        $this->fileName = '/var/log/uber/exception_' . date('m_Y') . '.log';
        parent::__construct($filesystem, $filePath, $fileName);
    }
}
