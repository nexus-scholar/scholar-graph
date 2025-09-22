<?php

namespace Mbsoft\ScholarGraph\Domain;

class Graph
{
    /** @var array<string,Node> */
    public array $nodes = [];

    /** @var array<string,string[]> adjacency list: nodeId => [neighborId,...] */
    public array $adjacencyList = [];

    /** @var Edge[] */
    public array $edges = [];

    /** @var array<string,array<string,mixed>> metrics registry per metric name */
    public array $metrics = [];

    public function addNode(Node $node): void
    {
        $this->nodes[$node->id] = $node;
        $this->adjacencyList[$node->id] ??= [];
    }

    public function addEdge(Edge $edge): void
    {
        $this->edges[] = $edge;
        $this->adjacencyList[$edge->source] ??= [];
        $this->adjacencyList[$edge->source][] = $edge->target;
        // For directed graphs we do not add reverse neighbor by default
    }

    /** @return string[] */
    public function getSuccessors(string $nodeId): array
    {
        return $this->adjacencyList[$nodeId] ?? [];
    }

    /** @return string[] */
    public function getPredecessors(string $nodeId): array
    {
        $pred = [];
        foreach ($this->adjacencyList as $src => $neighbors) {
            if (in_array($nodeId, $neighbors, true)) {
                $pred[] = $src;
            }
        }
        return $pred;
    }
}
