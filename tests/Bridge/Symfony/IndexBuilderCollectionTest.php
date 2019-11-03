<?php

declare(strict_types=1);

namespace Tests\Amenophis\Elasticsearch\Bridge\Symfony;

use Amenophis\Elasticsearch\Bridge\Symfony\Exception\IndexBuilderNotFound;
use Amenophis\Elasticsearch\Bridge\Symfony\IndexBuilderCollection;
use Amenophis\Elasticsearch\IndexBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\ServiceProviderInterface;

class IndexBuilderCollectionTest extends TestCase
{
    public function testHas()
    {
        $collection = new IndexBuilderCollection($this->getServiceLocator());

        $this->assertTrue($collection->has('single'));
        $this->assertFalse($collection->has('missing'));
    }

    public function testGetReturnExistingService()
    {
        $collection = new IndexBuilderCollection($this->getServiceLocator());

        $this->assertInstanceOf(IndexBuilder::class, $collection->get('single'));
    }

    public function testGetThrowClientNotFoundExceptionOnInvalidClient()
    {
        $collection = new IndexBuilderCollection($this->getServiceLocator());

        $this->expectException(IndexBuilderNotFound::class);
        $this->expectExceptionMessage('IndexBuilder for client "missing" not found');

        $collection->get('missing');
    }

    private function getServiceLocator(): ServiceProviderInterface
    {
        return new ServiceLocator([
            'single' => function () {
                return $this->prophesize(IndexBuilder::class)->reveal();
            },
        ]);
    }
}
