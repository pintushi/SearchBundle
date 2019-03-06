<?php

namespace Pintushi\Bundle\SearchBundle\Query\Factory;

use Pintushi\Bundle\DataGridBundle\Grid\GridInterface;
use Pintushi\Bundle\SearchBundle\Engine\Indexer;
use Pintushi\Bundle\SearchBundle\Query\IndexerQuery;

class QueryFactory implements QueryFactoryInterface
{
    /** @var Indexer */
    protected $indexer;

    /**
     * @param Indexer $indexer
     */
    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $config = [])
    {
        return new IndexerQuery(
            $this->indexer,
            $this->indexer->select()
        );
    }
}
