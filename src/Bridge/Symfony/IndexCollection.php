<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony;

use Amenophis\Elasticsearch\Bridge\Symfony\Exception\IndexNotFound;
use Amenophis\Elasticsearch\Index;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class IndexCollection
{
    private $serviceProvider;

    public function __construct(ServiceProviderInterface $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    public function has(string $indexName): bool
    {
        return $this->serviceProvider->has($indexName);
    }

    /**
     * @throws IndexNotFound
     */
    public function get(string $clientName): Index
    {
        if (!$this->serviceProvider->has($clientName)) {
            throw new IndexNotFound($clientName);
        }

        return $this->serviceProvider->get($clientName);
    }

    /**
     * @return \Generator|Index[]
     */
    public function all(): \Generator
    {
        foreach ($this->serviceProvider->getProvidedServices() as $indexName => $indexClass) {
            yield $indexName => $this->serviceProvider->get($indexName);
        }
    }
}
