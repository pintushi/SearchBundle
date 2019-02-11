<?php

namespace Pintushi\Bundle\SearchBundle\Resolver;

interface EntityTitleResolverInterface
{
    /**
     * Resolve entity title
     *
     * @param  object $entity
     * @return string|null
     */
    public function resolve($entity);
}
