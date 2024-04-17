<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\StoreInterface;
use Improntus\Uber\Api\Data\StoreInterfaceFactory;
use Improntus\Uber\Api\Data\StoreSearchResultInterface;
use Improntus\Uber\Api\Data\StoreSearchResultInterfaceFactory;
use Improntus\Uber\Api\StoreRepositoryInterface;
use Improntus\Uber\Model\ResourceModel\Store as StoreResourceModel;
use Improntus\Uber\Model\ResourceModel\Store\Collection;
use Improntus\Uber\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\AbstractModel;

class StoreRepository implements StoreRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var StoreResourceModel
     */
    protected $resource;

    /**
     * @var StoreCollectionFactory
     */
    protected $storeCollectionFactory;

    /**
     * @var StoreInterfaceFactory
     */
    protected $storeInterfaceFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var StoreSearchResultInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @param StoreResourceModel $resource
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param StoreInterfaceFactory $storeInterfaceFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param StoreSearchResultInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        StoreResourceModel $resource,
        StoreCollectionFactory $storeCollectionFactory,
        StoreInterfaceFactory $storeInterfaceFactory,
        DataObjectHelper $dataObjectHelper,
        StoreSearchResultInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->storeInterfaceFactory = $storeInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param StoreInterface $store
     * @return StoreInterface|AbstractModel
     * @throws CouldNotSaveException
     */
    public function save(StoreInterface $store)
    {
        /** @var StoreInterface|AbstractModel $store */
        try {
            $this->resource->save($store);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Store: %1',
                $exception->getMessage()
            ));
        }
        return $store;
    }

    /**
     * @param $id
     * @return StoreInterface|AbstractModel|mixed
     * @throws NoSuchEntityException
     */
    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            /** @var StoreInterface|AbstractModel $store */
            $store = $this->storeInterfaceFactory->create();
            $this->resource->load($store, $id);
            if (!$store->getId()) {
                throw new NoSuchEntityException(__("Requested Store doesn't exist"));
            }
            $this->instances[$id] = $store;
        }
        return $this->instances[$id];
    }

    /**
     * @param $waypointId
     * @return StoreInterface
     */
    public function getByWaypoint($waypointId)
    {
        $store = $this->storeInterfaceFactory->create();
        $this->resource->load($store, $waypointId, StoreInterface::WAYPOINT_ID);
        if (!$store->getId()) {
            return null;
        }
        return $store;
    }

    /**
     * @param $sourceCode
     * @return StoreInterface
     */
    public function getBySourceCode($sourceCode)
    {
        $store = $this->storeInterfaceFactory->create();
        $this->resource->load($store, $sourceCode, StoreInterface::SOURCE_CODE);
        if (!$store->getId()) {
            return null;
        }
        return $store;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return StoreSearchResultInterface|mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $collection = $this->storeCollectionFactory->create();
        //Add filters from root filter group to the collection
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @param StoreInterface $store
     * @return bool
     * @throws CouldNotSaveException
     * @throws StateException
     */
    public function delete(StoreInterface $store): bool
    {
        /** @var StoreInterface|AbstractModel $store */
        $id = $store->getId();
        try {
            unset($this->instances[$id]);
            $this->resource->delete($store);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            throw new StateException(
                __('Unable to Delete Store %1', $id)
            );
        }
        unset($this->instances[$id]);
        return true;
    }

    /**
     * @param $storeId
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function deleteById($storeId)
    {
        $store = $this->get($storeId);
        return $this->delete($store);
    }
}
