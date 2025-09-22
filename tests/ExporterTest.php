<?php

use Mbsoft\ScholarGraph\Domain\{Graph, Node, Edge};
use Mbsoft\ScholarGraph\Exporters\CytoscapeJsonExporter;

it('exports Cytoscape.js compatible JSON', function () {
    $g = new Graph();
    $g->addNode(new Node('X','work',['label' => 'X', 'year' => 2024], ['pagerank_score' => 0.5]));
    $g->addNode(new Node('Y','work',['label' => 'Y']));
    $g->addEdge(new Edge('X','Y', null, 'citation'));

    $exp = new CytoscapeJsonExporter();
    $json = $exp->export($g);

    expect($json)->toHaveKey('elements');
    expect($json['elements'])->toHaveKeys(['nodes','edges']);
    expect($json['elements']['nodes'][0]['data'])->toHaveKeys(['id','type','label']);
    expect($json['elements']['edges'][0]['data'])->toHaveKeys(['source','target','type']);
});
