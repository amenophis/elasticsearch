<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch;

class Index
{
    private $alias;

    private $mappings;

    private $settings;

    public function __construct(string $alias, ?array $mappings = null, ?array $settings = null)
    {
        $this->alias    = $alias;
        $this->mappings = $mappings;
        $this->settings = $settings;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getMappings(): ?array
    {
        return $this->mappings;
    }

    public function getSettings(): ?array
    {
        return $this->settings;
    }
}
