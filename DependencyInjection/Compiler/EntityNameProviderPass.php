<?php

namespace Pintushi\Bundle\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Pintushi\Bundle\SearchBundle\Provider\NameProvider;
use Pintushi\Bundle\SearchBundle\Provider\EntityNameResolver;

class EntityNameProviderPass extends CompilerPassInterface
{
     /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $chainDefinition = $container->getDefinition(EntityNameResolver::class);
        $taggedServiceIds = $container->findTaggedServiceIds('pintushi_entity.name_provider');

        foreach ($taggedServiceIds as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $chainDefinition->addMethodCall(
                    'addProvider',
                    [
                        new Reference($serviceId),
                        $tag['priority']
                    ]
                );
            }
        }
    }
}
