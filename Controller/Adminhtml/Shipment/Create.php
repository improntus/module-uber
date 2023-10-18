<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Controller\Adminhtml\Shipment;

use Exception;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\CreateShipment;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

class Create extends Action
{
    const ADMIN_RESOURCE = 'Improntus_Uber::shipment_create';

    /**
     * @var CreateShipment $createShipment
     */
    protected CreateShipment $createShipment;

    /**
     * @var OrderRepository $orderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @param Data $helper
     * @param Context $context
     * @param CreateShipment $createShipment
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Data $helper,
        Context $context,
        CreateShipment $createShipment,
        OrderRepository $orderRepository
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->createShipment = $createShipment;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (is_null($orderId)) {
            // Todo: ERROR MSG
            $this->messageManager->addErrorMessage(__('Order ID parameter is required'));
        }

        /**
         * Create Shipping
         */
        try {
            $shippingResponse = $this->createShipment->create($orderId);
            $this->addCommentConfirmation($orderId, $shippingResponse);
            $this->messageManager->addSuccessMessage(__('Shipment generated successfully'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->addCommentShippingError($orderId, $e->getMessage());
        }

        // Return
        $this->_redirect('sales/order/view', ['order_id' => $orderId]);
    }

    /**
     * addCommentShippingError
     * @param int $orderId
     * @param string $msgError
     * @return bool|void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function addCommentShippingError(int $orderId, string $msgError = '-')
    {
        $order = $this->orderRepository->get($orderId);
        if (is_null($order->getEntityId())) {
            return false;
        }

        try {
            $order->addCommentToStatusHistory(
                __('<strong>Uber Shipping Create ERROR</strong>: %1', $msgError)
            );

            $this->orderRepository->save($order);
            return true;
        } catch (Exception $e) {
            $this->helper->log(__("Uber Shipping Create ERROR: %1", $e->getMessage()));
        }
    }

    /**
     * addCommentConfirmation
     * @param int $orderId
     * @param array $cancellationData
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function addCommentConfirmation(int $orderId, array $cancellationData): void
    {
        $order = $this->orderRepository->get($orderId);
        if (is_null($order->getEntityId())) {
            return;
        }

        try {
            $order->addCommentToStatusHistory(
                __('<strong>Uber Shipping ID</strong>: %1', $cancellationData['id']),
                'uber_pending'
            );

            $this->orderRepository->save($order);
        } catch (Exception $e) {
            $this->helper->log(__("Uber Shipping Cancel ERROR: %1", $e->getMessage()));
        }
    }
}
