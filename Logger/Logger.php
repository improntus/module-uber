<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Logger;

class Logger extends \Monolog\Logger
{
    /**
     * setName
     *
     * @param $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
