<?php

namespace Pintushi\Bundle\SearchBundle\Query\Modifier;

use Doctrine\ORM\QueryBuilder;

interface QueryBuilderModifierInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     */
    public function modify(QueryBuilder $queryBuilder);
}
