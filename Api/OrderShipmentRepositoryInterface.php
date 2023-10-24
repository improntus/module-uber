<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api;

use Improntus\Uber\Api\Data\OrderShipmentInterface;
use Improntus\Uber\Api\Data\OrderShipmentSearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface OrderShipmentRepositoryInterface
{
    /**
     * @param OrderShipmentInterface $entity
     * @return mixed
     */
    public function save(OrderShipmentInterface $entity);

    /**
     * @param $id
     * @return OrderShipmentInterface
     * @throws NoSuchEntityException
     */
    public function get($id);

    /**
     * @param $orderId
     * @return OrderShipmentInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId($orderId);

    /**
     * @param $incrementId
     * @return OrderShipmentInterface
     * @throws NoSuchEntityException
     */
    public function getByIncrementId($incrementId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderShipmentSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param OrderShipmentInterface $entity
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(OrderShipmentInterface $entity);

    /**
     * @param int $entityId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($entityId);

    /**
     * @return void
     */
    public function clear();
}
