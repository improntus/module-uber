<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api;

/**
 * @api
 */
interface ExecutorInterface
{
    /**
     * execute
     * @param int $id
     */
    public function execute($id);
}
