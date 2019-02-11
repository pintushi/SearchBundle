<?php

namespace Pintushi\Bundle\SearchBundle\Query\Modifier;

use Pintushi\Bundle\SearchBundle\Query\Query;

interface QueryModifierInterface
{
    /**
     * @param Query $query
     */
    public function modify(Query $query);
}
