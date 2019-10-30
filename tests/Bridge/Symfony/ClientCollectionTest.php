<?php

declare(strict_types=1);

namespace Tests\Amenophis\Elasticsearch\Bridge\Symfony;

use Amenophis\Elasticsearch\Bridge\Symfony\ClientCollection;
use Amenophis\Elasticsearch\Bridge\Symfony\Exception\ClientNotFound;
use Elasticsearch\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\ServiceProviderInterface;

class ClientCollectionTest extends TestCase
{
    public function testHas()
    {
        $collection = new ClientCollection($this->getServiceLocator());

        $this->assertTrue($collection->has('single'));
        $this->assertFalse($collection->has('missing'));
    }

    public function testGetReturnExistingService()
    {
        $collection = new ClientCollection($this->getServiceLocator());

        $this->assertInstanceOf(Client::class, $collection->get('single'));
    }

    public function testGetThrowClientNotFoundExceptionOnInvalidClient()
    {
        $collection = new ClientCollection($this->getServiceLocator());

        $this->expectException(ClientNotFound::class);
        $this->expectExceptionMessage('Client "missing" not found');

        $collection->get('missing');
    }

    private function getServiceLocator(): ServiceProviderInterface
    {
        return new ServiceLocator([
            'single' => function () {
                return $this->prophesize(Client::class)->reveal();
            },
        ]);
    }
}
