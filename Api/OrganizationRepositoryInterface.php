<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Api;

use Improntus\Uber\Api\Data\OrganizationInterface;
use Improntus\Uber\Api\Data\OrganizationSearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface OrganizationRepositoryInterface
{
    /**
     * @param OrganizationInterface $organization
     * @return mixed
     */
    public function save(OrganizationInterface $organization);

    /**
     * @param $id
     * @return OrganizationInterface
     * @throws NoSuchEntityException
     */
    public function get($id);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrganizationSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param OrganizationInterface $organization
     * @return bool
     * @throws LocalizedException
     */
    public function delete(OrganizationInterface $organization);

    /**
     * @param int $id
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($id);

    /**
     * @return void
     */
    public function clear();
}
