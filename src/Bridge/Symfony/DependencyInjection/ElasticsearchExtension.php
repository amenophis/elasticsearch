<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony\DependencyInjection;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class ElasticsearchExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        foreach ($mergedConfig['clients'] as $clientName => $clientConfig) {
            $this->loadClient($clientName, $clientConfig['hosts'], $container);
        }
    }

    private function loadClient(string $clientName, array $hosts, ContainerBuilder $container)
    {
        $factory = (new Definition(ClientBuilder::class))
            ->setFactory([ClientBuilder::class, 'create'])
            ->addMethodCall('setHosts', [$hosts])
            ->setPublic(true)
        ;

        $def = (new Definition(Client::class))
            ->setFactory([$factory, 'build'])
            ->setPublic(true)
        ;

        $serviceId = 'amenophis.client.'.$clientName;

        $container->setDefinition($serviceId, $def);
        $container->registerAliasForArgument($serviceId, Client::class, $clientName.'Client');
    }
}
