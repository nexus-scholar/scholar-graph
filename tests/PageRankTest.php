<?php

use Mbsoft\ScholarGraph\Domain\{Graph, Node, Edge};
use Mbsoft\ScholarGraph\Algorithms\Centrality\PageRankCalculator;

it('computes PageRank on a small directed cycle', function () {
    $g = new Graph();
    foreach (['A','B','C'] as $id) {
        $g->addNode(new Node($id,'work', ['label' => $id]));
    }
    $g->addEdge(new Edge('A','B'));
    $g->addEdge(new Edge('B','C'));
    $g->addEdge(new Edge('C','A'));

    $calc = new PageRankCalculator();
    $scores = $calc->calculate($g);

    expect($scores)->toHaveKeys(['A','B','C']);
    $sum = array_sum($scores);
    expect($sum)->toBeGreaterThan(0.99)->toBeLessThan(1.01);
    foreach ($scores as $s) {
        expect($s)->toBeGreaterThan(0);
    }
});
