<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony\Exception;

class ClientNotFound extends \Exception
{
    public function __construct(string $clientName)
    {
        parent::__construct(sprintf('Client "%s" not found', $clientName));
    }
}
