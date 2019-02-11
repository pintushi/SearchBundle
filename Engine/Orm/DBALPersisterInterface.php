<?php

namespace Pintushi\Bundle\SearchBundle\Engine\Orm;

use Pintushi\Bundle\SearchBundle\Entity\AbstractItem;

interface DBALPersisterInterface
{
    /**
     * @param AbstractItem $item
     */
    public function writeItem(AbstractItem $item);

    /**
     * @return void
     */
    public function flushWrites();
}
