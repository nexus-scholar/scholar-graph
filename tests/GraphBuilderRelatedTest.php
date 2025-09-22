<?php

use Mbsoft\ScholarGraph\Services\{GraphBuilder, Analyzer, DiscoveryService, GraphCache};
use Mbsoft\ScholarGraph\Contracts\{DataSourceInterface, ExporterInterface};
use Mbsoft\ScholarGraph\Algorithms\Centrality\PageRankCalculator;
use Mbsoft\ScholarGraph\Algorithms\Community\LouvainDetector;
use Mbsoft\ScholarGraph\Exporters\CytoscapeJsonExporter;
use Mbsoft\ScholarGraph\Domain\{Graph, Node, Edge};
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;

class FakeDs implements DataSourceInterface {
    public function fetchEntity(string $id, string $type): Node {
        return new Node($id, $type, ['label' => $id]);
    }
    public function fetchReferences(string $workId): array {
        return [ new Edge($workId, 'W2', null, 'citation') ];
    }
    public function fetchCitations(string $workId): array {
        return [ new Edge('W3', $workId, null, 'citation') ];
    }
    public function findSimilar(Graph $graph, string $method, int $limit = 50): array {
        if ($method !== 'related') return ['nodes'=>[], 'edges'=>[]];
        $nodes = [ new Node('W4','work',['label'=>'W4']), new Node('W5','work',['label'=>'W5']) ];
        $edges = [ new Edge('W1','W4', null, 'related'), new Edge('W1','W5', null, 'related') ];
        return ['nodes'=>$nodes, 'edges'=>$edges];
    }
}

it('seeds work with placeholder endpoints and expands related works', function () {
    $cache = new Repository(new ArrayStore());
    $fakeDs = new FakeDs(); // just to ensure class is loaded
    $builder = new GraphBuilder(
        source: $fakeDs,
        analyzer: new Analyzer(new PageRankCalculator(), new LouvainDetector()),
        discovery: new DiscoveryService($fakeDs),
        cache: new GraphCache($cache, 600),
        exporter: new CytoscapeJsonExporter()
    );

    $builder->seed('work','W1')->findSimilar('related', 2);
    $g = $builder->getGraph();
    expect($g)->toBeInstanceOf(Graph::class)
        ->and(count($g->nodes))->toBe(5);
    // W1, W2

    // there should be edges W1->W2 (citation), W3->W1 (citation), W1->W4/W5 (related)
    $edgeSig = fn($e) => $e->source.'>'.$e->target.':'.$e->type;
    $sigs = array_map($edgeSig, $g->edges);
    expect($sigs)->toContain('W1>W2:citation');
    expect($sigs)->toContain('W3>W1:citation');
    expect($sigs)->toContain('W1>W4:related');
    expect($sigs)->toContain('W1>W5:related');
});
