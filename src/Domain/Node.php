<?php

namespace Mbsoft\ScholarGraph\Domain;

class Node
{
    public function __construct(
        public string $id,
        public string $type,           // work|author|concept
        public array $attributes = [], // title, year, citations, authors, concepts, ...
        public array $metrics = []      // pagerank_score, community_id, ...
    ) {}
}
