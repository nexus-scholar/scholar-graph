<?php

namespace Mbsoft\ScholarGraph\Services;

use Mbsoft\ScholarGraph\Contracts\{DataSourceInterface, ExporterInterface, AsyncProcessorInterface};
use Mbsoft\ScholarGraph\Domain\{Graph, Node, Edge};
use Mbsoft\ScholarGraph\Algorithms\Pathfinding\{DijkstraAlgorithm, AStarAlgorithm};
use Mbsoft\ScholarGraph\Support\Keys;

class GraphBuilder
{
    private Graph $graph;
    private bool $useAsync = false;
    private array $pendingOperations = [];

    public function __construct(
        private DataSourceInterface $source,
        private Analyzer $analyzer,
        private DiscoveryService $discovery,
        private GraphCache $cache,
        private ExporterInterface $exporter,
        private ?AsyncProcessorInterface $asyncProcessor = null
    ) {
        $this->graph = new Graph();
    }

    public function getGraph(): Graph
    {
        return $this->graph;
    }

    // Static factory methods
    public static function fromWork(string $workId): self
    {
        return app(self::class)->seed('work', $workId);
    }

    public static function fromAuthor(string $authorId): self
    {
        return app(self::class)->seed('author', $authorId);
    }

    public static function fromConcept(string $conceptId): self
    {
        return app(self::class)->seed('concept', $conceptId);
    }

    public static function fromMultiple(array $entities): self
    {
        $builder = app(self::class);
        return $builder->seedMultiple($entities);
    }

    public static function temporal(string $entityType, string $entityId): TemporalGraphBuilder
    {
        return new TemporalGraphBuilder($entityType, $entityId, app(\Mbsoft\ScholarGraph\Contracts\TemporalAnalyzerInterface::class));
    }

    // Core seeding methods
    public function seed(string $type, string $id): self
    {
        $key = Keys::seedKey($type, $id);
        $this->graph = $this->cache->remember($key, function () use ($type, $id) {
            $g = new Graph();
            $seed = $this->source->fetchEntity($id, $type);
            $g->addNode($seed);

            // Add initial relationships for works
            if ($type === 'work') {
                foreach ($this->source->fetchReferences($id) as $edge) {
                    $g->addEdge($edge);
                }
                foreach ($this->source->fetchCitations($id) as $edge) {
                    $g->addEdge($edge);
                }
            }
            return $g;
        });
        return $this;
    }

    public function seedMultiple(array $entities): self
    {
        $this->graph = new Graph();
        foreach ($entities as $entity) {
            $node = $this->source->fetchEntity($entity['id'], $entity['type']);
            $this->graph->addNode($node);
        }
        return $this;
    }

    // Async processing
    public function async(bool $useAsync = true): self
    {
        $this->useAsync = $useAsync;
        return $this;
    }

    public function process(): string
    {
        if (!$this->asyncProcessor) {
            throw new \RuntimeException('Async processor not available');
        }

        $seedNodes = array_keys($this->graph->nodes);
        if (empty($seedNodes)) {
            throw new \RuntimeException('No seed nodes to process');
        }

        $options = [
            'operations' => $this->pendingOperations,
            'algorithms' => []
        ];

        return $this->asyncProcessor->buildAsync('bulk', serialize($seedNodes), $options);
    }

    // Discovery methods
    public function findSimilar(string $method = 'cocitation', int $limit = 50): self
    {
        if ($this->useAsync && $this->shouldProcessAsync()) {
            return $this->queueOperation('findSimilar', [$method, $limit]);
        }

        return $this->executeOperation('findSimilar', [$method, $limit]);
    }

    public function expandByAuthors(int $limit = 20): self
    {
        return $this->findSimilar('coauthorship', $limit);
    }

    public function expandByConcepts(int $limit = 30): self
    {
        return $this->findSimilar('concept_similarity', $limit);
    }

    public function expandByInstitutions(int $limit = 15): self
    {
        return $this->findSimilar('institutional_collaboration', $limit);
    }

    // Algorithm methods
    public function calculateCentrality(string $name = 'pagerank'): self
    {
        if ($this->useAsync && $this->shouldProcessAsync()) {
            return $this->queueOperation('calculateCentrality', [$name]);
        }

        return $this->executeOperation('calculateCentrality', [$name]);
    }

    public function detectCommunities(string $name = 'louvain'): self
    {
        if ($this->useAsync && $this->shouldProcessAsync()) {
            return $this->queueOperation('detectCommunities', [$name]);
        }

        return $this->executeOperation('detectCommunities', [$name]);
    }

    public function calculateAllCentralities(): self
    {
        return $this
            ->calculateCentrality() // 'pagerank' is the default value
            ->calculateCentrality('betweenness')
            ->calculateCentrality('closeness')
            ->calculateCentrality('eigenvector');
    }

    // Pathfinding
    public function findPath(string $sourceId, string $targetId, string $algorithm = 'dijkstra'): array
    {
        $pathfinder = match($algorithm) {
            'dijkstra' => app(DijkstraAlgorithm::class),
            'astar' => app(AStarAlgorithm::class),
            default => app(DijkstraAlgorithm::class),
        };

        return $pathfinder->findPath($this->graph, $sourceId, $targetId);
    }

    // Export methods
    public function asCytoscapeJson(): array
    {
        return $this->exporter->export($this->graph);
    }

    public function toArray(string $format = 'cytoscape'): array
    {
        $exporter = $this->getExporter($format);
        return $exporter->export($this->graph);
    }

    public function asD3Json(): array
    {
        return $this->toArray('d3');
    }

    // Job status methods for async processing
    public static function getJobStatus(string $jobId): array
    {
        $processor = app(AsyncProcessorInterface::class);
        return $processor->getStatus($jobId);
    }

    public static function getJobResult(string $jobId): ?Graph
    {
        $processor = app(AsyncProcessorInterface::class);
        return $processor->getResult($jobId);
    }

    // Missing operation methods
    protected function queueOperation(string $operation, array $parameters): self
    {
        $this->pendingOperations[] = [
            'operation' => $operation,
            'parameters' => $parameters
        ];
        return $this;
    }

    protected function executeOperation(string $operation, array $parameters): self
    {
        $key = Keys::algoKey($this->graph, $operation . ':' . serialize($parameters));

        $this->graph = $this->cache->remember($key, function () use ($operation, $parameters) {
            switch ($operation) {
                case 'findSimilar':
                    $this->discovery->findSimilar($this->graph, $parameters[0], $parameters[1]);
                    break;
                case 'calculateCentrality':
                    $this->analyzer->applyCentrality($this->graph, $parameters[0]);
                    break;
                case 'detectCommunities':
                    $this->analyzer->applyCommunities($this->graph, $parameters[0]);
                    break;
            }
            return $this->graph;
        });

        return $this;
    }

    // Helper methods
    private function shouldProcessAsync(): bool
    {
        return count($this->graph->nodes) > config('scholar-graph.performance.max_nodes_sync', 1000);
    }

    private function getExporter(string $format): ExporterInterface
    {
        return app("scholar-graph.exporter.{$format}");
    }

    private function rebuildAdjacencyList(): void
    {
        $this->graph->adjacencyList = [];
        foreach ($this->graph->nodes as $nodeId => $node) {
            $this->graph->adjacencyList[$nodeId] = [];
        }
        foreach ($this->graph->edges as $edge) {
            $this->graph->adjacencyList[$edge->source][] = $edge->target;
        }
    }

    // Analysis methods
    public function getSummaryStatistics(): array
    {
        return [
            'node_count' => count($this->graph->nodes),
            'edge_count' => count($this->graph->edges),
            'density' => $this->calculateDensity(),
            'average_degree' => $this->calculateAverageDegree(),
        ];
    }

    private function calculateDensity(): float
    {
        $n = count($this->graph->nodes);
        $e = count($this->graph->edges);
        return $n > 1 ? (2 * $e) / ($n * ($n - 1)) : 0;
    }

    private function calculateAverageDegree(): float
    {
        $totalDegree = 0;
        foreach ($this->graph->adjacencyList as $neighbors) {
            $totalDegree += count($neighbors);
        }
        return count($this->graph->nodes) > 0 ? $totalDegree / count($this->graph->nodes) : 0;
    }

    public function identifyInfluencers(string $metric = 'pagerank', int $top = 10): array
    {
        if (!isset($this->graph->metrics[$metric])) {
            $this->calculateCentrality($metric);
        }

        return collect($this->graph->metrics[$metric])
            ->sortDesc()
            ->take($top)
            ->keys()
            ->toArray();
    }
}
