<?php

namespace Mbsoft\ScholarGraph\Domain;

class GraphSnapshot
{
    public function __construct(
        public string $date,
        public Graph $graph,
        public array $metadata = []
    ) {}

    public function getNodeCount(): int
    {
        return count($this->graph->nodes);
    }

    public function getEdgeCount(): int
    {
        return count($this->graph->edges);
    }

    public function getDensity(): float
    {
        $n = $this->getNodeCount();
        $e = $this->getEdgeCount();
        return $n > 1 ? (2 * $e) / ($n * ($n - 1)) : 0;
    }
}
