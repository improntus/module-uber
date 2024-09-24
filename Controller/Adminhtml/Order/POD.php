<?php

/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml\Order;

use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\OrderShipmentRepository;
use Improntus\Uber\Model\Uber;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;

class POD extends Action
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
     * @var Uber $uber
     */
    protected Uber $uber;

    /**
     * @var OrderRepositoryInterface $orderRepository
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @param OrderShipmentRepository $orderShipmentRepository
     * @param JsonFactory $jsonFactory
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(
        OrderShipmentRepository $orderShipmentRepository,
        OrderRepositoryInterface $orderRepository,
        JsonFactory $jsonFactory,
        Context $context,
        Data $helper,
        Uber $uber
    ) {
        $this->orderShipmentRepository = $orderShipmentRepository;
        $this->orderRepository = $orderRepository;
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
        $this->uber = $uber;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $orderId = $this->getRequest()->getParam('order_id');
        if ($orderId === null) {
            $this->helper->logDebug(__('Proof of Delivery: OrderID is required'));
            return $resultJson->setData(['error' => true, 'msg' => __('Proof of Delivery: OrderID is required')]);
        }

        if (!$this->helper->isModuleEnabled()) {
            return $resultJson->setData(['error' => true, 'msg' => __('Uber module is disabled')]);
        }

        try {
            $orderShipment = $this->orderShipmentRepository->getByOrderId($orderId);
            if ($orderShipment->getId() === null) {
                return $resultJson->setData(['error' => true, 'msg' => __('Delivery has not yet been validated')]);
            }

            if ($this->helper->isWebhooksEnabled()) {
                // Return data from Webhook / UberOrderShipmentRepository
                return $resultJson->setData(json_decode($orderShipment->getVerification()));
            } else {
                /**
                 * Get Proof of Delivery from API
                 */
                $orderData = $this->orderRepository->get($orderShipment->getOrderId());
                $customerId = $this->helper->getCustomerId($orderData->getStoreId());
                $podData = $this->uber->getProofOfDelivery(
                    $orderShipment->getUberShippingId(),
                    $customerId,
                    $orderData->getStoreId()
                );
                return $resultJson->setData([
                    'origin' => 'api',
                    'document' => $podData->document ?: ''
                ]);
            }
        } catch (NoSuchEntityException|\Exception $e) {
            return $resultJson->setData(['error' => true, 'msg' => $e->getMessage()]);
        }
    }
}
