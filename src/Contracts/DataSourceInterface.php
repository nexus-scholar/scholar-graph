<?php

namespace Mbsoft\ScholarGraph\Contracts;

use Mbsoft\ScholarGraph\Domain\{Node, Edge, Graph};

interface DataSourceInterface
{
    public function fetchEntity(string $id, string $type): Node; // type: work|author|concept

    /** @return Edge[] */
    public function fetchReferences(string $workId): array; // edges from work -> its referenced works

    /** @return Edge[] */
    public function fetchCitations(string $workId): array; // edges from citing works -> work

    /** @return array{nodes: Node[], edges: Edge[]} */
    public function findSimilar(Graph $graph, string $method, int $limit = 50): array; // method: cocitation|bibcoupling
}
