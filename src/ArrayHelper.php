<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch;

/**
 * @internal
 */
final class ArrayHelper
{
    private function __construct()
    {
    }

    public static function ksort_recursive(&$array): bool
    {
        if (!\is_array($array)) {
            return false;
        }

        ksort($array);
        foreach ($array as &$arr) {
            self::ksort_recursive($arr);
        }

        return true;
    }
}
