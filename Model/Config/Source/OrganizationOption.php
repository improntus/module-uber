<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source;

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
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrganizationRepository $organizationRepository
     * @param Data $helper
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrganizationRepository $organizationRepository,
        Data $helper
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
        $organizationOptions[] = ['label' => __('Use Base Organization'), 'value' => ''];

        // Search Criteria
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('active', true)
                                    ->create();

        try {
            // Get Organizations
            $organizationRepository = $this->organizationRepository->getList($searchCriteria)->getItems();

            // Add Options
            foreach ($organizationRepository as $organization) {
                $organizationOptions[] = [
                    'label' => $organization->getOrganizationName(),
                    'value' => $organization->getEntityId(),
                ];
            }
        } catch (LocalizedException $e) {
            $this->helper->log('Uber Getting organizations option ERROR: %1', $e->getMessage());
        }

        // Return Options
        return $organizationOptions;
    }
}
