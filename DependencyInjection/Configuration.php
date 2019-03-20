<?php

namespace Pintushi\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'pintushi_search';
    const DEFAULT_ENGINE = 'orm';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::ROOT_NODE);

        $entitiesConfigConfiguration = new EntitiesConfigConfiguration();

        $rootNode
            ->children()
                ->scalarNode('cache_provider')->defaultValue('array')->end()
                ->scalarNode('engine')
                    ->cannotBeEmpty()
                    ->defaultValue(self::DEFAULT_ENGINE)
                ->end()
                ->arrayNode('engine_parameters')
                    ->prototype('variable')->end()
                ->end()
                ->booleanNode('log_queries')
                    ->defaultFalse()
                ->end()
                ->scalarNode('item_container_template')
                    ->defaultValue('PintushiSearchBundle:Grid:itemContainer.html.twig')
                ->end()
                ->append($entitiesConfigConfiguration->getEntitiesConfigurationNode(new TreeBuilder()))
            ->end();

        return $treeBuilder;
    }
}
