<?php

/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api;

interface WebhookInterface
{
    /**
     * updateStatus
     *
     * @param mixed $data
     * @param mixed $kind
     * @param mixed $status
     * @param mixed $account_id
     * @param mixed $customer_id
     * @param mixed $delivery_id
     * @return array
     */
    public function updateStatus(mixed $data, mixed $kind, mixed $status, mixed $account_id, mixed $customer_id, mixed $delivery_id): array;
}
