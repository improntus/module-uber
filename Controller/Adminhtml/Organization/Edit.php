<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml\Organization;

use Improntus\Uber\Api\Data\OrganizationInterface;
use Improntus\Uber\Api\OrganizationRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Edit extends Action
{
    /**
     * @var OrganizationRepositoryInterface
     */
    private $organizationRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * Edit constructor.
     * @param Context $context
     * @param OrganizationRepositoryInterface $organizationRepository
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        OrganizationRepositoryInterface $organizationRepository,
        Registry $registry
    ) {
        $this->organizationRepository = $organizationRepository;
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * @return null|OrganizationInterface
     */
    private function getOrganization()
    {
        $organizationId = $this->getRequest()->getParam('entity_id');
        try {
            $organization = $this->organizationRepository->get($organizationId);
        } catch (NoSuchEntityException $e) {
            $organization = null;
        }
        $this->registry->register('organization', $organization);
        return $organization;
    }

    /**
     * @return Page
     */
    public function execute()
    {
        $organization = $this->getOrganization();
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Improntus_Uber::organization');
        $resultPage->getConfig()->getTitle()->prepend(__('Organizations'));
        if ($organization === null) {
            $resultPage->getConfig()->getTitle()->prepend(__('New Organization'));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__("Edit Organization: %1", $organization->getOrganizationName()));
        }
        return $resultPage;
    }
}
