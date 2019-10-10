<?php

declare(strict_types=1);

namespace Tests\Amenophis\Elasticsearch\Bridge\Symfony\DependencyInjection;

use Amenophis\Elasticsearch\Bridge\Symfony\DependencyInjection\AmenophisElasticsearchExtension;
use Elasticsearch\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Parser;

class ElasticsearchExtensionTest extends TestCase
{
    public function testEmptyConfigLoad()
    {
        $extension = new AmenophisElasticsearchExtension();
        $config = array();
        $extension->load(array($config), $container = $this->getContainer());
        $this->assertArrayNotHasKey('elasticsearch.clients', $container->getDefinitions());
    }

    public function testClientsConfiguration()
    {
        $extension = new AmenophisElasticsearchExtension();
        $config = $this->parseYaml($this->getYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertTrue($container->hasDefinition('amenophis.client.single'));
        $this->assertTrue($container->hasDefinition('amenophis.client.single_array'));
        $this->assertTrue($container->hasDefinition('amenophis.client.multiple'));

        $singleDefinition = $container->getDefinition('amenophis.client.single');
        $this->assertSame(
            [
                'amenophis.client' => [
                    [
                        'key' => 'single'
                    ]
                ]
            ],
            $singleDefinition->getTags()
        );

        $singleArrayDefinition = $container->getDefinition('amenophis.client.single_array');
        $this->assertSame(
            [
                'amenophis.client' => [
                    [
                        'key' => 'single_array'
                    ]
                ]
            ],
            $singleArrayDefinition->getTags()
        );

        $multipleDefinition = $container->getDefinition('amenophis.client.multiple');
        $this->assertSame(
            [
                'amenophis.client' => [
                    [
                        'key' => 'multiple'
                    ]
                ]
            ],
            $multipleDefinition->getTags()
        );

        $this->assertArrayHasKey(Client::class.' $singleClient', $container->getAliases());
        $this->assertArrayHasKey(Client::class.' $singleArrayClient', $container->getAliases());
        $this->assertArrayHasKey(Client::class.' $multipleClient', $container->getAliases());
    }

    private function parseYaml($yaml)
    {
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function getYamlConfig()
    {
        return <<<EOF
clients:
    single:
        hosts: localhost:9200
    single-array:
        hosts:
            - localhost:9200
    multiple:
        hosts:
            - localhost:9200
            - localhost:9201
EOF;
    }

    private function getContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
            'kernel.bundles'     => array(),
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir'    => __DIR__.'/../../../../' // src dir
        )));
    }
}
