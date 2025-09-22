<?php

namespace Mbsoft\ScholarGraph\Algorithms\Centrality;

use Mbsoft\ScholarGraph\Contracts\CentralityAlgorithmInterface;
use Mbsoft\ScholarGraph\Domain\Graph;

class BetweennessCalculator implements CentralityAlgorithmInterface
{
    public function calculate(Graph $graph): array
    {
        $nodes = array_keys($graph->nodes);
        $betweenness = array_fill_keys($nodes, 0.0);

        // Brandes algorithm for betweenness centrality
        foreach ($nodes as $source) {
            $stack = [];
            $paths = [];
            $distance = [];
            $sigma = array_fill_keys($nodes, 0);
            $delta = array_fill_keys($nodes, 0.0);

            $sigma[$source] = 1;
            $distance[$source] = 0;
            $queue = [$source];

            // BFS
            while (!empty($queue)) {
                $v = array_shift($queue);
                $stack[] = $v;

                foreach ($graph->getSuccessors($v) as $w) {
                    if (!isset($distance[$w])) {
                        $queue[] = $w;
                        $distance[$w] = $distance[$v] + 1;
                    }

                    if ($distance[$w] === $distance[$v] + 1) {
                        $sigma[$w] += $sigma[$v];
                        $paths[$w][] = $v;
                    }
                }
            }

            // Accumulation
            while (!empty($stack)) {
                $w = array_pop($stack);
                if (isset($paths[$w])) {
                    foreach ($paths[$w] as $v) {
                        $delta[$v] += ($sigma[$v] / $sigma[$w]) * (1 + $delta[$w]);
                    }
                }
                if ($w !== $source) {
                    $betweenness[$w] += $delta[$w];
                }
            }
        }

        // Normalize
        $n = count($nodes);
        $normalization = $n > 2 ? 2.0 / (($n - 1) * ($n - 2)) : 1.0;

        foreach ($betweenness as &$value) {
            $value *= $normalization;
        }

        return $betweenness;
    }
}
