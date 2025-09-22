<?php

namespace Mbsoft\ScholarGraph\Algorithms\Pathfinding;

use Mbsoft\ScholarGraph\Domain\Graph;

class DijkstraAlgorithm
{
    public function findPath(Graph $graph, string $sourceId, string $targetId): array
    {
        $distances = [];
        $previous = [];
        $queue = [];
        $nodes = array_keys($graph->nodes);

        // Initialize distances
        foreach ($nodes as $nodeId) {
            $distances[$nodeId] = $nodeId === $sourceId ? 0 : INF;
            $previous[$nodeId] = null;
            $queue[] = $nodeId;
        }

        while (!empty($queue)) {
            // Find node with minimum distance
            $minDistance = INF;
            $currentNode = null;
            $currentIndex = null;

            foreach ($queue as $index => $nodeId) {
                if ($distances[$nodeId] < $minDistance) {
                    $minDistance = $distances[$nodeId];
                    $currentNode = $nodeId;
                    $currentIndex = $index;
                }
            }

            if ($currentNode === null || $minDistance === INF) {
                break;
            }

            // Remove current node from queue
            array_splice($queue, $currentIndex, 1);

            if ($currentNode === $targetId) {
                break;
            }

            // Update distances to neighbors
            foreach ($graph->getSuccessors($currentNode) as $neighbor) {
                if (!in_array($neighbor, $queue)) {
                    continue;
                }

                $weight = $this->getEdgeWeight($graph, $currentNode, $neighbor);
                $altDistance = $distances[$currentNode] + $weight;

                if ($altDistance < $distances[$neighbor]) {
                    $distances[$neighbor] = $altDistance;
                    $previous[$neighbor] = $currentNode;
                }
            }
        }

        return $this->reconstructPath($previous, $sourceId, $targetId);
    }

    private function getEdgeWeight(Graph $graph, string $source, string $target): float
    {
        foreach ($graph->edges as $edge) {
            if ($edge->source === $source && $edge->target === $target) {
                return $edge->weight ?? 1.0;
            }
        }
        return 1.0;
    }

    private function reconstructPath(array $previous, string $source, string $target): array
    {
        $path = [];
        $current = $target;

        while ($current !== null) {
            array_unshift($path, $current);
            $current = $previous[$current];
        }

        return $path[0] === $source ? $path : [];
    }
}
