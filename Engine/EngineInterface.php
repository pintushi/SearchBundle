<?php

namespace Pintushi\Bundle\SearchBundle\Engine;

use Pintushi\Bundle\SearchBundle\Query\Query;
use Pintushi\Bundle\SearchBundle\Query\Result;

/**
 * Performs search operation for search index
 */
interface EngineInterface
{
    /**
     * Performs search in index according to passed query
     *
     * @param Query $query
     * @param array $context
     *
     * @return Result
     */
    public function search(Query $query, array $context = []);
}
