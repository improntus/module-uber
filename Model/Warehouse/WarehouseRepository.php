<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Warehouse;

use Exception;
use Improntus\Uber\Api\WarehouseRepositoryInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\OrganizationRepository;
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
     * @param WaypointRepository $waypointRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TimezoneInterface $timezone
     * @param OrganizationRepository $organizationRepository
     * @param Data $helper
     */
    public function __construct(
        WaypointRepository $waypointRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TimezoneInterface $timezone,
        OrganizationRepository $organizationRepository,
        Data $helper
    ) {
        $this->helper = $helper;
        $this->timezone = $timezone;
        $this->waypointRepository = $waypointRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->organizationRepository = $organizationRepository;
    }

    /**
     * getAvailableSources
     *
     * Return Available Sources
     * @param int $storeId
     * @param array $cartItemsSku
     * @param string $countryId
     */
    public function getAvailableSources(int $storeId, array $cartItemsSku, string $countryId): array
    {
        // Uber Waypoint
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('store_id', $storeId)->create();
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
        $daysOfWeek = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
        $day = ucfirst($daysOfWeek[$this->timezone->date()->format('w')]);
        $openHour = $warehouse->{"get{$day}Open"}();
        $closeHour = $warehouse->{"get{$day}Close"}();
        $deliveryHour = $deliveryTime->format("H");
        // Check Waypoint Availability
        if ($deliveryHour >= $openHour && $deliveryHour <= $closeHour) {
            return true;
        }
        return false;
    }

    /**
     * checkWarehouseClosest
     *
     * Return the nearest warehouse to the customer.
     * @param array $customerCoords
     * @param $warehouses
     * @return void
     */
    public function checkWarehouseClosest(array $customerCoords, $warehouses)
    {
        // Init Vars
        $closestDistance = PHP_FLOAT_MAX;
        $closestWarehouse = null;

        // Get Customer Coordinates
        $customerLatitude = deg2rad($customerCoords['latitude']);
        $customerLongitude = deg2rad($customerCoords['longitude']);

        // Find the closest Warehouse
        foreach ($warehouses as $warehouse) {
            $warehouseLatitude = deg2rad($warehouse->getLatitude());
            $warehouseLongitude = deg2rad($warehouse->getLongitude());
            $dLat = $warehouseLatitude - $customerLatitude;
            $dLon = $warehouseLongitude - $customerLongitude;
            $a = sin($dLat / 2) ** 2 + cos($customerLatitude) * cos($warehouseLatitude) * sin($dLon / 2) ** 2;
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $earthRadius = 6371; // Average Earth radius in kilometers
            $distance = $earthRadius * $c;
            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closestWarehouse = $warehouse;
            }
        }
        return $closestWarehouse;
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
        if ($organizationId == 0) {
            // Use ROOT Organization from Shipping Configuration
            return $this->helper->getCustomerId();
        }

        // Get from Organization
        $organizationModel = $this->organizationRepository->get($organizationId);
        if (is_null($organizationModel->getId())) {
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
        return $warehouse->getId();
    }

    /**
     * getWarehouse
     *
     * Returns Uber Waypoint information
     * @param int|string $warehouseId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getWarehouse(int|string $warehouseId)
    {
        return $this->waypointRepository->get($warehouseId);
    }

    /**
     * getWarehousePickupData
     *
     * Returns json with the waypoint information
     * @param $warehouse
     * @return array
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
        if (!is_null($warehouse->getInstructions())) {
            $pickupData['pickup_notes'] = $warehouse->getInstructions();
        }

        // Return Data
        return $pickupData;
    }
}
