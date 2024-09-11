<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\OrderShipmentInterface;
use Improntus\Uber\Api\Data\OrderShipmentInterfaceFactory;
use Improntus\Uber\Api\Data\OrderShipmentSearchResultInterface;
use Improntus\Uber\Api\Data\OrderShipmentSearchResultInterfaceFactory;
use Improntus\Uber\Api\OrderShipmentRepositoryInterface;
use Improntus\Uber\Model\ResourceModel\OrderShipment as OrderShipmentResourceModel;
use Improntus\Uber\Model\ResourceModel\OrderShipment\Collection;
use Improntus\Uber\Model\ResourceModel\OrderShipment\CollectionFactory as OrderShipmentCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\AbstractModel;

class OrderShipmentRepository implements OrderShipmentRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var OrderShipmentResourceModel
     */
    protected $resource;

    /**
     * @var OrderShipmentCollectionFactory
     */
    protected $orderShipmentCollectionFactory;

    /**
     * @var OrderShipmentInterfaceFactory
     */
    protected $orderShipmentInterfaceFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var OrderShipmentSearchResultInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @param OrderShipmentResourceModel $resource
     * @param OrderShipmentCollectionFactory $orderShipmentCollectionFactory
     * @param OrderShipmentInterfaceFactory $orderShipmentInterfaceFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param OrderShipmentSearchResultInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        OrderShipmentResourceModel $resource,
        OrderShipmentCollectionFactory $orderShipmentCollectionFactory,
        OrderShipmentInterfaceFactory $orderShipmentInterfaceFactory,
        DataObjectHelper $dataObjectHelper,
        OrderShipmentSearchResultInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->orderShipmentCollectionFactory = $orderShipmentCollectionFactory;
        $this->orderShipmentInterfaceFactory = $orderShipmentInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param OrderShipmentInterface $orderShipment
     * @return OrderShipmentInterface|AbstractModel
     * @throws CouldNotSaveException
     */
    public function save(OrderShipmentInterface $orderShipment)
    {
        /** @var OrderShipmentInterface|AbstractModel $orderShipment */
        try {
            $this->resource->save($orderShipment);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the OrderShipment: %1',
                $exception->getMessage()
            ));
        }
        return $orderShipment;
    }

    /**
     * @param $id
     * @return OrderShipmentInterface|AbstractModel|mixed
     * @throws NoSuchEntityException
     */
    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            /** @var OrderShipmentInterface|AbstractModel $orderShipment */
            $orderShipment = $this->orderShipmentInterfaceFactory->create();
            $this->resource->load($orderShipment, $id);
            if (!$orderShipment->getId()) {
                throw new NoSuchEntityException(__("The requested OrderShipment doesn't exist"));
            }
            $this->instances[$id] = $orderShipment;
        }
        return $this->instances[$id];
    }

    /**
     * @param $orderId
     * @return OrderShipmentInterface|AbstractModel|mixed
     * @throws NoSuchEntityException
     */
    public function getByOrderId($orderId)
    {
        if (!isset($this->instances[$orderId])) {
            /** @var OrderShipmentInterface|AbstractModel $orderShipment */
            $orderShipment = $this->orderShipmentInterfaceFactory->create();
            $this->resource->load($orderShipment, $orderId, OrderShipmentInterface::ORDER_ID);
            if (!$orderShipment->getId()) {
                throw new NoSuchEntityException(__("The requested OrderShipment doesn't exist"));
            }
            $this->instances[$orderId] = $orderShipment;
        }
        return $this->instances[$orderId];
    }

    /**
     * @param $incrementId
     * @return OrderShipmentInterface|AbstractModel|mixed
     * @throws NoSuchEntityException
     */
    public function getByIncrementId($incrementId)
    {
        if (!isset($this->instances[$incrementId])) {
            /** @var OrderShipmentInterface|AbstractModel $orderShipment */
            $orderShipment = $this->orderShipmentInterfaceFactory->create();
            $this->resource->load($orderShipment, $incrementId, OrderShipmentInterface::INCREMENT_ID);
            if (!$orderShipment->getId()) {
                throw new NoSuchEntityException(__("The requested OrderShipment doesn't exist"));
            }
            $this->instances[$incrementId] = $orderShipment;
        }
        return $this->instances[$incrementId];
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderShipmentSearchResultInterface|mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $collection = $this->orderShipmentCollectionFactory->create();
        //Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                $field = $sortOrder->getField();
                $collection->addOrder(
                    $field,
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? SortOrder::SORT_ASC : SortOrder::SORT_DESC
                );
            }
        } else {
            $collection->addOrder('main_table.' . OrderShipmentInterface::ENTITY_ID, SortOrder::SORT_ASC);
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $orderShipments = [];
        foreach ($collection as $orderShipment) {
            $orderShipmentDataObject = $this->orderShipmentInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $orderShipmentDataObject,
                $orderShipment->getData(),
                OrderShipmentInterface::class
            );
            $orderShipments[] = $orderShipmentDataObject;
        }
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults->setItems($orderShipments);
    }

    /**
     * @param OrderShipmentInterface $orderShipment
     * @return bool
     * @throws CouldNotSaveException
     * @throws StateException
     */
    public function delete(OrderShipmentInterface $orderShipment): bool
    {
        /** @var OrderShipmentInterface|AbstractModel $orderShipment */
        $id = $orderShipment->getId();
        try {
            unset($this->instances[$id]);
            $this->resource->delete($orderShipment);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            throw new StateException(
                __('Unable to Delete OrderShipment %1', $id)
            );
        }
        unset($this->instances[$id]);
        return true;
    }

    /**
     * @param $entityId
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function deleteById($entityId)
    {
        $orderShipment = $this->get($entityId);
        return $this->delete($orderShipment);
    }

    /**
     * @param FilterGroup $filterGroup
     * @param Collection $collection
     * @return $this
     */
    protected function addFilterGroupToCollection(
        FilterGroup $filterGroup,
        Collection $collection
    ) {
        $fields = [];
        $conditions = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $fields[] = $filter->getField();
            $conditions[] = [$condition => $filter->getValue()];
        }
        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
        return $this;
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->instances = [];
    }
}
