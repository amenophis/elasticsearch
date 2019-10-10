<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony\DependencyInjection;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class AmenophisElasticsearchExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        foreach ($mergedConfig['clients'] as $clientName => $clientConfig) {
            $this->loadClient($clientName, $clientConfig, $container);
        }

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');
    }

    private function loadClient(string $clientName, array $config, ContainerBuilder $container)
    {
        $factory = (new Definition(ClientBuilder::class))
            ->setFactory([ClientBuilder::class, 'create'])
            ->addMethodCall('setHosts', [$config['hosts']])
            ->setPublic(true)
        ;

        $def = (new Definition(Client::class))
            ->setFactory([$factory, 'build'])
            ->setPublic(true)
            ->addTag('amenophis.client', ['key' => $clientName])
        ;

        $serviceId = 'amenophis.client.'.$clientName;

        $container->setDefinition($serviceId, $def);
        $container->registerAliasForArgument($serviceId, Client::class, $clientName.'Client');
    }
}
