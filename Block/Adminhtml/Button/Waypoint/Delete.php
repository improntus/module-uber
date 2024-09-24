<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Block\Adminhtml\Button\Waypoint;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Delete implements ButtonProviderInterface
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param Registry $registry
     * @param UrlInterface $url
     */
    public function __construct(Registry $registry, UrlInterface $url)
    {
        $this->registry = $registry;
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        // Add Delete Button?
        if ($this->getWaypointId()) {
            $data = [
                'label' => __('Delete Waypoint'),
                'class' => 'delete',
                'on_click' => "deleteConfirm('" . __('Are you sure you want to do this?') . "','{$this->getDeleteUrl()}')",
                'sort_order' => 50,
            ];
        }
        return $data;
    }

    /**
     * @return mixed|null
     */
    private function getWaypoint()
    {
        return $this->registry->registry('waypoint');
    }

    /**
     * @return int|null
     */
    private function getWaypointId()
    {
        $waypoint = $this->getWaypoint();
        return ($waypoint) ? $waypoint->getId() : null;
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->url->getUrl(
            '*/*/delete',
            [
                'waypoint_id' => $this->getWaypointId()
            ]
        );
    }
}
