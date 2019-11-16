<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('amenophis_elasticsearch');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('clients')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('hosts')
                            ->beforeNormalization()->castToArray()->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() // clients
                ->arrayNode('indices')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('client')->end()
                            ->variableNode('settings')->end()
                            ->variableNode('mappings')->end()
                        ->end()
                    ->end()
                ->end() // indices
            ->end()
        ;

        return $treeBuilder;
    }
}
