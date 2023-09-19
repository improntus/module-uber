<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
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
        $eavSetup->addAttribute(Product::ENTITY, 'is_uber_can_ship', [
            'group' => 'General',
            'type' => 'int',
            'label' => 'Shipping With Uber',
            'input' => 'boolean',
            'backend' => \Magento\Catalog\Model\Product\Attribute\Backend\Boolean::class,
            'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'default' => 0,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'sort_order' => 100
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
        $eavSetup->removeAttribute(Product::ENTITY, 'is_uber_can_ship');
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
