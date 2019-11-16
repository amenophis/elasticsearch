<?php

declare(strict_types=1);

namespace Tests\Amenophis\Elasticsearch\Bridge;

use Amenophis\Elasticsearch\ArrayHelper;
use Amenophis\Elasticsearch\Index;
use Amenophis\Elasticsearch\IndexBuilder;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;

class IndexBuilderTest extends TestCase
{
    /** @var Client */
    private $client;

    /** @var IndexBuilder */
    private $indexBuilder;

    protected function setUp(): void
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['localhost:9200'])
            ->build()
        ;

        $this->indexBuilder = new IndexBuilder($this->client);

        $this->client->indices()->delete(['index' => '*']);
    }

    public function testCreateIndexWithoutMappingOrSettings(): void
    {
        $indexName = $this->indexBuilder->createIndex(new Index('my-index'));
        $this->assertTrue($this->client->indices()->exists(['index' => $indexName]));
    }

    public function testCreateIndexWithMappingAndSettings(): void
    {
        $index = new Index(
            'articles',
            $mappings = [
                'dynamic'    => 'false', // Required to be a string because ES client return boolean as string
                'properties' => [
                    'title' => [
                        'type' => 'text',
                    ],
                    'content' => [
                        'type' => 'text',
                    ],
                ],
            ],
            $settings = [
                'number_of_shards'   => 2,
                'number_of_replicas' => 2,
            ]
        );

        $indexName = $this->indexBuilder->createIndex($index);

        $configuredMapping = $this->client->indices()->getMapping(['index' => $indexName])[$indexName]['mappings'];
        $this->assertSame(
            ArrayHelper::ksort_recursive($mappings),
            ArrayHelper::ksort_recursive($configuredMapping)
        );

        $configuredSettings = $this->client->indices()->getSettings(['index' => $indexName])[$indexName]['settings']['index'];
        $commonConfig       = array_intersect_assoc($settings, $configuredSettings);
        $this->assertSame(
            ArrayHelper::ksort_recursive($settings),
            ArrayHelper::ksort_recursive($commonConfig)
        );
    }

    public function testGetMappingFreshnessIndexMissing(): void
    {
        $index = new Index('articles');

        $this->assertSame(IndexBuilder::INDEX_MISSING, $this->indexBuilder->getMappingFreshness($index));
    }

    /**
     * @dataProvider provideTestGetMappingFreshnessFresh
     */
    public function testGetMappingFreshnessFresh(array $mapping): void
    {
        $index    = new Index('articles', $mapping);
        $realName = $this->indexBuilder->createIndex($index);
        $this->indexBuilder->markAsLive($index, $realName);

        $this->assertSame(IndexBuilder::MAPPING_FRESH, $this->indexBuilder->getMappingFreshness($index));
    }

    public function provideTestGetMappingFreshnessFresh()
    {
        yield 'empty mapping' => [
            [],
        ];
        yield 'not empty mapping' => [
            [
                'properties' => [
                    'title' => [
                        'type' => 'text',
                    ],
                ],
            ],
        ];
    }

    public function testGetMappingFreshnessNeedRecreate(): void
    {
        $index    = new Index('articles');
        $realName = $this->indexBuilder->createIndex($index);

        $this->indexBuilder->markAsLive($index, $realName);

        $index = new Index(
            'articles',
            [
                'properties' => [
                    'title' => [
                        'type' => 'text',
                    ],
                ],
            ]
        );

        $this->assertSame(IndexBuilder::MAPPING_NEED_RECREATE, $this->indexBuilder->getMappingFreshness($index));
    }

    public function testReindex(): void
    {
        $index    = new Index('articles');
        $realName = $this->indexBuilder->createIndex($index);
        $this->indexBuilder->markAsLive($index, $realName);

        $this->client->index([
            'index' => 'articles',
            'id'    => 1,
            'body'  => [
                'title'   => 'My super title',
                'content' => 'My super content',
            ],
        ]);

        $this->client->index([
            'index' => 'articles',
            'id'    => 2,
            'body'  => [
                'title'   => 'My super title 2',
                'content' => 'My super content 2',
            ],
        ]);
        $this->client->indices()->refresh(['index' => 'articles']);

        sleep(1); // Sleeping 1 second to generate a new index name
        $realName2 = $this->indexBuilder->createIndex($index);
        $this->assertSame(0, $this->client->count(['index' => $realName2])['count']);

        $this->indexBuilder->reindex($index, $realName2);
        $this->indexBuilder->markAsLive($index, $realName2);
        $this->assertSame(2, $this->client->count(['index' => $realName2])['count']);
        $this->assertSame(2, $this->client->count(['index' => 'articles'])['count']);
    }

    public function testMarkAsLive(): void
    {
        $index    = new Index('articles');
        $realName = $this->indexBuilder->createIndex($index);
        $this->indexBuilder->markAsLive($index, $realName);

        $this->assertSame(0, $this->client->count(['index' => 'articles'])['count']);

        sleep(1); // Sleeping 1 second to generate a new index name
        $realName2 = $this->indexBuilder->createIndex($index);

        $this->client->index([
            'index' => $realName2,
            'id'    => 1,
            'body'  => [
                'title'   => 'My super title',
                'content' => 'My super content',
            ],
        ]);
        $this->client->indices()->refresh(['index' => $realName2]);
        $this->assertSame(1, $this->client->count(['index' => $realName2])['count']);

        $this->indexBuilder->markAsLive($index, $realName2);
        $this->assertSame(1, $this->client->count(['index' => 'articles'])['count']);
    }

    /**
     * @dataProvider provideTestPurgeOldIndices
     */
    public function testPurgeOldIndices(int $existingIndices, int $expectedOpenedIndices, int $expectedClosedIndices): void
    {
        $index    = new Index('articles');

        for ($i = 1; $i <= $existingIndices; $i++) {
            $realName = $this->indexBuilder->createIndex($index);
            sleep(1); // Sleeping 1 second to generate a new index name
            if ($i === $existingIndices) {
                $this->indexBuilder->markAsLive($index, $realName);
            }
        }

        $this->indexBuilder->purgeOldIndices($index);

        $openedIndices = 0;
        $closedIndices = 0;

        $result = $this->client->cluster()->state([
            'filter_path' => 'metadata.indices.*.state,metadata.indices.*.aliases',
        ]);

        foreach ($result['metadata']['indices'] as $indexName => $index) {
            if ('open' === $index['state']) {
                $openedIndices++;
                $this->assertEquals('articles', $index['aliases'][0]);
            } elseif ('close' === $index['state']) {
                $closedIndices++;
                $this->assertCount(0, $index['aliases']);
            } else {
                $this->fail('Unexpected index status');
                return;
            }
        }

        $this->assertEquals($expectedOpenedIndices, $openedIndices);
        $this->assertEquals($expectedClosedIndices, $closedIndices);
    }

    public function provideTestPurgeOldIndices()
    {
        yield '1 index'   => [1, 1, 0];
        yield '2 indices' => [2, 1, 1];
        yield '3 indices' => [3, 1, 1];
    }
}
