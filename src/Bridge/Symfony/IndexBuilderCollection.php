<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony;

use Amenophis\Elasticsearch\Bridge\Symfony\Exception\IndexBuilderNotFound;
use Amenophis\Elasticsearch\IndexBuilder;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class IndexBuilderCollection
{
    private $serviceProvider;

    public function __construct(ServiceProviderInterface $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    public function has(string $clientName): bool
    {
        return $this->serviceProvider->has($clientName);
    }

    /**
     * @throws IndexBuilderNotFound
     */
    public function get(string $clientName): IndexBuilder
    {
        if (!$this->serviceProvider->has($clientName)) {
            throw new IndexBuilderNotFound($clientName);
        }

        return $this->serviceProvider->get($clientName);
    }

    /**
     * @return \Generator|IndexBuilder[]
     */
    public function all(): \Generator
    {
        foreach ($this->serviceProvider->getProvidedServices() as $clientName => $clientClass) {
            yield $clientName => $this->serviceProvider->get($clientName);
        }
    }
}
