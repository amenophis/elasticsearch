<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony\DependencyInjection;

use Amenophis\Elasticsearch\Index;
use Amenophis\Elasticsearch\IndexBuilder;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class AmenophisElasticsearchExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');

        foreach ($mergedConfig['clients'] as $clientName => $clientConfig) {
            $this->loadClient($clientName, $clientConfig, $container);
        }

        foreach ($mergedConfig['indices'] as $alias => $indexConfig) {
            $mappings = $indexConfig['mappings'];
            $settings = $indexConfig['settings'];
            $this->loadIndex($alias, $mappings, $settings, $container);
        }
    }

    private function loadClient(string $clientName, array $config, ContainerBuilder $container)
    {
        $clientFactoryDefinition = (new Definition(ClientBuilder::class))
            ->setFactory([ClientBuilder::class, 'create'])
            ->addMethodCall('setHosts', [$config['hosts']])
            ->setPublic(true)
        ;
        $clientDefinition = (new Definition(Client::class))
            ->setFactory([$clientFactoryDefinition, 'build'])
            ->setPublic(true)
            ->addTag('amenophis_elasticsearch.client', ['key' => $clientName])
        ;
        $clientServiceId = $this->getClientServiceId($clientName);
        $container->setDefinition($clientServiceId, $clientDefinition);
        $container->registerAliasForArgument($clientServiceId, Client::class, $clientName.'Client');

        $indexBuilderDefinition = (new Definition(IndexBuilder::class, [new Reference($clientServiceId)]))
            ->addTag('amenophis_elasticsearch.index_builder', ['key' => $clientName])
        ;
        $indexBuilderServiceId = $this->getIndexBuilderServiceId($clientName);
        $container->setDefinition($indexBuilderServiceId, $indexBuilderDefinition);
        $container->registerAliasForArgument($indexBuilderServiceId, IndexBuilder::class, $clientName.'IndexBuilder');
    }

    private function loadIndex(string $alias, ?array $mappings, ?array $settings, ContainerBuilder $container)
    {
        $indexDefinition = new Definition(Index::class, [
            $alias,
            $mappings,
            $settings,
        ]);
        $indexDefinition->addTag('amenophis_elasticsearch.index', ['key' => $alias]);

        $serviceId = $this->getIndexServiceId($alias);
        $container->setDefinition($serviceId, $indexDefinition);
        $container->registerAliasForArgument($serviceId, Index::class, $alias.'Index');
    }

    private function getClientServiceId(string $clientName): string
    {
        return 'amenophis_elasticsearch.client.'.$clientName;
    }

    private function getIndexServiceId(string $indexName): string
    {
        return 'amenophis_elasticsearch.index.'.$indexName;
    }

    private function getIndexBuilderServiceId(string $clientName): string
    {
        return 'amenophis_elasticsearch.index_builder.'.$clientName;
    }
}
