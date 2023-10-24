<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Plugin;

use Magento\InventoryApi\Api\Data\SourceExtensionFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class SourceRepositoryPlugin
{
    const ORGANIZATION_ID = 'organization_id';

    const MONDAY_OPEN = 'monday_open';

    const MONDAY_CLOSE = 'monday_close';

    const TUESDAY_OPEN = 'tuesday_open';

    const TUESDAY_CLOSE = 'tuesday_close';

    const WEDNESDAY_OPEN = 'wednesday_open';

    const WEDNESDAY_CLOSE = 'wednesday_close';

    const THURSDAY_OPEN = 'thursday_open';

    const THURSDAY_CLOSE = 'thursday_close';

    const FRIDAY_OPEN = 'friday_open';

    const FRIDAY_CLOSE = 'friday_close';

    const SATURDAY_OPEN = 'saturday_open';

    const SATURDAY_CLOSE = 'saturday_close';

    const SUNDAY_OPEN = 'sunday_open';

    const SUNDAY_CLOSE = 'sunday_close';

    /**
     * @var SourceExtensionFactory $extensionFactory
     */
    protected $extensionFactory;

    /**
     * @var $sourceFactory
     */
    protected $sourceFactory;

    /**
     * @param SourceExtensionFactory $extensionFactory
     */
    public function __construct(
        SourceExtensionFactory $extensionFactory
    ) {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * @param SourceRepositoryInterface $subject
     * @param SourceInterface $source
     * @return SourceInterface
     */
    public function afterGet(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ) {
        // Get Data
        $organizationId = $source->getData(self::ORGANIZATION_ID);
        $mondayOpen = $source->getData(self::MONDAY_OPEN);
        $mondayClose = $source->getData(self::MONDAY_CLOSE);
        $tuesdayOpen = $source->getData(self::TUESDAY_OPEN);
        $tuesdayClose = $source->getData(self::TUESDAY_CLOSE);
        $wednesdayOpen = $source->getData(self::WEDNESDAY_OPEN);
        $wednesdayClose = $source->getData(self::WEDNESDAY_CLOSE);
        $thursdayOpen = $source->getData(self::THURSDAY_OPEN);
        $thursdayClose = $source->getData(self::THURSDAY_CLOSE);
        $fridayOpen = $source->getData(self::FRIDAY_OPEN);
        $fridayClose = $source->getData(self::FRIDAY_CLOSE);
        $saturdayOpen = $source->getData(self::SATURDAY_OPEN);
        $saturdayClose = $source->getData(self::SATURDAY_CLOSE);
        $sundayOpen = $source->getData(self::SUNDAY_OPEN);
        $sundayClose = $source->getData(self::SUNDAY_CLOSE);

        // Get Extension Attributes
        $extensionAttributes = $source->getExtensionAttributes();
        $extensionAttributes = $extensionAttributes ?: $this->extensionFactory->create();

        // Set Data
        $extensionAttributes->setOrganizationId($organizationId);
        $extensionAttributes->setMondayOpen($mondayOpen);
        $extensionAttributes->setMondayClose($mondayClose);
        $extensionAttributes->setTuesdayOpen($tuesdayOpen);
        $extensionAttributes->setTuesdayClose($tuesdayClose);
        $extensionAttributes->setWednesdayOpen($wednesdayOpen);
        $extensionAttributes->setWednesdayClose($wednesdayClose);
        $extensionAttributes->setThursdayOpen($thursdayOpen);
        $extensionAttributes->setThursdayClose($thursdayClose);
        $extensionAttributes->setFridayOpen($fridayOpen);
        $extensionAttributes->setFridayClose($fridayClose);
        $extensionAttributes->setSaturdayOpen($saturdayOpen);
        $extensionAttributes->setSaturdayClose($saturdayClose);
        $extensionAttributes->setSundayOpen($sundayOpen);
        $extensionAttributes->setSundayClose($sundayClose);

        // Set Attributes
        $source->setExtensionAttributes($extensionAttributes);
        return $source;
    }

    /**
     * @param SourceRepositoryInterface $subject
     * @param SourceSearchResultsInterface $result
     * @return SourceSearchResultsInterface
     */
    public function afterGetList(
        SourceRepositoryInterface $subject,
        SourceSearchResultsInterface $result
    ) {
        $products = [];
        $sources = $result->getItems();
        foreach ($sources as $source) {
            // Get Data
            $organizationId = $source->getData(self::ORGANIZATION_ID);
            $mondayOpen = $source->getData(self::MONDAY_OPEN);
            $mondayClose = $source->getData(self::MONDAY_CLOSE);
            $tuesdayOpen = $source->getData(self::TUESDAY_OPEN);
            $tuesdayClose = $source->getData(self::TUESDAY_CLOSE);
            $wednesdayOpen = $source->getData(self::WEDNESDAY_OPEN);
            $wednesdayClose = $source->getData(self::WEDNESDAY_CLOSE);
            $thursdayOpen = $source->getData(self::THURSDAY_OPEN);
            $thursdayClose = $source->getData(self::THURSDAY_CLOSE);
            $fridayOpen = $source->getData(self::FRIDAY_OPEN);
            $fridayClose = $source->getData(self::FRIDAY_CLOSE);
            $saturdayOpen = $source->getData(self::SATURDAY_OPEN);
            $saturdayClose = $source->getData(self::SATURDAY_CLOSE);
            $sundayOpen = $source->getData(self::SUNDAY_OPEN);
            $sundayClose = $source->getData(self::SUNDAY_CLOSE);

            // Get Extension Attributes
            $extensionAttributes = $source->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ?: $this->extensionFactory->create();

            // Set Data
            $extensionAttributes->setOrganizationId($organizationId);
            $extensionAttributes->setMondayOpen($mondayOpen);
            $extensionAttributes->setMondayClose($mondayClose);
            $extensionAttributes->setTuesdayOpen($tuesdayOpen);
            $extensionAttributes->setTuesdayClose($tuesdayClose);
            $extensionAttributes->setWednesdayOpen($wednesdayOpen);
            $extensionAttributes->setWednesdayClose($wednesdayClose);
            $extensionAttributes->setThursdayOpen($thursdayOpen);
            $extensionAttributes->setThursdayClose($thursdayClose);
            $extensionAttributes->setFridayOpen($fridayOpen);
            $extensionAttributes->setFridayClose($fridayClose);
            $extensionAttributes->setSaturdayOpen($saturdayOpen);
            $extensionAttributes->setSaturdayClose($saturdayClose);
            $extensionAttributes->setSundayOpen($sundayOpen);
            $extensionAttributes->setSundayClose($sundayClose);

            // Set Attributes
            $source->setExtensionAttributes($extensionAttributes);
            $products[] = $source;
        }

        $result->setItems($products);
        return $result;
    }

    public function beforeSave(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ) {
        $extensionAttributes = $source->getExtensionAttributes() ?: $this->extensionFactory->create();
        if ($extensionAttributes !== null) {
            // Set Open / Close
            if (!is_null($extensionAttributes->getMondayOpen())) {
                $source->setMondayOpen($extensionAttributes->getMondayOpen());
            }
            if (!is_null($extensionAttributes->getMondayClose())) {
                $source->setMondayClose($extensionAttributes->getMondayClose());
            }

            // Tuesday
            if (!is_null($extensionAttributes->getTuesdayOpen())) {
                $source->setTuesdayOpen($extensionAttributes->getTuesdayOpen());
            }
            if (!is_null($extensionAttributes->getTuesdayClose())) {
                $source->setTuesdayClose($extensionAttributes->getTuesdayClose());
            }

            // Wednesday
            if (!is_null($extensionAttributes->getWednesdayOpen())) {
                $source->setWednesdayOpen($extensionAttributes->getWednesdayOpen());
            }
            if (!is_null($extensionAttributes->getWednesdayClose())) {
                $source->setWednesdayClose($extensionAttributes->getWednesdayClose());
            }

            // Thursday
            if (!is_null($extensionAttributes->getThursdayOpen())) {
                $source->setThursdayOpen($extensionAttributes->getThursdayOpen());
            }
            if (!is_null($extensionAttributes->getThursdayClose())) {
                $source->setThursdayClose($extensionAttributes->getThursdayClose());
            }

            // Friday
            if (!is_null($extensionAttributes->getFridayOpen())) {
                $source->setFridayOpen($extensionAttributes->getFridayOpen());
            }
            if (!is_null($extensionAttributes->getFridayClose())) {
                $source->setFridayClose($extensionAttributes->getFridayClose());
            }

            // Saturday
            if (!is_null($extensionAttributes->getSaturdayOpen())) {
                $source->setSaturdayOpen($extensionAttributes->getSaturdayOpen());
            }
            if (!is_null($extensionAttributes->getSaturdayClose())) {
                $source->setSaturdayClose($extensionAttributes->getSaturdayClose());
            }

            // Sunday
            if (!is_null($extensionAttributes->getSundayOpen())) {
                $source->setSundayOpen($extensionAttributes->getSundayOpen());
            }
            if (!is_null($extensionAttributes->getSundayClose())) {
                $source->setSundayClose($extensionAttributes->getSundayClose());
            }

            // Set Organization
            if (!is_null($extensionAttributes->getOrganizationId())) {
                $source->setOrganizationId($extensionAttributes->getOrganizationId());
            }
        }

        return [$source];
    }
}
