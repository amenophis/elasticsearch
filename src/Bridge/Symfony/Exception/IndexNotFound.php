<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony\Exception;

class IndexNotFound extends \Exception
{
    public function __construct(string $indexName)
    {
        parent::__construct(sprintf('Index "%s" not found', $indexName));
    }
}
