<?php

namespace Mbsoft\ScholarGraph\Exporters;

use Mbsoft\ScholarGraph\Contracts\ExporterInterface;
use Mbsoft\ScholarGraph\Domain\Graph;
use Mbsoft\ScholarGraph\Domain\Node;

class RealtimeExporter implements ExporterInterface
{
    public function __construct(private string $format = 'cytoscape') {}

    public function export(Graph $graph): array
    {
        $baseExporter = $this->getBaseExporter();
        $data = $baseExporter->export($graph);

        // Add real-time specific metadata
        $data['realtime'] = [
            'timestamp' => microtime(true),
            'update_type' => 'full_refresh',
            'node_updates' => [],
            'edge_updates' => [],
            'removed_elements' => [],
        ];

        return $data;
    }

    public function exportDelta(Graph $oldGraph, Graph $newGraph): array
    {
        $delta = [
            'timestamp' => microtime(true),
            'update_type' => 'delta',
            'added_nodes' => [],
            'added_edges' => [],
            'updated_nodes' => [],
            'updated_edges' => [],
            'removed_nodes' => [],
            'removed_edges' => [],
        ];

        // Calculate differences
        $oldNodeIds = array_keys($oldGraph->nodes);
        $newNodeIds = array_keys($newGraph->nodes);

        $delta['added_nodes'] = array_diff($newNodeIds, $oldNodeIds);
        $delta['removed_nodes'] = array_diff($oldNodeIds, $newNodeIds);

        // Check for updated nodes
        foreach (array_intersect($oldNodeIds, $newNodeIds) as $nodeId) {
            if ($this->nodeChanged($oldGraph->nodes[$nodeId], $newGraph->nodes[$nodeId])) {
                $delta['updated_nodes'][] = $nodeId;
            }
        }

        return $delta;
    }

    private function getBaseExporter(): ExporterInterface
    {
        return match($this->format) {
            'cytoscape' => new CytoscapeJsonExporter(),
            'd3' => new D3JsonExporter(),
            default => new CytoscapeJsonExporter(),
        };
    }

    private function nodeChanged(Node $oldNode, Node $newNode): bool
    {
        return serialize($oldNode->attributes) !== serialize($newNode->attributes) ||
            serialize($oldNode->metrics) !== serialize($newNode->metrics);
    }
}
