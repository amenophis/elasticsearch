<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony;

use Amenophis\Elasticsearch\Bridge\Symfony\Exception\ClientNotFound;
use Elasticsearch\Client;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ClientCollection
{
    private $clientLocator;

    public function __construct(ServiceLocator $clientLocator)
    {
        $this->clientLocator = $clientLocator;
    }

    public function has(string $clientName): bool
    {
        return $this->clientLocator->has($clientName);
    }

    /**
     * @throws ClientNotFound
     */
    public function get(string $clientName): Client
    {
        if (!$this->clientLocator->has($clientName)) {
            throw new ClientNotFound($clientName);
        }

        return $this->clientLocator->get($clientName);
    }

    /**
     * @return \Generator|Client[]
     */
    public function all(): \Generator
    {
        foreach ($this->clientLocator->getProvidedServices() as $clientName => $clientClass) {
            yield $clientName => $this->clientLocator->get($clientName);
        }
    }
}
