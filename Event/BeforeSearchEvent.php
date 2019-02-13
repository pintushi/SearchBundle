<?php

namespace Pintushi\Bundle\SearchBundle\Event;

use Pintushi\Bundle\SearchBundle\Query\Query;
use Symfony\Component\EventDispatcher\Event;

class BeforeSearchEvent extends Event
{
    const EVENT_NAME = "oro_search.before_search";

    /**
     * @var Query
     */
    protected $query;

    /**
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param Query $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }
}
