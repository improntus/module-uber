<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Plugin;

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
        if ($this->helper->isModuleEnabled($order->getStoreId()) && $order->getShippingMethod() == self::CARRIER_CODE) {
            try {
                $orderData = $this->orderRepository->get($order->getId());

                // Create Shipping Button
                if ($orderData->getShipmentsCollection()->getSize() === 0 && !$order->hasShipments()) {

                    // Add when isAutoShipmentGenerationEnabled is False
                    if (!$this->helper->isAutomaticShipmentGenerationEnabled($order->getStoreId())) {
                        $baseUrl = $this->urlInterface->getUrl('uber/shipment/create', ['order_id' => $order->getId()]);
                        $buttonList->add(
                            'uber_ship',
                            [
                                'label' => __('Request Driver'),
                                'onclick' => "location.href='{$baseUrl}'",
                                'class' => 'uber-button'
                            ]
                        );
                    }
                }

                // Add Re-call / Cancel / Verification Buttons
                if ($orderData->getShipmentsCollection()->getSize() !== 0) {
                    $uberOrderShipmentRepository = $this->orderShipmentRepository->getByOrderId($order->getId());

                    // Button data
                    $buttonAction = $this->urlInterface->getUrl('uber/shipment/cancel', ['order_id' => $order->getId()]);
                    $buttonLabel = __('Cancel Driver');
                    if (is_null($uberOrderShipmentRepository->getUberShippingId())) {
                        $buttonLabel = __('Re-call Driver');
                        $buttonAction = $this->urlInterface->getUrl('uber/shipment/create', ['order_id' => $order->getId()]);
                    }
                    $buttonList->add(
                        'uber_ship',
                        [
                            'label' => $buttonLabel,
                            'onclick' => "location.href='{$buttonAction}'",
                            'class' => 'uber-button'
                        ]
                    );
                }
            } catch (InputException|NoSuchEntityException $e) {
                // TODO Logger msg
                $this->helper->log(__('Uber Order View Toolbar ERROR: %1', $e->getMessage()));
            }
        }

        return [$context, $buttonList];
    }
}
