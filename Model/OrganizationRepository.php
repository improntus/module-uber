<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\OrganizationInterface;
use Improntus\Uber\Api\Data\OrganizationInterfaceFactory;
use Improntus\Uber\Api\Data\OrganizationSearchResultInterface;
use Improntus\Uber\Api\Data\OrganizationSearchResultInterfaceFactory;
use Improntus\Uber\Api\OrganizationRepositoryInterface;
use Improntus\Uber\Model\ResourceModel\Organization as OrganizationResourceModel;
use Improntus\Uber\Model\ResourceModel\Organization\Collection;
use Improntus\Uber\Model\ResourceModel\Organization\CollectionFactory as OrganizationCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\AbstractModel;

class OrganizationRepository implements OrganizationRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var OrganizationResourceModel
     */
    protected $resource;

    /**
     * @var OrganizationCollectionFactory
     */
    protected $organizationCollectionFactory;

    /**
     * @var OrganizationInterfaceFactory
     */
    protected $organizationInterfaceFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var OrganizationSearchResultInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @param OrganizationResourceModel $resource
     * @param OrganizationCollectionFactory $organizationCollectionFactory
     * @param OrganizationInterfaceFactory $organizationInterfaceFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param OrganizationSearchResultInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        OrganizationResourceModel $resource,
        OrganizationCollectionFactory $organizationCollectionFactory,
        OrganizationInterfaceFactory $organizationInterfaceFactory,
        DataObjectHelper $dataObjectHelper,
        OrganizationSearchResultInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->organizationCollectionFactory = $organizationCollectionFactory;
        $this->organizationInterfaceFactory = $organizationInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(OrganizationInterface $organization)
    {
        /** @var OrganizationInterface|AbstractModel $organization */
        try {
            $this->resource->save($organization);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Organization: %1',
                $exception->getMessage()
            ));
        }
        return $organization;
    }

    /**
     * @param $id
     * @return OrganizationInterface|AbstractModel|mixed
     * @throws NoSuchEntityException
     */
    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            /** @var OrganizationInterface|AbstractModel $organization */
            $organization = $this->organizationInterfaceFactory->create();
            $this->resource->load($organization, $id);
            if (!$organization->getId()) {
                throw new NoSuchEntityException(__('Requested Organization doesn\'t exist'));
            }
            $this->instances[$id] = $organization;
        }
        return $this->instances[$id];
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrganizationSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $collection = $this->organizationCollectionFactory->create();
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
            $collection->addOrder('main_table.' . OrganizationInterface::ENTITY_ID, SortOrder::SORT_ASC);
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $organizations = [];
        foreach ($collection as $organization) {
            $organizationDataObject = $this->organizationInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $organizationDataObject,
                $organization->getData(),
                OrganizationInterface::class
            );
            $organizations[] = $organizationDataObject;
        }
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults->setItems($organizations);
    }

    /**
     * @param OrganizationInterface $organization
     * @return bool
     * @throws CouldNotSaveException
     * @throws StateException
     */
    public function delete(OrganizationInterface $organization): bool
    {
        /** @var OrganizationInterface|AbstractModel $organization */
        $id = $organization->getId();
        try {
            unset($this->instances[$id]);
            $this->resource->delete($organization);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            throw new StateException(
                __('Unable to Delete Organization %1', $id)
            );
        }
        unset($this->instances[$id]);
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($id)
    {
        $organization = $this->get($id);
        return $this->delete($organization);
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
