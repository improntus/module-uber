<?php

namespace Improntus\Uber\Api;

use Magento\Framework\Webapi\Exception;

interface WebhookInterface
{
    /**
     * @param string $data
     * @return string
     */
    public function updateStatus($data);
}
