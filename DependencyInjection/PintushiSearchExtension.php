<?php

namespace Pintushi\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class PintushiSearchExtension extends Extension implements PrependExtensionInterface
{
    const SEARCH_FILE_ROOT_NODE = 'search';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {

        // load entity search configuration from search.yml files
        $ymlLoader = new YamlCumulativeFileLoader('Resources/config/app/search.yml');
        $configurationLoader = new CumulativeConfigLoader('pintushi_search', $ymlLoader);
        $engineResources = $configurationLoader->load($container);

        $entitiesConfigPart = [];
        foreach ($engineResources as $resource) {
            $entitiesConfigPart[] = $resource->data[self::SEARCH_FILE_ROOT_NODE];
        }

        // Process and merge configuration for entities_config section
        $processedEntitiesConfig = $this->processConfiguration(new EntitiesConfigConfiguration(), $entitiesConfigPart);

        $configs = $this->mergeConfigs($configs, $processedEntitiesConfig);

        // parse and validate configuration
        $config = $this->processConfiguration(new Configuration(), $configs);

        // set configuration parameters to container
        $container->setParameter('pintushi_search.engine', $config['engine']);
        $container->setParameter('pintushi_search.engine_parameters', $config['engine_parameters']);
        $container->setParameter('pintushi_search.log_queries', $config['log_queries']);
        $this->setEntitiesConfigParameter($container, $config[EntitiesConfigConfiguration::ROOT_NODE]);
        $container->setParameter('pintushi_search.twig.item_container_template', $config['item_container_template']);


        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $ymlLoader = new YamlCumulativeFileLoader('Resources/config/app/search_engine/' . $config['engine'] . '.yml');
        $engineLoader = new CumulativeConfigLoader('pintushi_search', $ymlLoader);
        $engineResources = $engineLoader->load($container);

        foreach ($engineResources as $engineResource) {
            $loader->load($engineResource->path);
        }

        $container->setParameter('pintushi_search.entity_name_formats', $config['entity_name_formats']);
        $container->setParameter('pintushi_search.entity_name_format.default', 'full');
    }

     /**
     * @param array $configs
     * @param array $processedEntitiesConfig
     * @return array
     */
    protected function mergeConfigs(array $configs, array $processedEntitiesConfig)
    {
        // replace configuration from bundles by configuration from mail config file
        if (isset($configs[Configuration::ROOT_NODE][EntitiesConfigConfiguration::ROOT_NODE])) {
            $configs[Configuration::ROOT_NODE][EntitiesConfigConfiguration::ROOT_NODE] = array_merge(
                $processedEntitiesConfig,
                $configs[Configuration::ROOT_NODE][EntitiesConfigConfiguration::ROOT_NODE]
            );
        } else {
            $configs[Configuration::ROOT_NODE][EntitiesConfigConfiguration::ROOT_NODE] = $processedEntitiesConfig;
        }

        return $configs;
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     * @deprecated since 1.9, will be removed after 1.11
     * Please use pintushi_search.provider.search_mapping service for mapping config
     */
    protected function setEntitiesConfigParameter(ContainerBuilder $container, array $config)
    {
        $container->setParameter('pintushi_search.entities_config', $config);
    }

     /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('doctrine_cache')) {
            throw new \RuntimeException(sprintf('DoctrineCacheBundle is required, install it with `composer require "doctrine/doctrine-cache-bundle"`'));
        }

        $configs = $container->getExtensionConfig('pintushi_search');
        $cacheProvider = $configs[0]['cache_provider'];

        $container->prependExtensionConfig('doctrine_cache', [
            'providers'=> [
                'search_mapping_configuration' => [
                    'type' => $cacheProvider,
                    'namespace' => 'pintushi_search_mapping_configuration',
                ],
            ]
        ]);
    }
}
