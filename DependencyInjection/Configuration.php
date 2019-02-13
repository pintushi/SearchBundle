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
                    ->defaultValue('PintushiSearchBundle:Datagrid:itemContainer.html.twig')
                ->end()
                ->append($entitiesConfigConfiguration->getEntitiesConfigurationNode(new TreeBuilder()))
                ->arrayNode('entity_name_formats')
                    ->info('Formats of entity text representation')
                    ->example(
                        [
                            'long' => [
                                'fallback' => 'short'
                            ],
                            'short' => null,
                            'html' => [
                                'fallback'  => 'long',
                                'decorator' => true
                            ]
                        ]
                    )
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('fallback')->defaultValue(null)->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->always(
                            function ($v) {
                                $known        = array_fill_keys(array_keys($v), true);
                                $dependencies = [];
                                foreach ($v as $name => $item) {
                                    if (empty($item['fallback'])) {
                                        continue;
                                    }
                                    $fallback = $item['fallback'];
                                    if (!isset($known[$fallback])) {
                                        throw new InvalidConfigurationException(
                                            sprintf(
                                                'The undefined text representation format "%s" cannot be used as '
                                                . 'a fallback format for the format "%s".',
                                                $fallback,
                                                $name
                                            )
                                        );
                                    }
                                    if ($name === $fallback) {
                                        throw new InvalidConfigurationException(
                                            sprintf(
                                                'The text representation format "%s" have '
                                                . 'a cyclic dependency on itself.',
                                                $name
                                            )
                                        );
                                    }
                                    $dependencies[$name] = [$fallback];
                                }
                                $continue = true;
                                while ($continue) {
                                    $continue = false;
                                    foreach ($v as $name => $item) {
                                        if (empty($item['fallback'])) {
                                            continue;
                                        }
                                        $fallback = $item['fallback'];
                                        foreach ($dependencies as $depName => &$depItems) {
                                            if ($depName === $name) {
                                                continue;
                                            }
                                            if (in_array($name, $depItems, true)) {
                                                if (in_array($fallback, $depItems, true)) {
                                                    continue;
                                                }
                                                $depItems[] = $fallback;
                                                if ($fallback === $depName) {
                                                    throw new InvalidConfigurationException(
                                                        sprintf(
                                                            'The text representation format "%s" have '
                                                            . 'a cyclic dependency "%s".',
                                                            $depName,
                                                            implode(' -> ', $depItems)
                                                        )
                                                    );
                                                }
                                                $continue   = true;
                                            }
                                        }
                                    }
                                }

                                return $v;
                            }
                        )
                ->end()
            ->end();

        return $treeBuilder;
    }
}
