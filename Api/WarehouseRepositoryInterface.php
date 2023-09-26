<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Api;

interface WarehouseRepositoryInterface
{
    /**
     * checkStockAvailability
     * @param string $sourceCode
     * @return bool
     */
    public function checkStockAvailability(string $sourceCode): bool;

    /**
     * getAvailableSources
     * @param int $storeId
     * @param array $cartItemsSku
     * @return mixed
     */
    public function getAvailableSources(int $storeId, array $cartItemsSku);

    /**
     * checkWarehouseWorkSchedule
     *
     * Return the available warehouse based on the delivery time.
     * @param $warehouse
     * @param $deliveryTime
     * @return bool
     */
    public function checkWarehouseWorkSchedule($warehouse, $deliveryTime): bool;

    /**
     * checkWarehouseClosest
     * @param array $customerCoords
     * @param $warehouses
     * @return mixed
     */
    public function checkWarehouseClosest(array $customerCoords, $warehouses);

    /**
     * getWarehouseAddressData
     * @param $warehouse
     * @return mixed
     */
    public function getWarehouseAddressData($warehouse);

    /**
     * getWarehouseOrganization
     * @param $warehouse
     * @return mixed
     */
    public function getWarehouseOrganization($warehouse);

    /**
     * getWarehouseId
     * @param $warehouse
     * @return mixed
     */
    public function getWarehouseId($warehouse);

    /**
     * getWarehouse
     * @param $warehouseId
     * @return mixed
     */
    public function getWarehouse($warehouseId);

    /**
     * getWarehousePickupData
     * @param $warehouse
     * @return mixed
     */
    public function getWarehousePickupData($warehouse);
}
