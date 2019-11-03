<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony\Exception;

class IndexBuilderNotFound extends \Exception
{
    public function __construct(string $clientName)
    {
        parent::__construct(sprintf('IndexBuilder for client "%s" not found', $clientName));
    }
}
