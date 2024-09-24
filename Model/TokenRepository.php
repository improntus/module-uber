<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\TokenInterface;
use Improntus\Uber\Api\Data\TokenInterfaceFactory;
use Improntus\Uber\Api\Data\TokenSearchResultInterface;
use Improntus\Uber\Api\Data\TokenSearchResultInterfaceFactory;
use Improntus\Uber\Api\TokenRepositoryInterface;
use Improntus\Uber\Model\ResourceModel\Token as TokenResourceModel;
use Improntus\Uber\Model\ResourceModel\Token\Collection;
use Improntus\Uber\Model\ResourceModel\Token\CollectionFactory as TokenCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\AbstractModel;

class TokenRepository implements TokenRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var TokenResourceModel
     */
    protected $resource;

    /**
     * @var TokenCollectionFactory
     */
    protected $tokenCollectionFactory;

    /**
     * @var TokenInterfaceFactory
     */
    protected $tokenInterfaceFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var TokenSearchResultInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @param TokenResourceModel $resource
     * @param TokenCollectionFactory $tokenCollectionFactory
     * @param TokenInterfaceFactory $tokenInterfaceFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param TokenSearchResultInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        TokenResourceModel $resource,
        TokenCollectionFactory $tokenCollectionFactory,
        TokenInterfaceFactory $tokenInterfaceFactory,
        DataObjectHelper $dataObjectHelper,
        TokenSearchResultInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->tokenCollectionFactory = $tokenCollectionFactory;
        $this->tokenInterfaceFactory = $tokenInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param TokenInterface $token
     * @return TokenInterface|AbstractModel
     * @throws CouldNotSaveException
     */
    public function save(TokenInterface $token)
    {
        /** @var TokenInterface|AbstractModel $token */
        try {
            $this->resource->save($token);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Token: %1',
                $exception->getMessage()
            ));
        }
        return $token;
    }

    /**
     * @param $id
     * @return TokenInterface|AbstractModel|mixed
     * @throws NoSuchEntityException
     */
    public function get($id)
    {
        if (!isset($this->instances[$id])) {
            /** @var TokenInterface|AbstractModel $token */
            $token = $this->tokenInterfaceFactory->create();
            $this->resource->load($token, $id);
            if (!$token->getId()) {
                throw new NoSuchEntityException(__("The requested Token doesn't exist"));
            }
            $this->instances[$id] = $token;
        }
        return $this->instances[$id];
    }

    /**
     * @param $storeId
     * @return TokenInterface|mixed
     * @throws NoSuchEntityException
     */
    public function getByStore($storeId)
    {
        if (!isset($this->instances[$storeId])) {
            $token = $this->tokenInterfaceFactory->create();
            $this->resource->load($token, $storeId, TokenInterface::STORE_ID);
            if (!$token->getId()) {
                throw new NoSuchEntityException(__("The requested Token doesn't exist"));
            }
            $this->instances[$storeId] = $token;
        }
        return $this->instances[$storeId];
    }

    /**
     * @param $scope
     * @return TokenInterface
     * @throws NoSuchEntityException
     */
    public function getByScope($scope)
    {
        $token = $this->tokenInterfaceFactory->create();
        $this->resource->load($token, $scope, TokenInterface::SCOPE);
        if (!$token->getId()) {
            throw new NoSuchEntityException(__("The requested Token doesn't exist"));
        }
        return $token;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return TokenSearchResultInterface|mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $collection = $this->tokenCollectionFactory->create();
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
            $collection->addOrder('main_table.' . TokenInterface::TOKEN_ID, SortOrder::SORT_ASC);
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $tokens = [];
        foreach ($collection as $token) {
            $tokenDataObject = $this->tokenInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $tokenDataObject,
                $token->getData(),
                TokenInterface::class
            );
            $tokens[] = $tokenDataObject;
        }
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults->setItems($tokens);
    }

    /**
     * @param TokenInterface $token
     * @return bool
     * @throws CouldNotSaveException
     * @throws StateException
     */
    public function delete(TokenInterface $token): bool
    {
        /** @var TokenInterface|AbstractModel $token */
        $id = $token->getId();
        try {
            unset($this->instances[$id]);
            $this->resource->delete($token);
        } catch (ValidatorException $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            throw new StateException(
                __('Unable to Delete Token %1', $id)
            );
        }
        unset($this->instances[$id]);
        return true;
    }

    /**
     * @param $tokenId
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function deleteById($tokenId)
    {
        $token = $this->get($tokenId);
        return $this->delete($token);
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
