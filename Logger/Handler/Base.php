<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Logger\Handler;

use Exception;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;

class Base extends AbstractHandler
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var array
     */
    protected $loggerTypes = [
        Logger::DEBUG,
        Logger::INFO,
        Logger::NOTICE,
        Logger::WARNING,
        Logger::ALERT,
        Logger::EMERGENCY,
    ];

    /**
     * @var string
     */
    protected $fileName = '/var/log/uber/debug.log';

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
        $this->fileName = '/var/log/uber/debug_' . date('m_Y') . '.log';
        parent::__construct($filesystem, $filePath, $fileName);
    }
}