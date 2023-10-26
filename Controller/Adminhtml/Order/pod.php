<?php

/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml\Order;

use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\OrderShipmentRepository;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class pod extends Action
{

    /**
     * @var JsonFactory $jsonFactory
     */
    protected JsonFactory $jsonFactory;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @var OrderShipmentRepository $orderShipmentRepository
     */
    protected OrderShipmentRepository $orderShipmentRepository;

    /**
     * @param OrderShipmentRepository $orderShipmentRepository
     * @param JsonFactory $jsonFactory
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(
        OrderShipmentRepository $orderShipmentRepository,
        JsonFactory $jsonFactory,
        Context $context,
        Data $helper
    ) {
        $this->orderShipmentRepository = $orderShipmentRepository;
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $orderId = $this->getRequest()->getParam('order_id');
        if (is_null($orderId)) {
            $this->helper->logDebug(__('POF: OrderID is required'));
            return $resultJson->setData(['error' => true, 'msg' => __('POF: OrderID is required')]);
        }

        if (!$this->helper->isModuleEnabled()) {
            return $resultJson->setData(['error' => true, 'msg' => __('Uber module is disabled')]);
        }

        try {
            $orderShipment = $this->orderShipmentRepository->getByOrderId($orderId);
            if (is_null($orderShipment->getId())) {
                return $resultJson->setData(['error' => true, 'msg' => __('Delivery has not yet been validated')]);
            }
            return $resultJson->setData(json_decode($orderShipment->getVerification()));
        } catch (NoSuchEntityException $e) {
            return $resultJson->setData(['error' => true, 'msg' => $e->getMessage()]);
        }
    }
}
