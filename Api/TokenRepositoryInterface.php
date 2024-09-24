<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api;

use Improntus\Uber\Api\Data\TokenInterface;
use Improntus\Uber\Api\Data\TokenSearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface TokenRepositoryInterface
{
    /**
     * @param TokenInterface $token
     * @return mixed
     */
    public function save(TokenInterface $token);

    /**
     * @param $id
     * @return TokenInterface
     * @throws NoSuchEntityException
     */
    public function get($id);

    /**
     * @param $storeId
     * @return TokenInterface
     * @throws NoSuchEntityException
     */
    public function getByStore($storeId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return TokenSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param TokenInterface $token
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(TokenInterface $token);

    /**
     * @param int $tokenId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($tokenId);

    /**
     * @return void
     */
    public function clear();
}
