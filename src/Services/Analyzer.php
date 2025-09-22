<?php

namespace Mbsoft\ScholarGraph\Services;

use Mbsoft\ScholarGraph\Contracts\{CentralityAlgorithmInterface, CommunityDetectionInterface};
use Mbsoft\ScholarGraph\Domain\Graph;

class Analyzer
{
    public function __construct(
        private CentralityAlgorithmInterface $centrality,
        private CommunityDetectionInterface $community
    ) {}

    public function applyCentrality(Graph $graph, string $name = 'pagerank'): void
    {
        $scores = $this->centrality->calculate($graph);
        foreach ($scores as $id => $score) {
            if (isset($graph->nodes[$id])) {
                $graph->nodes[$id]->metrics[$name.'_score'] = $score;
            }
        }
        $graph->metrics[$name] = $scores;
    }

    public function applyCommunities(Graph $graph, string $name = 'louvain'): void
    {
        $labels = $this->community->detect($graph);
        foreach ($labels as $id => $cid) {
            if (isset($graph->nodes[$id])) {
                $graph->nodes[$id]->metrics['community_id'] = $cid;
            }
        }
        $graph->metrics[$name] = $labels;
    }
}
