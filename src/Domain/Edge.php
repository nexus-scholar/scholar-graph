<?php

namespace Mbsoft\ScholarGraph\Domain;

class Edge
{
    public function __construct(
        public string $source,
        public string $target,
        public ?float $weight = null,
        public ?string $type = null // citation|coauthor|concept_link|...
    ) {}
}
