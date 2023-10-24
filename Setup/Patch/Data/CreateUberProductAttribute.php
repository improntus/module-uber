<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class CreateUberProductAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var EavSetupFactory $eavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->logger = $logger;
    }
    /**
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(Product::ENTITY, 'can_ship_uber', [
            'group' => 'General',
            'type' => 'int',
            'label' => __('Shipping With Uber'),
            'input' => 'boolean',
            'backend' => \Magento\Catalog\Model\Product\Attribute\Backend\Boolean::class,
            'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'default' => true,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'sort_order' => 100
        ]);

        $eavSetup->addAttribute(Product::ENTITY, 'uber_size', [
            'group' => 'General',
            'type' => 'varchar',
            'label' => __('Uber Product Size'),
            'input' => 'select',
            'source' => \Improntus\Uber\Model\Config\Source\Product\SizeOption::class,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'default' => 'small',
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'sort_order' => 110
        ]);

        $eavSetup->addAttribute(Product::ENTITY, 'uber_width', [
            'group' => 'General',
            'type' => 'varchar',
            'label' => __('Uber Product Width'),
            'input' => 'text',
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'default' => 0,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'visible_on_front' => false,
            'used_in_product_listing' => false,
            'sort_order' => 120,
            'note' => __('Expressed in centimeters')
        ]);

        $eavSetup->addAttribute(Product::ENTITY, 'uber_height', [
            'group' => 'General',
            'type' => 'varchar',
            'label' => __('Uber Product Height'),
            'input' => 'text',
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'default' => 0,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'visible_on_front' => false,
            'used_in_product_listing' => false,
            'sort_order' => 130,
            'note' => __('Expressed in centimeters')
        ]);

        $eavSetup->addAttribute(Product::ENTITY, 'uber_depth', [
            'group' => 'General',
            'type' => 'varchar',
            'label' => __('Uber Product Depth'),
            'input' => 'text',
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'default' => 0,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'visible_on_front' => false,
            'used_in_product_listing' => false,
            'sort_order' => 140,
            'note' => __('Expressed in centimeters')
        ]);
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @return void
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Product::ENTITY, 'can_ship_uber');
        $eavSetup->removeAttribute(Product::ENTITY, 'uber_size');
        $eavSetup->removeAttribute(Product::ENTITY, 'uber_width');
        $eavSetup->removeAttribute(Product::ENTITY, 'uber_height');
        $eavSetup->removeAttribute(Product::ENTITY, 'uber_depth');
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}
