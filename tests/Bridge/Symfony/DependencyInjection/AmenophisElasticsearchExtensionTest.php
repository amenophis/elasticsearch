<?php

declare(strict_types=1);

namespace Tests\Amenophis\Elasticsearch\Bridge\Symfony\DependencyInjection;

use Amenophis\Elasticsearch\Bridge\Symfony\DependencyInjection\AmenophisElasticsearchExtension;
use Amenophis\Elasticsearch\Index;
use Amenophis\Elasticsearch\IndexBuilder;
use Elasticsearch\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Parser;

class AmenophisElasticsearchExtensionTest extends TestCase
{
    public function testClientsConfiguration()
    {
        $extension = new AmenophisElasticsearchExtension();
        $config    = $this->parseYaml($this->getYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $this->assertTrue($container->hasDefinition('amenophis_elasticsearch.client.single'));
        $this->assertTrue($container->hasDefinition('amenophis_elasticsearch.client.single_array'));
        $this->assertTrue($container->hasDefinition('amenophis_elasticsearch.client.multiple'));

        $singleDefinition = $container->getDefinition('amenophis_elasticsearch.client.single');
        $this->assertSame(
            [
                'amenophis_elasticsearch.client' => [
                    [
                        'key' => 'single',
                    ],
                ],
            ],
            $singleDefinition->getTags()
        );

        $singleArrayDefinition = $container->getDefinition('amenophis_elasticsearch.client.single_array');
        $this->assertSame(
            [
                'amenophis_elasticsearch.client' => [
                    [
                        'key' => 'single_array',
                    ],
                ],
            ],
            $singleArrayDefinition->getTags()
        );

        $multipleDefinition = $container->getDefinition('amenophis_elasticsearch.client.multiple');
        $this->assertSame(
            [
                'amenophis_elasticsearch.client' => [
                    [
                        'key' => 'multiple',
                    ],
                ],
            ],
            $multipleDefinition->getTags()
        );

        $this->assertArrayHasKey(Client::class.' $singleClient', $container->getAliases());
        $this->assertArrayHasKey(Client::class.' $singleArrayClient', $container->getAliases());
        $this->assertArrayHasKey(Client::class.' $multipleClient', $container->getAliases());
    }

    public function testIndexConfiguration()
    {
        $extension = new AmenophisElasticsearchExtension();
        $config    = $this->parseYaml($this->getYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $this->assertTrue($container->hasDefinition('amenophis_elasticsearch.index.posts'));
        $this->assertTrue($container->hasDefinition('amenophis_elasticsearch.index.pages'));

        $postsDefinition = $container->getDefinition('amenophis_elasticsearch.index.posts');
        $this->assertSame(
            [
                'amenophis_elasticsearch.index' => [
                    [
                        'key' => 'posts',
                    ],
                ],
            ],
            $postsDefinition->getTags()
        );

        $pagesDefinition = $container->getDefinition('amenophis_elasticsearch.index.pages');
        $this->assertSame(
            [
                'amenophis_elasticsearch.index' => [
                    [
                        'key' => 'pages',
                    ],
                ],
            ],
            $pagesDefinition->getTags()
        );

        $this->assertArrayHasKey(Index::class.' $postsIndex', $container->getAliases());
        $this->assertArrayHasKey(Index::class.' $pagesIndex', $container->getAliases());

        /** @var Index $postsIndex */
        $postsIndex = $container->get('amenophis_elasticsearch.index.posts');
        $this->assertSame($postsIndex->getAlias(), 'posts');
        $this->assertSame($postsIndex->getMappings(), [
            'properties' => [
                'title' => [
                    'type' => 'text',
                ],
            ],
        ]);
        $this->assertSame($postsIndex->getSettings(), [
            'number_of_shards'   => 1,
            'number_of_replicas' => 1,
        ]);

        /** @var Index $pagesIndex */
        $pagesIndex = $container->get('amenophis_elasticsearch.index.pages');
        $this->assertSame($pagesIndex->getAlias(), 'pages');
        $this->assertSame($pagesIndex->getMappings(), [
            'properties' => [
                'content' => [
                    'type' => 'text',
                ],
            ],
        ]);
        $this->assertSame($pagesIndex->getSettings(), [
            'number_of_shards'   => 2,
            'number_of_replicas' => 2,
        ]);
    }

    public function testIndexBuilderConfiguration()
    {
        $extension = new AmenophisElasticsearchExtension();
        $config    = $this->parseYaml($this->getYamlConfig());
        $extension->load([$config], $container = $this->getContainer());

        $this->assertTrue($container->hasDefinition('amenophis_elasticsearch.index_builder.single'));
        $this->assertTrue($container->hasDefinition('amenophis_elasticsearch.index_builder.single_array'));
        $this->assertTrue($container->hasDefinition('amenophis_elasticsearch.index_builder.multiple'));

        $singleDefinition = $container->getDefinition('amenophis_elasticsearch.index_builder.single');
        $this->assertSame(
            [
                'amenophis_elasticsearch.index_builder' => [
                    [
                        'key' => 'single',
                    ],
                ],
            ],
            $singleDefinition->getTags()
        );

        $singleArrayDefinition = $container->getDefinition('amenophis_elasticsearch.index_builder.single_array');
        $this->assertSame(
            [
                'amenophis_elasticsearch.index_builder' => [
                    [
                        'key' => 'single_array',
                    ],
                ],
            ],
            $singleArrayDefinition->getTags()
        );

        $multipleDefinition = $container->getDefinition('amenophis_elasticsearch.index_builder.multiple');
        $this->assertSame(
            [
                'amenophis_elasticsearch.index_builder' => [
                    [
                        'key' => 'multiple',
                    ],
                ],
            ],
            $multipleDefinition->getTags()
        );

        $this->assertArrayHasKey(IndexBuilder::class.' $singleIndexBuilder', $container->getAliases());
        $this->assertArrayHasKey(IndexBuilder::class.' $singleArrayIndexBuilder', $container->getAliases());
        $this->assertArrayHasKey(IndexBuilder::class.' $multipleIndexBuilder', $container->getAliases());
    }

    private function parseYaml($yaml)
    {
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function getYamlConfig()
    {
        return <<<'EOF'
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
indices:
    posts:
        settings:
            number_of_shards: 1
            number_of_replicas: 1
        mappings:
            properties:
                title:
                    type: text
    pages:
        settings:
            number_of_shards: 2
            number_of_replicas: 2
        mappings:
            properties:
                content:
                    type: text
EOF;
    }

    private function getContainer()
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug'       => false,
            'kernel.bundles'     => [],
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir'    => __DIR__.'/../../../elasticsearch/', // src dir
        ]));
    }
}
