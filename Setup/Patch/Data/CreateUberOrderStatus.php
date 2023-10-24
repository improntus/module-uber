<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Setup\Patch\Data;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class CreateUberOrderStatus implements DataPatchInterface
{
    /**
     * Status_Code => Status_Label
     */
    protected const UBER_STATUS = [
        'uber_pending' => 'Pending Delivery',
        'uber_canceled' => 'Delivery has been canceled',
        'uber_delivered' => 'Courier has completed the dropoff',
        'uber_dropoff' => 'Courier is moving towards the dropoff',
        'uber_pickup' => 'Courier is in route to pick up shipment',
        'uber_pickup_complete' => 'Courier has picked up the items and has begin moved towards the dropoff',
        'uber_returned' => 'The delivery was canceled, and a new delivery was created to return items to the sender'
    ];

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    protected ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var StatusFactory $statusFactory
     */
    protected StatusFactory $statusFactory;

    /**
     * @var StatusResourceFactory $statusResourceFactory
     */
    protected StatusResourceFactory $statusResourceFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StatusFactory $statusFactory
     * @param StatusResourceFactory $statusResourceFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }

    /**
     * @return void
     * @throws AlreadyExistsException
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /**
         * Create Status and Assign to State
         */
        foreach (self::UBER_STATUS as $status_code => $status_label) {
            $status = $this->statusFactory->create();
            $status->setStatus($status_code);
            $status->setLabel($status_label);
            $statusResource = $this->statusResourceFactory->create();
            $statusResource->save($status);
            $status->assignState(Order::STATE_COMPLETE, false, true);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}
