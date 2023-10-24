<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml\Waypoint;

use Improntus\Uber\Api\Data\WaypointInterface;
use Improntus\Uber\Api\WaypointRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Edit extends Action
{

    const ADMIN_RESOURCE = 'Improntus_Uber::waypoint_edit';

    /**
     * @var WaypointRepositoryInterface
     */
    private $waypointRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Context $context
     * @param WaypointRepositoryInterface $waypointRepository
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        WaypointRepositoryInterface $waypointRepository,
        Registry $registry
    ) {
        $this->waypointRepository = $waypointRepository;
        $this->registry = $registry;
        parent::__construct($context);
    }

    /**
     * @return null|WaypointInterface
     */
    private function getWaypoint()
    {
        $waypointId = $this->getRequest()->getParam('waypoint_id');
        try {
            $waypoint = $this->waypointRepository->get($waypointId);
        } catch (NoSuchEntityException $e) {
            $waypoint = null;
        }
        $this->registry->register('waypoint', $waypoint);
        return $waypoint;
    }

    /**
     * @return Page
     */
    public function execute()
    {
        $waypoint = $this->getWaypoint();
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Improntus_Uber::waypoint');
        $resultPage->getConfig()->getTitle()->prepend(__('Waypoints'));
        if ($waypoint === null) {
            $resultPage->getConfig()->getTitle()->prepend(__('New Waypoint'));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__("Edit Waypoint: %1", $waypoint->getName()));
        }
        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
