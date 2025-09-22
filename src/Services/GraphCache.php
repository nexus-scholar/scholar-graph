<?php

namespace Mbsoft\ScholarGraph\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Mbsoft\ScholarGraph\Domain\Graph;

class GraphCache
{
    public function __construct(private CacheRepository $cache, private int $ttl)
    {}

    public function remember(string $key, callable $builder): Graph
    {
        return $this->cache->remember($key, $this->ttl, function () use ($builder) {
            /** @var Graph $graph */
            $graph = $builder();
            // Store as array for portability
            return $graph;
        });
    }
}
