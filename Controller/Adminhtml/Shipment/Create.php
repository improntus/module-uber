<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Controller\Adminhtml\Shipment;

use Exception;
use Improntus\Uber\Model\CreateShipment;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Create extends Action
{
    const ADMIN_RESOURCE = 'Improntus_Uber::shipment_create';

    protected CreateShipment $createShipment;

    public function __construct(
        Context $context,
        CreateShipment $createShipment
    ) {
        parent::__construct($context);
        $this->createShipment = $createShipment;
    }

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (is_null($orderId)) {
            // Todo: ERROR MSG
            $this->messageManager->addErrorMessage(__('Order ID parameter is required'));
        }

        /**
         * Create
         */
        try {
            $shipResponse = $this->createShipment->create($orderId);
            die;
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            die($e->getMessage());
        }
    }
}
