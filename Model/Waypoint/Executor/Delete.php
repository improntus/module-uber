<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Waypoint\Executor;

use Improntus\Uber\Api\WaypointRepositoryInterface;
use Improntus\Uber\Api\ExecutorInterface;
use Magento\Framework\Exception\LocalizedException;

class Delete implements ExecutorInterface
{
    /**
     * @var WaypointRepositoryInterface
     */
    private $waypointRepository;

    /**
     * @param WaypointRepositoryInterface $waypointRepository
     */
    public function __construct(
        WaypointRepositoryInterface $waypointRepository
    ) {
        $this->waypointRepository = $waypointRepository;
    }

    /**
     * @param int $id
     * @throws LocalizedException
     */
    public function execute($id)
    {
        $this->waypointRepository->deleteById($id);
    }
}
