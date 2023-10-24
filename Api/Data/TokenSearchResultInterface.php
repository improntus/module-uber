<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TokenSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return mixed
     */
    public function getItems();

    /**
     * @param array $items
     * @return mixed
     */
    public function setItems(array $items);
}