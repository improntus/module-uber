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
use Magento\Framework\Serialize\Serializer\Json;

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
     * @var Json $json
     */
    protected Json $json;

    /**
     * @param OrderShipmentRepository $orderShipmentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param JsonFactory $jsonFactory
     * @param Context $context
     * @param Data $helper
     * @param Uber $uber
     * @param Json $json
     */
    public function __construct(
        OrderShipmentRepository $orderShipmentRepository,
        OrderRepositoryInterface $orderRepository,
        JsonFactory $jsonFactory,
        Context $context,
        Data $helper,
        Uber $uber,
        Json $json
    ) {
        $this->orderShipmentRepository = $orderShipmentRepository;
        $this->orderRepository = $orderRepository;
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
        $this->uber = $uber;
        $this->json = $json;
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
                $verificationData = $orderShipment->getVerification();
                if($verificationData === null){
                    $customerId = $this->helper->getCustomerId($orderData->getStoreId());
                    $podData = $this->uber->getProofOfDelivery(
                        $orderShipment->getUberShippingId(),
                        $customerId,
                        $orderData->getStoreId()
                    );

                    // Save Verification Data
                    if($podData['document'] !== ''){
                        $verificationData = $podData['document'];
                        $orderShipment->setVerification($verificationData);
                        $orderShipment->save();
                    }
                }

                /**
                 * If the "decode" fails, it means that the verification is a base 64 image, so I modify the object
                 * that we return.
                 */
                try {
                    // Json Data
                    $verificationObjectData = $this->json->unserialize($verificationData);
                } catch (\Exception $e) {
                    // Base 64 Image / API Data
                    $verificationObjectData = [
                        'origin' => 'api',
                        'document' => $verificationData ?: ''
                    ];
                }

                return $resultJson->setData($verificationObjectData);
            }
        } catch (NoSuchEntityException|\Exception $e) {
            return $resultJson->setData(['error' => true, 'msg' => $e->getMessage()]);
        }
    }
}
