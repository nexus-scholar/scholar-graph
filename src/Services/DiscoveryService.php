<?php

namespace Mbsoft\ScholarGraph\Services;

use Mbsoft\ScholarGraph\Contracts\DataSourceInterface;
use Mbsoft\ScholarGraph\Domain\{Graph, Node, Edge};

class DiscoveryService
{
    public function __construct(private DataSourceInterface $source) {}

    /** Expand graph by co-citation or bibliographic coupling */
    public function findSimilar(Graph $graph, string $method = 'cocitation', int $limit = 50): void
    {
        $result = $this->source->findSimilar($graph, $method, $limit);
        foreach ($result['nodes'] as $node) {
            $graph->addNode($node);
        }
        foreach ($result['edges'] as $edge) {
            $graph->addEdge($edge);
        }
    }
}
