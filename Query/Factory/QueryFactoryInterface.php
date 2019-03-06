<?php

namespace Pintushi\Bundle\SearchBundle\Query\Factory;

use Pintushi\Bundle\DataGridBundle\Grid\GridInterface;
use Pintushi\Bundle\SearchBundle\Query\SearchQueryInterface;

interface QueryFactoryInterface
{
    /**
     * Creating the Query wrapper object in the given
     * Datasource context.
     *
     * @param array $config
     * @return SearchQueryInterface
     */
    public function create(array $config = []);
}
