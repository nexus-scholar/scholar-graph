<?php

namespace Mbsoft\ScholarGraph\Exporters;

use Mbsoft\ScholarGraph\Contracts\ExporterInterface;
use Mbsoft\ScholarGraph\Domain\Graph;
use Mbsoft\ScholarGraph\Domain\Node;

class D3JsonExporter implements ExporterInterface
{
    public function export(Graph $graph): array
    {
        $nodes = [];
        $links = [];

        // Build nodes array for D3.js force-directed layout
        foreach ($graph->nodes as $node) {
            $nodeData = [
                'id' => $node->id,
                'name' => $node->attributes['title'] ?? $node->id,
                'group' => $node->metrics['community_id'] ?? 0,
                'size' => $this->calculateNodeSize($node),
                'year' => $node->attributes['publication_year'] ?? null,
                'citations' => $node->attributes['citation_count'] ?? 0,
            ];

            if (isset($node->metrics['pagerank_score'])) {
                $nodeData['importance'] = $node->metrics['pagerank_score'];
            }

            $nodes[] = $nodeData;
        }

        // Build links array
        foreach ($graph->edges as $edge) {
            $links[] = [
                'source' => $edge->source,
                'target' => $edge->target,
                'value' => $edge->weight ?? 1,
                'type' => $edge->type ?? 'citation',
            ];
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
            'metadata' => [
                'node_count' => count($nodes),
                'edge_count' => count($links),
                'algorithm_results' => $graph->metrics,
                'generated_at' => now()->toISOString(),
            ]
        ];
    }

    private function calculateNodeSize(Node $node): int
    {
        $citations = $node->attributes['citation_count'] ?? 0;
        return max(5, min(50, log($citations + 1) * 8)); // Logarithmic scaling
    }
}
