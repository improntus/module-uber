<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Organization;

use Improntus\Uber\Model\ResourceModel\AbstractCollection;
use Improntus\Uber\Ui\Provider\CollectionProviderInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Improntus\Uber\Model\ResourceModel\Organization\CollectionFactory;

class CollectionProvider implements CollectionProviderInterface
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter            = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return AbstractCollection|AbstractDb
     * @throws LocalizedException
     */
    public function getCollection()
    {
        return $this->filter->getCollection($this->collectionFactory->create());
    }
}
