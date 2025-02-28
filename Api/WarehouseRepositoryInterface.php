<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api;

interface WarehouseRepositoryInterface
{
    /**
     * getAvailableSources
     * @param int $storeId
     * @param array $cartItemsSku
     * @param string $countryId
     * @param $regionId
     * @return mixed
     */
    public function getAvailableSources(int $storeId, array $cartItemsSku, string $countryId, $regionId);

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
     * @param array $uberStores
     * @param $warehouses
     * @return mixed
     */
    public function checkWarehouseClosest(array $uberStores, $warehouses);

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

    /**
     * Get Warehouse Store
     *
     * @param $warehouse
     * @return mixed
     */
    public function getWarehouseStore($warehouse);

    /**
     * Get Sources MSI by Website
     *
     * @param $storeId
     * @return array
     */
    public function getSourcesByWebsite($storeId): array;

    /**
     * getWarehouseName
     * @param $warehouse
     * @return mixed
     */
    public function getWarehouseName($warehouse): string;
}
