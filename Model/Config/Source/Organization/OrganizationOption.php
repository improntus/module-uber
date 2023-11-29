<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source\Organization;

use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\OrganizationRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;

class OrganizationOption implements OptionSourceInterface
{
    /**
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var OrganizationRepository $organizationRepository
     */
    protected OrganizationRepository $organizationRepository;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @param Data $helper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrganizationRepository $organizationRepository
     */
    public function __construct(
        Data $helper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrganizationRepository $organizationRepository
    ) {
        $this->helper = $helper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->organizationRepository = $organizationRepository;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        // Init Option
        $organizationOptions[] = ['label' => __('Use Root Organization'), 'value' => 0];

        // Search Criteria
        $searchCriteria = $this->searchCriteriaBuilder->create();

        try {
            // Get Organizations
            $organizationRepository = $this->organizationRepository->getList($searchCriteria)->getItems();

            // Add Options
            foreach ($organizationRepository as $organization) {
                $organizationOptions[] = [
                    'label' => $organization->getOrganizationName(),
                    'value' => $organization->getId(),
                ];
            }
        } catch (LocalizedException $e) {
            $this->helper->log($e->getMessage());
            return $organizationOptions;
        }

        // Return Options
        return $organizationOptions;
    }
}
