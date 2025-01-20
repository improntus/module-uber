<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2025 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class EmailSender
{

    /**
     * Uber Shipment Templates
     */
    private const UBER_SHIPMENT_TEMPLATES = [
        'pickup' => [
            'template_identifier' => 'uber_pickup_template',
            'template_subject' => 'Shipment with Uber updated for order #%1 - Courier is en route to pick up shipment',
        ],
        'pickup_complete' => [
            'template_identifier' => 'uber_onway_template',
            'template_subject' => 'Shipment with Uber updated for order #%1 - Courier is moving towards the drop off location',
        ],
        'delivered' => [
            'template_identifier' => 'uber_delivered_template',
            'template_subject' => 'Shipment with Uber updated for order #%1 - Courier has completed the dropoff',
        ],
    ];

    /**
     * @var TransportBuilder $transportBuilder
     */
    protected TransportBuilder $transportBuilder;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @param Data $helper
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data                  $helper,
        TransportBuilder      $transportBuilder,
        StoreManagerInterface $storeManager
    )
    {
        $this->helper = $helper;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * @param OrderInterface $order
     * @param string $uberShipmentStatus
     * @param string $uberTrackingUrl
     * @return void
     * @throws LocalizedException
     * @throws MailException
     */
    public function sendEmail(OrderInterface $order, string $uberShipmentStatus, string $uberTrackingUrl)
    {
        if (in_array($uberShipmentStatus, array_keys(self::UBER_SHIPMENT_TEMPLATES))) {
            $templateData = self::UBER_SHIPMENT_TEMPLATES[$uberShipmentStatus];

            $storeId = $order->getStoreId();
            $customerEmail = $order->getCustomerEmail();
            $customerName = $order->getCustomerName();

            $this->transportBuilder
                ->setTemplateIdentifier($templateData['template_identifier'])
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars([
                    'customer_name' => $customerName,
                    'order_id' => $order->getIncrementId(),
                    'order_uber_tracking_url' => $uberTrackingUrl,
                ])
                ->addTo($customerEmail, $customerName)
                ->setFromByScope('sales');

            $bccMail = $this->getBccMail($uberShipmentStatus, $order->getStoreId());
            if($this->helper->getEnableEmailBCC($order->getStoreId()) && $bccMail !== null){
                $this->transportBuilder->addBcc($bccMail);
            }
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
        }
    }

    /**
     * @param $status
     * @param $storeId
     * @return string|null
     */
    private function getBccMail($status, $storeId = null)
    {
        $pickupMail = $this->helper->getUpdateEmailPickup($storeId);
        $onwayMail = $this->helper->getUpdateEmailOnway($storeId);
        $dropoffMail = $this->helper->getUpdateEmailDropoff($storeId);

        if (empty($onwayMail) || empty($dropoffMail)) {
            return $pickupMail;
        }

        switch ($status) {
            case 'pickup':
                return $pickupMail;
            case 'pickup_complete':
                return $onwayMail;
            case 'delivered':
                return $dropoffMail;
            default:
                return null;
        }
    }

}
