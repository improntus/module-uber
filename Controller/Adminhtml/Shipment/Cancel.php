<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml\Shipment;

use Exception;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\CancelShipment;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;
use const _PHPStan_582a9cb8b\__;

class Cancel extends Action
{
    const ADMIN_RESOURCE = 'Improntus_Uber::shipment_cancel';

    /**
     * @var CancelShipment $cancelShipment
     */
    protected CancelShipment $cancelShipment;

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
     * @param CancelShipment $cancelShipment
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Data $helper,
        Context $context,
        CancelShipment $cancelShipment,
        OrderRepository $orderRepository
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->cancelShipment = $cancelShipment;
        $this->orderRepository = $orderRepository;
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
         * Cancel Shipping
         */
        try {
            $this->cancelShipment->cancel($orderId);
            $this->messageManager->addSuccessMessage(__('Shipment successfully canceled'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage('Uber: ' . __($e->getMessage()));
            $this->addCommentCancelError($orderId, __($e->getMessage()));
        }

        // Return
        $this->_redirect('sales/order/view', ['order_id' => $orderId]);
    }

    /**
     * addCommentCancelError
     * @param int $orderId
     * @param string $msgError
     * @return bool|void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function addCommentCancelError(int $orderId, string $msgError = '-')
    {
        $order = $this->orderRepository->get($orderId);
        if (is_null($order->getEntityId())) {
            return false;
        }
        try {
            $order->addCommentToStatusHistory(
                __('<strong>Uber Cancellation ERROR</strong>: %1', $msgError)
            );
            $this->orderRepository->save($order);
            return true;
        } catch (Exception $e) {
            $this->helper->log(__("Uber Shipping Cancel ERROR: %1", __($e->getMessage())));
        }
    }
}
