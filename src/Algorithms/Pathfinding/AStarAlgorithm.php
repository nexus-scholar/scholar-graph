<?php

namespace Mbsoft\ScholarGraph\Algorithms\Pathfinding;

use Mbsoft\ScholarGraph\Domain\Graph;

class AStarAlgorithm
{
    public function findPath(Graph $graph, string $sourceId, string $targetId): array
    {
        $openSet = [$sourceId];
        $closedSet = [];
        $gScore = [$sourceId => 0];
        $fScore = [$sourceId => $this->heuristic($sourceId, $targetId)];
        $cameFrom = [];

        while (!empty($openSet)) {
            // Find node with lowest fScore
            $current = $this->getLowestFScore($openSet, $fScore);

            if ($current === $targetId) {
                return $this->reconstructPath($cameFrom, $current);
            }

            $openSet = array_diff($openSet, [$current]);
            $closedSet[] = $current;

            foreach ($graph->getSuccessors($current) as $neighbor) {
                if (in_array($neighbor, $closedSet)) {
                    continue;
                }

                $tentativeGScore = $gScore[$current] + $this->getEdgeWeight($graph, $current, $neighbor);

                if (!in_array($neighbor, $openSet)) {
                    $openSet[] = $neighbor;
                } elseif ($tentativeGScore >= ($gScore[$neighbor] ?? INF)) {
                    continue;
                }

                $cameFrom[$neighbor] = $current;
                $gScore[$neighbor] = $tentativeGScore;
                $fScore[$neighbor] = $gScore[$neighbor] + $this->heuristic($neighbor, $targetId);
            }
        }

        return []; // No path found
    }

    private function heuristic(string $nodeA, string $nodeB): float
    {
        // Simple heuristic - in practice, this could use semantic similarity
        return 1.0;
    }

    private function getLowestFScore(array $openSet, array $fScore): string
    {
        $lowest = INF;
        $lowestNode = null;

        foreach ($openSet as $node) {
            $score = $fScore[$node] ?? INF;
            if ($score < $lowest) {
                $lowest = $score;
                $lowestNode = $node;
            }
        }

        return $lowestNode;
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

    private function reconstructPath(array $cameFrom, string $current): array
    {
        $path = [$current];

        while (isset($cameFrom[$current])) {
            $current = $cameFrom[$current];
            array_unshift($path, $current);
        }

        return $path;
    }
}
