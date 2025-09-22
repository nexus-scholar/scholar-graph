<?php

use Mbsoft\ScholarGraph\Domain\{Graph, Node, Edge};

it('builds adjacency and predecessors correctly', function () {
    $g = new Graph();
    $g->addNode(new Node('A','work', ['label' => 'A']));
    $g->addNode(new Node('B','work', ['label' => 'B']));
    $g->addNode(new Node('C','work', ['label' => 'C']));
    $g->addEdge(new Edge('A','B'));
    $g->addEdge(new Edge('B','C'));

    expect($g->getSuccessors('A'))->toBeArray()->toContain('B');
    expect($g->getSuccessors('B'))->toBeArray()->toContain('C');
    expect($g->getSuccessors('C'))->toBeArray()->toBe([]);

    expect($g->getPredecessors('B'))->toBeArray()->toContain('A');
    expect($g->getPredecessors('C'))->toBeArray()->toContain('B');
});
