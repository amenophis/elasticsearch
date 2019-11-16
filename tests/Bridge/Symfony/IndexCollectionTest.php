<?php

declare(strict_types=1);

namespace Tests\Amenophis\Elasticsearch\Bridge\Symfony;

use Amenophis\Elasticsearch\Bridge\Symfony\Exception\IndexNotFound;
use Amenophis\Elasticsearch\Bridge\Symfony\IndexCollection;
use Amenophis\Elasticsearch\Index;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\ServiceProviderInterface;

class IndexCollectionTest extends TestCase
{
    public function testHas()
    {
        $collection = new IndexCollection($this->getServiceLocator());

        $this->assertTrue($collection->has('posts'));
        $this->assertFalse($collection->has('missing'));
    }

    public function testGetReturnExistingService()
    {
        $collection = new IndexCollection($this->getServiceLocator());

        $this->assertInstanceOf(Index::class, $collection->get('posts'));
    }

    public function testGetThrowClientNotFoundExceptionOnInvalidClient()
    {
        $collection = new IndexCollection($this->getServiceLocator());

        $this->expectException(IndexNotFound::class);
        $this->expectExceptionMessage('Index "missing" not found');

        $collection->get('missing');
    }

    private function getServiceLocator(): ServiceProviderInterface
    {
        return new ServiceLocator([
            'posts' => function () {
                return $this->prophesize(Index::class)->reveal();
            },
        ]);
    }
}
