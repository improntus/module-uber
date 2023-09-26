<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Controller\Adminhtml\Organization;

use Improntus\Uber\Api\Data\OrganizationInterface;
use Improntus\Uber\Api\Data\OrganizationInterfaceFactory;
use Improntus\Uber\Api\OrganizationRepositoryInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\Uber;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Improntus_Uber::organization_edit';

    /**
     * @var OrganizationInterfaceFactory $organizationFactory
     */
    protected OrganizationInterfaceFactory $organizationFactory;

    /**
     * @var DataObjectProcessor $dataObjectProcessor
     */
    protected DataObjectProcessor $dataObjectProcessor;

    /**
     * @var DataObjectHelper $dataObjectHelper
     */
    protected DataObjectHelper $dataObjectHelper;

    /**
     * @var DataPersistorInterface $dataPersistor
     */
    protected DataPersistorInterface $dataPersistor;

    /**
     * @var Registry $registry
     */
    protected Registry $registry;

    /**
     * @var OrganizationRepositoryInterface $organizationRepository
     */
    protected OrganizationRepositoryInterface $organizationRepository;

    /**
     * @var Uber $uber
     */
    protected Uber $uber;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @param Context $context
     * @param OrganizationInterfaceFactory $organizationFactory
     * @param OrganizationRepositoryInterface $organizationRepository
     * @param DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param DataPersistorInterface $dataPersistor
     * @param Registry $registry
     * @param Uber $uber
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        OrganizationInterfaceFactory $organizationFactory,
        OrganizationRepositoryInterface $organizationRepository,
        DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        DataPersistorInterface $dataPersistor,
        Registry $registry,
        Uber $uber,
        Data $helper
    ) {
        $this->organizationFactory = $organizationFactory;
        $this->organizationRepository = $organizationRepository;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPersistor = $dataPersistor;
        $this->registry = $registry;
        $this->uber = $uber;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var OrganizationInterface $organization */
        $organization = null;
        $postData = $this->getRequest()->getPostValue();
        $data = $postData;
        $id = !empty($data['entity_id']) ? $data['entity_id'] : null;
        $resultRedirect = $this->resultRedirectFactory->create();

        // Create Organization?
        if (empty($data['uber_organization_id'])) {
            try {
                // Create Organization
                $uberResult = $this->uber->createOrganization($data);

                // Set Organization Id
                if (isset($uberResult["organization_id"])) {
                    $data['uber_organization_id'] = $uberResult["organization_id"];
                } else {
                    // ERROR todo
                    $this->messageManager->addErrorMessage(__('There was a problem creating the Organization in UBER'));
                }
            } catch (\Exception $e) {
                $this->helper->log("UBER Create Org: " . $e->getMessage());
            }
        }

        // Save
        try {
            if ($id) {
                $organization = $this->organizationRepository->get((int)$id);
            } else {
                unset($data['entity_id']);
                $organization = $this->organizationFactory->create();
            }
            $this->dataObjectHelper->populateWithArray($organization, $data, OrganizationInterface::class);
            $this->organizationRepository->save($organization);
            $this->messageManager->addSuccessMessage(__('The Organization has been saved.'));
            $this->dataPersistor->clear('uber_organization');
            if ($this->getRequest()->getParam('back')) {
                $resultRedirect->setPath('*/*/edit', ['entity_id' => $organization->getId()]);
            } else {
                $resultRedirect->setPath('*/*');
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('uber_organization', $postData);
            $resultRedirect->setPath('*/*/edit', ['entity_id' => $id]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('There was a problem saving the Organization'));
            $this->dataPersistor->set('improntus\uber_organization', $postData);
            $resultRedirect->setPath('*/*/edit', ['entity_id' => $id]);
        }
        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
