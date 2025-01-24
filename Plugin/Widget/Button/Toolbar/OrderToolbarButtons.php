<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Plugin\Widget\Button\Toolbar;

use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\Carrier\Uber;
use Improntus\Uber\Model\OrderShipmentRepository;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\Toolbar;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class OrderToolbarButtons
{
    protected const CARRIER_CODE = Uber::CARRIER_CODE . '_' . Uber::CARRIER_CODE;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @var OrderRepository $orderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @var UrlInterface $urlInterface
     */
    protected UrlInterface $urlInterface;

    /**
     * @var OrderShipmentRepository $orderShipmentRepository
     */
    protected OrderShipmentRepository $orderShipmentRepository;

    /**
     * @param Data $helper
     * @param UrlInterface $urlInterface
     * @param OrderRepository $orderRepository
     * @param OrderShipmentRepository $orderShipmentRepository
     */
    public function __construct(
        Data $helper,
        UrlInterface $urlInterface,
        OrderRepository $orderRepository,
        OrderShipmentRepository $orderShipmentRepository
    ) {
        $this->helper = $helper;
        $this->urlInterface = $urlInterface;
        $this->orderRepository = $orderRepository;
        $this->orderShipmentRepository = $orderShipmentRepository;
    }

    /**
     * beforePushButtons
     * @param Toolbar $subject
     * @param AbstractBlock $context
     * @param ButtonList $buttonList
     * @return array
     */
    public function beforePushButtons(
        Toolbar $subject,
        AbstractBlock $context,
        ButtonList $buttonList
    ): array {
        if (!$context instanceof View) {
            return [$context, $buttonList];
        }

        $order = $context->getOrder();

        // If the order is closed or canceled I do not render the buttons
        $orderStatusNotAllowed = [Order::STATE_CANCELED, Order::STATE_CLOSED];
        if (in_array($order->getStatus(), $orderStatusNotAllowed)) {
            return [$context, $buttonList];
        }

        if ($this->helper->isModuleEnabled($order->getStoreId()) && $order->getShippingMethod() == self::CARRIER_CODE) {
            try {
                $orderData = $this->orderRepository->get($order->getId());

                // Create Shipping Button
                if ($orderData->getShipmentsCollection()->getSize() === 0 && !$order->hasShipments()) {
                    $baseUrl = $this->urlInterface->getUrl(
                        'uber/shipment/create',
                        ['order_id' => $order->getId()
                        ]
                    );
                    $buttonList->add(
                        'uber_ship',
                        [
                            'label' => __('Request Driver'),
                            'onclick' => "location.href='{$baseUrl}'",
                            'class' => 'uber-button'
                        ]
                    );
                }

                // Add Re-call / Cancel / Verification Buttons
                if ($orderData->getShipmentsCollection()->getSize() !== 0) {
                    $uberOrderShipmentRepository = $this->orderShipmentRepository->getByOrderId($order->getId());

                    // Button data
                    $buttonAction = $this->urlInterface->getUrl(
                        'uber/shipment/cancel',
                        [
                        'order_id' => $order->getId()]
                    );
                    $buttonLabel = __('Cancel Driver');
                    if ($uberOrderShipmentRepository->getUberShippingId() === null) {
                        $buttonLabel = __('Re Request Driver');
                        $buttonAction = $this->urlInterface->getUrl(
                            'uber/shipment/create',
                            ['order_id' => $order->getId()]
                        );
                    }

                    // Show buttons
                    if ($uberOrderShipmentRepository->getStatus() === 'delivered' &&
                        $uberOrderShipmentRepository->getVerification() !== null) {
                        // Show POD Retrieval Button
                        $buttonOptions = json_encode([
                            'order_id' => $order->getId(),
                            'url' => $this->urlInterface->getUrl('uber/order/pod')
                        ]);
                        $onclickJs = "jQuery('#uber_pod').orderUberPod($buttonOptions).orderUberPod('showPOD');";
                        $buttonList->add(
                            'uber_pod',
                            [
                                'label' => __('Proof of Delivery'),
                                'class' => 'uber-button',
                                'onclick' => $onclickJs,
                                'data_attribute' => [
                                    'mage-init' => '{"orderUberPod":{}}',
                                ]
                            ]
                        );
                    } else {
                        // Default Action
                        $buttonList->add(
                            'uber_ship',
                            [
                                'label' => $buttonLabel,
                                'onclick' => "location.href='{$buttonAction}'",
                                'class' => 'uber-button'
                            ]
                        );
                    }
                }
            } catch (InputException|NoSuchEntityException $e) {
                $this->helper->log(__('Uber Order View Toolbar ERROR: %1', $e->getMessage()));
            }
        }

        return [$context, $buttonList];
    }
}
