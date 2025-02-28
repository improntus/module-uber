<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Warehouse;

use Exception;
use Improntus\Uber\Api\Data\StoreInterface;
use Improntus\Uber\Api\WarehouseRepositoryInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\OrganizationRepository;
use Improntus\Uber\Model\ResourceModel\Store\Collection as UberStoreCollection;
use Improntus\Uber\Model\StoreRepository;
use Improntus\Uber\Model\WaypointRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class WarehouseRepository implements WarehouseRepositoryInterface
{

    /**
     * @var WaypointRepository $waypointRepository
     */
    protected WaypointRepository $waypointRepository;

    /**
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var TimezoneInterface $timezone
     */
    protected TimezoneInterface $timezone;

    /**
     * @var OrganizationRepository $organizationRepository
     */
    protected OrganizationRepository $organizationRepository;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @var UberStoreCollection $uberStoreCollection
     */
    protected UberStoreCollection $uberStoreCollection;

    /**
     * @var StoreRepository $uberStoreRepository
     */
    protected StoreRepository $uberStoreRepository;

    /**
     * @param WaypointRepository $waypointRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TimezoneInterface $timezone
     * @param OrganizationRepository $organizationRepository
     * @param Data $helper
     * @param UberStoreCollection $uberStoreCollection
     * @param StoreRepository $uberStoreRepository
     */
    public function __construct(
        WaypointRepository $waypointRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TimezoneInterface $timezone,
        OrganizationRepository $organizationRepository,
        Data $helper,
        UberStoreCollection $uberStoreCollection,
        StoreRepository $uberStoreRepository
    ) {
        $this->helper = $helper;
        $this->timezone = $timezone;
        $this->waypointRepository = $waypointRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->organizationRepository = $organizationRepository;
        $this->uberStoreCollection = $uberStoreCollection;
        $this->uberStoreRepository = $uberStoreRepository;
    }

    /**
     * getAvailableSources
     *
     * Return Available Sources
     * @param int $storeId
     * @param array $cartItemsSku
     * @param string $countryId
     * @param $regionId
     * @return array
     */
    public function getAvailableSources(int $storeId, array $cartItemsSku, string $countryId, $regionId): array
    {
        // Uber Waypoint
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('store_id', $storeId)
            ->addFilter('active', 1)
            ->create();
        return $this->waypointRepository->getList($searchCriteria)->getItems();
    }

    /**
     * checkWorkSchedule
     *
     * Return the available warehouse based on the delivery time.
     * @param $warehouse
     * @param $deliveryTime
     * @return bool
     */
    public function checkWarehouseWorkSchedule($warehouse, $deliveryTime): bool
    {
        if ($warehouse->getActive()) {
            $daysOfWeek = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            $day = ucfirst($daysOfWeek[$this->timezone->date()->format('w')]);
            $openHour = $warehouse->{"get{$day}Open"}();
            $closeHour = $warehouse->{"get{$day}Close"}();
            $deliveryHour = $deliveryTime->format("H");

            // Check Waypoint Availability
            if ($deliveryHour >= $openHour && $deliveryHour <= $closeHour) {
                return true;
            }
        }

        return false;
    }

    /**
     * checkWarehouseClosest
     *
     * Return the nearest warehouse to the customer.
     * @param array $uberStores
     * @param $warehouses
     * @return void
     */
    public function checkWarehouseClosest(array $uberStores, $warehouses)
    {
        // If there are no stores available on Uber, I leave directly
        if (!isset($uberStores['stores'])) {
            return null;
        }

        // Get ExternalId from UberStores
        $uberWarehouses = array_map(fn ($store) => $store['external_id'], $uberStores['stores']);

        /**
         * Get Sources by Uber Stores
         */
        $this->uberStoreCollection->getSelect()
            ->join(
                ["iuw" => "improntus_uber_waypoint"],
                'main_table.waypoint_id = iuw.waypoint_id'
            );
        $uberSources = $this->uberStoreCollection->addFieldToFilter(StoreInterface::ENTITY_ID, ['in' => $uberWarehouses])
            ->addFieldToFilter("main_table." . StoreInterface::WAYPOINT_ID, ['neq' => null])
            ->addFieldToFilter("iuw.active", ['eq' => 1])
            ->getItems();

        /**
         * Get Warehouse Closest
         *
         * I go through the Magento Waypoints and verify, if it corresponds to the first key of $uberWarehouses, it is the one closest to the client.
         * If NOT applicable, I store it in $alternativeWaypoint.
         * Then I determine which Warehouse / Waypoint to use.
         */
        $closestWarehouse = null;
        $alternativeWaypoint = null;
        $deliveryTimeLocal = $this->helper->getDeliveryTime();
        $showUberShipping = $this->helper->showUberShippingOBH();
        foreach ($uberSources as $uberStore) {
            $isWarehouseValid = in_array($uberStore->getId(), $uberWarehouses);
            if ($showUberShipping || $this->checkWarehouseWorkSchedule($uberStore, $deliveryTimeLocal)) {
                if ($isWarehouseValid) {
                    if ($uberStore->getId() == $uberWarehouses[0]) {
                        $closestWarehouse = $uberStore;
                        break;
                    } else {
                        // Alternative Waypoint
                        $alternativeWaypoint = $uberStore;
                    }
                }
            }
        }
        return $closestWarehouse ?? $alternativeWaypoint;
    }

    /**
     * getWarehouseAddressData
     *
     * Returns json with the warehouse address
     * @param $warehouse
     * @return string
     * @throws Exception
     */
    public function getWarehouseAddressData($warehouse): string
    {
        // Prepare Warehouse Address data
        $address = [
            'street_address' => [$warehouse->getAddress()],
            'city' => $warehouse->getCity(),
            'state' => $warehouse->getRegion(),
            'zip_code' => $warehouse->getPostcode(),
            'country' => $warehouse->getCountry()
        ];
        return json_encode($address, JSON_UNESCAPED_SLASHES);
    }

    /**
     * getWarehouseOrganization
     *
     * Return customerId (OrganizationId)
     * @param $warehouse
     * @return mixed|string
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function getWarehouseOrganization($warehouse): mixed
    {
        $organizationId = $warehouse->getOrganizationId();
        if (str_contains($organizationId, 'W') !== false) {
            // Use ROOT Organization from Shipping Configuration
            [$letter, $websiteId] = explode('W', $organizationId);
            return $this->helper->getCustomerId($websiteId);
        }

        // Get from Organization
        $organizationModel = $this->organizationRepository->get($organizationId);
        if ($organizationModel->getId() === null) {
            throw new Exception(__("Warehouse Repository Missing Organization"));
        }

        return $organizationModel->getUberOrganizationId();
    }

    /**
     * getWarehouseId
     *
     * Return Uber WaypointID
     * @param $warehouse
     * @return mixed
     */
    public function getWarehouseId($warehouse)
    {
        return $warehouse->getWaypointId();
    }

    /**
     * getWarehouse
     *
     * Returns Uber Waypoint information
     * @param $warehouseId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getWarehouse($warehouseId)
    {
        return $this->waypointRepository->get($warehouseId);
    }

    /**
     * getWarehousePickupData
     *
     * Returns json with the waypoint information
     * @param $warehouse
     * @return array
     * @throws Exception
     */
    public function getWarehousePickupData($warehouse)
    {
        $pickupData = [
            'pickup_phone_number' => $warehouse->getTelephone(),
            'pickup_name' => $warehouse->getName(),
            'pickup_business_name' => $warehouse->getName(),
            'pickup_latitude' => (float)$warehouse->getLatitude(),
            'pickup_longitude' => (float)$warehouse->getLongitude()
        ];

        // Set Pickup Address Data
        $pickupData['pickup_address'] = $this->getWarehouseAddressData($warehouse);

        // Set Pickup Notes
        if ($warehouse->getInstructions() !== null) {
            $pickupData['pickup_notes'] = $warehouse->getInstructions();
        }

        // Return Data
        return $pickupData;
    }

    /**
     * Get Warehouse Store
     *
     * @param $warehouse
     * @return mixed
     */
    public function getWarehouseStore($warehouse)
    {
        $uberStore = $this->uberStoreRepository->getByWaypoint($warehouse->getWaypointId());
        if ($uberStore === null) {
            return false;
        }
        return $uberStore->getId();
    }

    /**
     * Get Sources MSI by Website
     *
     * @param $storeId
     * @return array
     */
    public function getSourcesByWebsite($storeId): array
    {
        return [];
    }

    /**
     * getWarehouseName
     * @param $warehouse
     * @return mixed
     */
    public function getWarehouseName($warehouse): string
    {
        return $warehouse->getName() ?: 'n/a';
    }
}
