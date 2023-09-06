<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;


class MSIUber extends AbstractModel
{
    /**
     * @var string $_eventPrefix
     */
    protected $_eventPrefix = 'improntus_uber_msi_event';

    /**
     * @var string $_eventObject
     */
    protected $_eventObject = 'improntus_uber_msi_object';

    /**
     * @var bool $_isStatusChanged
     */
    protected $_isStatusChanged = false;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Improntus\Uber\Model\ResourceModel\MSIUber');
    }
}
