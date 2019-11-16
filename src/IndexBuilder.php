<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch;

use Elasticsearch\Client;

/**
 * This class is ported from jolicode/elastically but without elastica.
 *
 * @see https://github.com/jolicode/elastically/blob/master/src/IndexBuilder.php
 */
class IndexBuilder
{
    public const INDEX_MISSING         = 1;
    public const MAPPING_FRESH         = 2;
    public const MAPPING_NEED_RECREATE = 4;

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function createIndex(Index $index): string
    {
        $realName = sprintf('%s_%s', $index->getAlias(), date('Y-m-d-His'));
        if ($this->client->indices()->exists(['index' => $realName])) {
            throw new \RuntimeException(sprintf('Index "%s" is already created, something is wrong.', $realName));
        }

        $body = [];

        if ($index->getMappings()) {
            $body['mappings'] = $index->getMappings();
        }

        if ($index->getSettings()) {
            $body['settings'] = $index->getSettings();
        }

        $this->client->indices()->create([
            'index' => $realName,
            'body'  => $body,
        ]);

        return $realName;
    }

    // TODO: Use updateMapping instead of recreate index
//    public function updateMapping(Index $index): void
//    {
//        $body = [];
//
//        if ($index->getMappings()) {
//            $body['mappings'] = $index->getMappings();
//        }
//
//        if ($index->getSettings()) {
//            $body['settings'] = $index->getSettings();
//        }
//
//        $this->client->indices()->putMapping([
//            'index' => $index->getAlias(),
//            'body'  => $body,
//        ]);
//    }

    public function getMappingFreshness(Index $index): int
    {
        if (!$this->client->indices()->exists(['index' => $index->getAlias()])) {
            return self::INDEX_MISSING;
        }

        $result          = $this->client->indices()->getMapping(['index' => $index->getAlias()]);
        $existingMapping = array_shift($result)['mappings'];

        $mappings = $index->getMappings() ?? [];

        ArrayHelper::ksort_recursive($existingMapping);
        ArrayHelper::ksort_recursive($mappings);

        return $existingMapping === $mappings
            ? self::MAPPING_FRESH
            : self::MAPPING_NEED_RECREATE;
    }

    public function reindex(Index $index, string $newIndexName): void
    {
        if ($this->client->indices()->exists(['index' => $index->getAlias()])) {
            $this->client->indices()->refresh(['index' => $index->getAlias()]);

            $this->client->reindex([
                'refresh'             => true,
                'wait_for_completion' => true,
                'body'                => [
                    'source' => [
                        'index' => $index->getAlias(),
                    ],
                    'dest' => [
                        'index'        => $newIndexName,
                        'version_type' => 'external',
                    ],
                ],
            ]);
        }
    }

    public function markAsLive(Index $index, string $realName): void
    {
        $this->client->indices()->updateAliases([
            'body' => [
                'actions' => [
                    ['remove' => ['index' => '*', 'alias' => $index->getAlias()]],
                    ['add' => ['index' => $realName, 'alias' => $index->getAlias()]],
                ],
            ],
        ]);
    }

    public function purgeOldIndices(Index $index): void
    {
        $result = $this->client->cluster()->state([
            'filter_path' => 'metadata.indices.*.state,metadata.indices.*.aliases',
        ]);

        $indexes = $result['metadata']['indices'];
        foreach ($indexes as $realIndexName => &$data) {
            if (0 !== strpos($realIndexName, $index->getAlias())) {
                unset($indexes[$realIndexName]);

                continue;
            }
            $date            = \DateTime::createFromFormat('Y-m-d-His', str_replace($index->getAlias().'_', '', $realIndexName));
            $data['date']    = $date;
            $data['is_live'] = \in_array($index->getAlias(), $data['aliases'], true);
        }
        unset($data);

        // Newest first
        uasort($indexes, function ($a, $b) {
            return $a['date'] < $b['date'];
        });

        $afterLiveCounter = 0;
        $livePassed       = false;

        foreach ($indexes as $realIndexName => $indexData) {
            if ($livePassed) {
                ++$afterLiveCounter;
            }

            if ($indexData['is_live']) {
                $livePassed = true;
            }

            if ($livePassed && $afterLiveCounter > 1) {
                // Remove
                $this->client->indices()->delete(['index' => $realIndexName]);
            } elseif ($livePassed && 1 === $afterLiveCounter) {
                // Close
                $this->client->indices()->close(['index' => $realIndexName]);
            }
        }
    }
}
