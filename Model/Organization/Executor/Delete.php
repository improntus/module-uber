<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Organization\Executor;

use Improntus\Uber\Api\OrganizationRepositoryInterface;
use Improntus\Uber\Api\ExecutorInterface;
use Magento\Framework\Exception\LocalizedException;

class Delete implements ExecutorInterface
{
    /**
     * @var OrganizationRepositoryInterface
     */
    private $organizationRepository;

    /**
     * @param OrganizationRepositoryInterface $organizationRepository
     */
    public function __construct(
        OrganizationRepositoryInterface $organizationRepository
    ) {
        $this->organizationRepository = $organizationRepository;
    }

    /**
     * @param int $id
     * @throws LocalizedException
     */
    public function execute($id)
    {
        $this->organizationRepository->deleteById($id);
    }
}
