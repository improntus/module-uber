<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model\Token\Executor;

use Improntus\Uber\Api\TokenRepositoryInterface;
use Improntus\Uber\Api\ExecutorInterface;
use Magento\Framework\Exception\LocalizedException;

class Delete implements ExecutorInterface
{
    /**
     * @var TokenRepositoryInterface
     */
    private $tokenRepository;

    /**
     * @param TokenRepositoryInterface $tokenRepository
     */
    public function __construct(
        TokenRepositoryInterface $tokenRepository
    ) {
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * @param int $id
     * @throws LocalizedException
     */
    public function execute($id)
    {
        $this->tokenRepository->deleteById($id);
    }
}
