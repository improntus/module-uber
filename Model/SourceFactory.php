<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */
namespace Improntus\Uber\Model;

use Improntus\Uber\Helper\Data;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

class SourceFactory
{
    /**
     * @var Manager
     */
    protected Manager $moduleManager;

    /**
     * @var ObjectManagerInterface
     */
    protected ObjectManagerInterface $objectManager;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @param Manager $moduleManager
     * @param Data $helper
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Manager $moduleManager,
        Data $helper,
        ObjectManagerInterface $objectManager
    ) {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->helper = $helper;
    }

    /**
     * create
     * @param array $data
     * @return mixed|string
     */
    public function create(array $data = [])
    {
        if ($this->moduleManager->isEnabled('Magento_Inventory') && $this->helper->getSourceOrigin()) {
            $instanceName =  'Magento\Inventory\Model\SourceRepository';
        } else {
            $instanceName = 'Improntus\Uber\Model\WaypointRepository';
        }
        return $this->objectManager->create($instanceName, $data);
    }
}
