<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source\Carrier;

use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Framework\Option\ArrayInterface;

class CatalogProductOption implements ArrayInterface
{
    /**
     * @var AttributeFactory
     */
    protected $_attributeFactory;

    /**
     * @var TypeFactory
     */
    protected $_eavTypeFactory;

    /**
     * @param AttributeFactory $attributeFactory
     * @param TypeFactory $typeFactory
     */
    public function __construct(
        AttributeFactory $attributeFactory,
        TypeFactory $typeFactory
    ) {
        $this->_attributeFactory    = $attributeFactory;
        $this->_eavTypeFactory      = $typeFactory;
    }

    /**
     * @return array
     * TODO: REFACTOR - COPY PASTE FROM PEYA
     */
    public function toOptionArray()
    {
        $options = ['label' => __('-- Select --'), 'value' => ''];

        $entityType = $this->_eavTypeFactory->create()->loadByCode('catalog_product');
        $collection = $this->_attributeFactory->create()
            ->getCollection()
            ->addFieldToFilter('entity_type_id', $entityType->getId())
            ->setOrder('attribute_code', 'ASC');

        foreach ($collection as $attribute) {
            if ($attribute->getFrontendLabel() != null) {
                $options[] = ['value' => $attribute->getAttributeCode(), 'label' => __($attribute->getAttributeCode())];
            }
        }
        return $options;
    }
}
