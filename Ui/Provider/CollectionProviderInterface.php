<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Ui\Provider;

use Improntus\Uber\Model\ResourceModel\AbstractCollection;

interface CollectionProviderInterface
{
    /**
     * @return AbstractCollection
     */
    public function getCollection();
}
