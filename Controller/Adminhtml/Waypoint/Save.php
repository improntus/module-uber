<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Controller\Adminhtml\Waypoint;

use Improntus\Uber\Api\WaypointRepositoryInterface;
use Improntus\Uber\Api\Data\WaypointInterface;
use Improntus\Uber\Api\Data\WaypointInterfaceFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;

class Save extends Action
{

    const ADMIN_RESOURCE = 'Improntus_Uber::waypoint_edit';

    /**
     * @var WaypointInterfaceFactory
     */
    protected $waypointFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var WaypointRepositoryInterface
     */
    protected $waypointRepository;

    /**
     * @param Context $context
     * @param WaypointInterfaceFactory $waypointFactory
     * @param WaypointRepositoryInterface $waypointRepository
     * @param DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param DataPersistorInterface $dataPersistor
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        WaypointInterfaceFactory $waypointFactory,
        WaypointRepositoryInterface $waypointRepository,
        DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        DataPersistorInterface $dataPersistor,
        Registry $registry
    ) {
        $this->waypointFactory = $waypointFactory;
        $this->waypointRepository = $waypointRepository;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPersistor = $dataPersistor;
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var WaypointInterface $waypoint */
        $waypoint = null;
        $postData = $this->getRequest()->getPostValue();
        $data = $postData;
        $id = !empty($data['waypoint_id']) ? $data['waypoint_id'] : null;
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            if ($id) {
                $waypoint = $this->waypointRepository->get((int)$id);
            } else {
                unset($data['waypoint_id']);
                $waypoint = $this->waypointFactory->create();
            }
            $this->dataObjectHelper->populateWithArray($waypoint, $data, WaypointInterface::class);
            $this->waypointRepository->save($waypoint);
            $this->messageManager->addSuccessMessage(__('The Waypoint has been saved.'));
            $this->dataPersistor->clear('uber_waypoint');
            if ($this->getRequest()->getParam('back')) {
                $resultRedirect->setPath('*/*/edit', ['waypoint_id' => $waypoint->getId()]);
            } else {
                $resultRedirect->setPath('*/*');
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('uber_waypoint', $postData);
            $resultRedirect->setPath('*/*/edit', ['waypoint_id' => $id]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('There was a problem saving the Waypoint'));
            $this->dataPersistor->set('improntus\uber_waypoint', $postData);
            $resultRedirect->setPath('*/*/edit', ['waypoint_id' => $id]);
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
