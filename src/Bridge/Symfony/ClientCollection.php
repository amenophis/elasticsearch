<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony;

use Amenophis\Elasticsearch\Bridge\Symfony\Exception\ClientNotFound;
use Elasticsearch\Client;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
class ClientCollection
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
     * @throws ClientNotFound
     */
    public function get(string $clientName): Client
    {
        if (!$this->serviceProvider->has($clientName)) {
            throw new ClientNotFound($clientName);
        }

        return $this->serviceProvider->get($clientName);
    }

    /**
     * @return \Generator|Client[]
     */
    public function all(): \Generator
    {
        foreach ($this->serviceProvider->getProvidedServices() as $clientName => $clientClass) {
            yield $clientName => $this->serviceProvider->get($clientName);
        }
    }
}
