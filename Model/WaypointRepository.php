<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\WaypointInterface;
use Improntus\Uber\Api\Data\WaypointInterfaceFactory;
use Improntus\Uber\Api\Data\WaypointSearchResultInterfaceFactory;
use Improntus\Uber\Api\WaypointRepositoryInterface;
use Improntus\Uber\Model\ResourceModel\Waypoint as WaypointResourceModel;
use Improntus\Uber\Model\ResourceModel\Waypoint\Collection;
use Improntus\Uber\Model\ResourceModel\Waypoint\CollectionFactory as WaypointCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\AbstractModel;

class WaypointRepository implements WaypointRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var WaypointResourceModel
     */
    protected $resource;

    /**
     * @var WaypointCollectionFactory
     */
    protected $waypointCollectionFactory;

    /**
     * @var WaypointInterfaceFactory
     */
    protected $waypointInterfaceFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var WaypointSearchResultInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @param WaypointResourceModel $resource
     * @param WaypointCollectionFactory $waypointCollectionFactory
     * @param WaypointInterfaceFactory $waypointInterfaceFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param WaypointSearchResultInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        WaypointResourceModel $resource,
        WaypointCollectionFactory $waypointCollectionFactory,
        WaypointInterfaceFactory $waypointInterfaceFactory,
        DataObjectHelper $dataObjectHelper,
        WaypointSearchResultInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->waypointCollectionFactory = $waypointCollectionFactory;
        $this->waypointInterfaceFactory = $waypointInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param WaypointInterface $waypoint
     * @return WaypointInterface|AbstractModel
     * @throws CouldNotSaveException
     */
    public function save(WaypointInterface $waypoint)
    {
        /** @var WaypointInterface|AbstractModel $waypoint */
        try {
            $this->resource->save($waypoint);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Waypoint: %1',
                $exception->getMessage()
            ));
        }
        return $waypoint;
    }

    /**
     * @param $id
     * @return WaypointInterface|AbstractModel|mixed
     * @throws NoSuchEntityException
     */
    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            /** @var WaypointInterface|AbstractModel $waypoint */
            $waypoint = $this->waypointInterfaceFactory->create();
            $this->resource->load($waypoint, $id);
            if (!$waypoint->getId()) {
                throw new NoSuchEntityException(__('Requested Waypoint doesn\'t exist'));
            }
            $this->instances[$id] = $waypoint;
        }
        return $this->instances[$id];
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Improntus\Uber\Api\Data\WaypointSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $collection = $this->waypointCollectionFactory->create();
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
            $collection->addOrder('main_table.' . WaypointInterface::WAYPOINT_ID, SortOrder::SORT_ASC);
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $waypoints = [];
        foreach ($collection as $waypoint) {
            $waypointDataObject = $this->waypointInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $waypointDataObject,
                $waypoint->getData(),
                WaypointInterface::class
            );
            $waypoints[] = $waypointDataObject;
        }
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults->setItems($waypoints);
    }

    /**
     * @param WaypointInterface $waypoint
     * @return bool
     * @throws CouldNotSaveException
     * @throws StateException
     */
    public function delete(WaypointInterface $waypoint): bool
    {
        /** @var WaypointInterface|AbstractModel $waypoint */
        $id = $waypoint->getId();
        try {
            unset($this->instances[$id]);
            $this->resource->delete($waypoint);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            throw new StateException(
                __('Unable to Delete Waypoint %1', $id)
            );
        }
        unset($this->instances[$id]);
        return true;
    }

    /**
     * @param $waypointId
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function deleteById($waypointId)
    {
        $waypoint = $this->get($waypointId);
        return $this->delete($waypoint);
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
