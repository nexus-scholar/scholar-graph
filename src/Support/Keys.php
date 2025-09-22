<?php

namespace Mbsoft\ScholarGraph\Support;

use Mbsoft\ScholarGraph\Domain\Graph;

class Keys
{
    public static function seedKey(string $type, string $id): string
    {
        return "sg:seed:$type:$id";
    }

    public static function expandKey(Graph $g, string $method, int $limit): string
    {
        // Hash node ids to keep key bounded
        $sig = substr(sha1(implode(',', array_keys($g->nodes))), 0, 12);
        return "sg:expand:$method:$limit:$sig";
    }

    public static function algoKey(Graph $g, string $name): string
    {
        $sig = substr(sha1(implode(',', array_keys($g->nodes))), 0, 12);
        return "sg:algo:$name:$sig";
    }
}
