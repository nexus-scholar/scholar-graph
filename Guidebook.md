# Scholar Graph — Developer Guidebook

_A practical integration guide for Laravel devs (based on the uploaded source code)_

**Repo parsed:** `scholar-graph-main/` (ZIP upload)  
**Package name (composer.json):** `nexus/scholar-graph`  
**Namespace:** `Mbsoft\ScholarGraph\`  
**Service Provider:** `Mbsoft\ScholarGraph\ScholarGraphServiceProvider` (auto-discovered via composer extra)

---

## Table of Contents

1. [TL;DR Quickstart (10 minutes)](#tldr-quickstart-10-minutes)
2. [Package Overview](#package-overview)
3. [Installation & Configuration](#installation--configuration)
4. [Integration Guide](#integration-guide)
5. [Core APIs (Developer Reference)](#core-apis-developer-reference)
6. [Feature How-Tos](#feature-how-tos)
7. [Testing & Local Verification](#testing--local-verification)
8. [Troubleshooting](#troubleshooting)
9. [Extension Points](#extension-points)
10. [Appendices](#appendices)

---

## 1) TL;DR Quickstart (10 minutes)

### What this package does (one-liner)
Build and analyze **scholarly citation graphs** in Laravel using **OpenAlex** as the data source; includes centrality, community detection, pathfinding, temporal snapshots, and JSON exporters for front‑end graph libraries.

### Requirements
- **PHP:** `^8.4` (from `composer.json`)
- **Laravel:** Package uses Laravel’s container, cache, queue, broadcasting, and config.
  > Exact Illuminate component constraints are not fully listed in this ZIP (the `require` block is ellipsized), but the code targets modern Laravel (10+).

### Install (Composer)
```bash
composer require nexus/scholar-graph mbsoft31/laravel-openalex
```
> This package expects the OpenAlex wrapper (`mbsoft31/laravel-openalex`) because the code imports `Mbsoft\OpenAlex\OpenAlex`.

### Package discovery & config publish
Auto-discovered via composer extra. Publish the config:
```bash
php artisan vendor:publish --tag=scholar-graph-config
```
Source: `src/ScholarGraphServiceProvider.php` lines ~75–83.

### Minimal `.env`
```env
# Caching
SCHOLAR_GRAPH_CACHE_STORE=file        # file|redis
SCHOLAR_GRAPH_CACHE_TTL=86400
SCHOLAR_GRAPH_CACHE_PREFIX=sg:

# Queues (used by async builder)
SCHOLAR_GRAPH_QUEUE_CONNECTION=default
SCHOLAR_GRAPH_QUEUE=scholar-graph
SCHOLAR_GRAPH_QUEUE_TIMEOUT=300
SCHOLAR_GRAPH_RETRY_AFTER=90

# Exporter
SCHOLAR_GRAPH_DEFAULT_EXPORTER=cytoscape  # cytoscape|graphml|gexf|d3

# Realtime (optional)
SCHOLAR_GRAPH_REALTIME_ENABLED=false
BROADCAST_DRIVER=log

# OpenAlex (also used by mbsoft31/laravel-openalex)
OPENALEX_MAILTO=you@example.com
OPENALEX_BASE_URL=https://api.openalex.org
OPENALEX_RATE_LIMIT=100
OPENALEX_BATCH_SIZE=25
```
Source: `config/scholar-graph.php`.

### Minimal integration route (end-to-end)
```php
// routes/web.php
use Illuminate\Support\Facades\Route;
use Mbsoft\ScholarGraph\Services\GraphBuilder;

Route::get('/graph/{workId}', function (GraphBuilder $builder, string $workId) {
    $data = $builder
        ->seed('work', $workId)            // Seed from an OpenAlex work (e.g., "W123456789")
        ->findSimilar('related', 25)       // Expand via OpenAlex "related_works"
        ->calculateCentrality('pagerank')  // Store PageRank scores on nodes/metrics
        ->asCytoscapeJson();               // Export for Cytoscape.js

    return response()->json($data);
});
```

---

## 2) Package Overview

### File/Folder Map (main)
```
config/
  scholar-graph.php

database/
  factories/ModelFactory.php
  migrations/create_scholar_graph_table.php.stub

src/
  Algorithms/
    Centrality/{BetweennessCalculator.php, PageRankCalculator.php}
    Community/{LeidenDetector.php, LouvainDetector.php}
    Pathfinding/{AStarAlgorithm.php, DijkstraAlgorithm.php}
  Commands/ScholarGraphCommand.php
  Contracts/
    {AsyncProcessorInterface.php, CentralityAlgorithmInterface.php,
     CommunityDetectionInterface.php, DataSourceInterface.php,
     ExporterInterface.php, TemporalAnalyzerInterface.php}
  DataSources/OpenAlexDataSource.php
  Domain/{Edge.php, Graph.php, GraphSnapshot.php, Node.php, TemporalGraph.php}
  Exporters/{CytoscapeJsonExporter.php, D3JsonExporter.php, GraphMLExporter.php, GexfExporter.php, RealtimeExporter.php}
  Facades/ScholarGraph.php
  Jobs/GraphBuildingJob.php
  ScholarGraph.php
  ScholarGraphServiceProvider.php
  Services/
    {Analyzer.php, AsyncGraphBuilder.php, DiscoveryService.php, GraphBuilder.php,
     GraphCache.php, RealtimeGraphManager.php, TemporalAnalyzer.php, TemporalGraphBuilder.php}
  Support/{Keys.php, NotImplemented.php}

resources/views/.gitkeep
```

### Roles (by area)
- **DataSources** — API adapters (OpenAlex).
- **Domain** — in‑memory graph: `Graph`, `Node`, `Edge` (+ temporal types).
- **Services** — `GraphBuilder` (build/expand/analyze/export), `Analyzer`, `DiscoveryService`, `GraphCache`, `AsyncGraphBuilder`, `Temporal*`, `RealtimeGraphManager`.
- **Algorithms** — centrality, community detection, pathfinding.
- **Exporters** — Cytoscape/D3 (implemented); GraphML/GEXF (stubs); Realtime (diff wrapper).

### High-level architecture (Mermaid)
```mermaid
flowchart LR
  A[Controller/Route] --> B(GraphBuilder)
  B -->|seed(type,id)| C[DataSourceInterface\n(OpenAlexDataSource)]
  C -->|Node/Edge| D[Graph]
  B -->|analyze| E[Analyzer\n(Centrality/Community)]
  B -->|findSimilar| F[DiscoveryService]
  B -->|export| G[ExporterInterface\n(Cytoscape/D3/...)]
  subgraph Temporal
    B -.temporal(...).-> TGB[TemporalGraphBuilder]
    TGB --> TA[TemporalAnalyzer]
  end
  B -->|cache| H[GraphCache]
  B -.optional.-> R[RealtimeGraphManager]
```

---

## 3) Installation & Configuration

### Composer
```bash
composer require nexus/scholar-graph mbsoft31/laravel-openalex
```

### Service Provider (auto-discovered)
- `Mbsoft\ScholarGraph\ScholarGraphServiceProvider`
    - **Publishes config:** tag `scholar-graph-config` → `config/scholar-graph.php`  
      Source: `src/ScholarGraphServiceProvider.php:79–83`
    - **Loads migrations:** `database/migrations` (stub included, not required at runtime)
    - **Binds**:
        - `DataSourceInterface` → `DataSources\OpenAlexDataSource`
        - `CentralityAlgorithmInterface` → `Algorithms\Centrality\PageRankCalculator`
        - `CommunityDetectionInterface` → `Algorithms\Community\LouvainDetector`
        - `TemporalAnalyzerInterface` → `Services\TemporalAnalyzer`
        - `ExporterInterface` → default from `config('scholar-graph.exporters.default')`
        - Named exporters: `scholar-graph.exporter.{cytoscape|graphml|gexf|d3}`
        - `GraphCache` (singleton using `scholar-graph.cache.*`)
        - `GraphBuilder` (singleton)
        - `AsyncProcessorInterface` bound to `Services\AsyncGraphBuilder` (used by async flow)
    - **Commands**: **none registered** (see `Troubleshooting`)

### Config keys (from `config/scholar-graph.php`)
- `cache.store|ttl|prefix`
- `queue.connection|queue|timeout|retry_after`
- `exporters.default` (supported formats: `cytoscape`, `graphml`, `gexf`, `d3`)
- `openalex.mailto|base_url|rate_limit|batch_size`
- `realtime.enabled|broadcast_driver|channel_prefix`
- `temporal.default_time_window|snapshot_intervals|trend_analysis_periods`

---

## 4) Integration Guide

### 4.1 Wire the OpenAlex client
This package binds `DataSourceInterface` to `OpenAlexDataSource`, which requires `Mbsoft\OpenAlex\OpenAlex` (from `mbsoft31/laravel-openalex`). Set these in `.env`:

```env
OPENALEX_MAILTO=you@example.com
OPENALEX_BASE_URL=https://api.openalex.org
OPENALEX_RATE_LIMIT=100
OPENALEX_BATCH_SIZE=25
```

### 4.2 Build → Analyze → Export (sync)
```php
use Mbsoft\ScholarGraph\Services\GraphBuilder;

$cyto = GraphBuilder::fromWork('W123456789')
    ->findSimilar('related', 25)       // Uses OpenAlex "related_works"
    ->calculateCentrality('pagerank')  // Runs bound centrality algorithm (default: PageRank)
    ->asCytoscapeJson();               // Or: ->toArray('cytoscape')

// Return JSON
return response()->json($cyto);
```

### 4.3 Minimal controller + route
```php
// app/Http/Controllers/GraphController.php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Mbsoft\ScholarGraph\Services\GraphBuilder;

class GraphController extends Controller
{
    public function show(string $workId): JsonResponse
    {
        $data = GraphBuilder::fromWork($workId)
            ->findSimilar('related', 30)
            ->calculateCentrality('pagerank')
            ->asCytoscapeJson();

        return response()->json($data);
    }
}

// routes/web.php
use App\Http\Controllers\GraphController;
Route::get('/graphs/{workId}', [GraphController::class, 'show']);
```

### 4.4 Switch cache store (file ↔ redis)
- Config: `scholar-graph.cache.store` (via `.env` `SCHOLAR_GRAPH_CACHE_STORE`)
- TTL: `SCHOLAR_GRAPH_CACHE_TTL`
- Prefix: `SCHOLAR_GRAPH_CACHE_PREFIX`

### 4.5 Async builds (present but **incomplete**)
- Interfaces/Classes:
    - `Contracts\AsyncProcessorInterface`
    - `Services\AsyncGraphBuilder`
    - `Jobs\GraphBuildingJob`
- **Caveat:** async flow calls `$cache->get()`/`$cache->set()` on `GraphCache`, but `GraphCache` only defines `remember()` → you must add `get()/set()` or use the underlying cache repository. See **Troubleshooting**.

### 4.6 CLI command (not registered)
- `src/Commands/ScholarGraphCommand.php` has signature `scholar-graph`, but it is **not** registered in the service provider.  
  Register in your app’s `Console\Kernel` if needed.

---

## 5) Core APIs (Developer Reference)

> File paths are relative to `src/`.

### 5.1 Facade / Entry

- `Facades/ScholarGraph.php` → accessor returns `\Mbsoft\ScholarGraph\ScholarGraph::class`
- `ScholarGraph.php` (empty class)  
  **Note:** The facade does not expose builder methods. Prefer DI of `Services\GraphBuilder`.

### 5.2 Services

#### `Services\GraphBuilder`
Static:
- `fromWork(string $workId): self`
- `fromAuthor(string $authorId): self`
- `fromConcept(string $conceptId): self`
- `fromMultiple(array $entities): self`
- `temporal(string $entityType, string $entityId): Services\TemporalGraphBuilder`
- `getJobStatus(string $jobId): array` *(async helper; see caveats)*
- `getJobResult(string $jobId): ?Domain\Graph` *(async helper; see caveats)*

Instance:
- `seed(string $type, string $id): self`
- `seedMultiple(array $entities): self`
- `async(bool $useAsync = true): self`
- `process(): string` *(dispatches async; requires working AsyncGraphBuilder/GraphCache)*
- `findSimilar(string $method = 'cocitation', int $limit = 50): self`
- `expandByAuthors(int $limit = 20): self`
- `expandByConcepts(int $limit = 30): self`
- `expandByInstitutions(int $limit = 15): self`
- `calculateCentrality(string $name = 'pagerank'): self`
- `detectCommunities(string $name = 'louvain'): self`
- `calculateAllCentralities(): self`
- `findPath(string $sourceId, string $targetId, string $algorithm = 'dijkstra'): array`
- `asCytoscapeJson(): array`
- `toArray(string $format = 'cytoscape'): array`
- `asD3Json(): array`
- `getSummaryStatistics(): array`
- `identifyInfluencers(string $metric = 'pagerank', int $top = 10): array`
- `getGraph(): Domain\Graph`

Constructor (DI):
```
__construct(
  Contracts\DataSourceInterface $source,
  Services\Analyzer $analyzer,
  Services\DiscoveryService $discovery,
  Services\GraphCache $cache,
  Contracts\ExporterInterface $exporter,
  ?Contracts\AsyncProcessorInterface $asyncProcessor = null
)
```

#### `Services\Analyzer`
- `applyCentrality(Domain\Graph $graph, string $name = 'pagerank'): void`
- `applyCommunities(Domain\Graph $graph, string $name = 'louvain'): void`

#### `Services\DiscoveryService`
- `findSimilar(Domain\Graph $graph, string $method = 'cocitation', int $limit = 50): void`

#### `Services\TemporalGraphBuilder`
- `timeWindows(array $windows): self`
- `analyzeEvolution(): self`
- `getEvolutionMetrics(): array`

#### `Services\TemporalAnalyzer`
- `createTemporalGraph(string $entityType, string $entityId, array $timeWindows): Domain\TemporalGraph`
- `analyzeEvolution(Domain\TemporalGraph $temporalGraph): array`
- `buildWithDateRange(string $type, string $id, \Carbon\Carbon $start, \Carbon\Carbon $end): Domain\Graph`
  > **Note:** Another call site references a missing `buildGraphForPeriod(...)`. Use `buildWithDateRange(...)`.

#### `Services\AsyncGraphBuilder`
- `buildAsync(string $entityType, string $entityId, array $options = []): string`
- `getStatus(string $jobId): array`
- `getResult(string $jobId): ?Domain\Graph`
  > **Requires** `GraphCache::get()`/`set()` implementations (missing).

#### `Services\GraphCache`
- `remember(string $key, callable $builder): Domain\Graph`
  > **Missing:** `get()` / `set()` (async code assumes these exist).

#### `Services\RealtimeGraphManager`
- `subscribeToUpdates(string $graphId, string $channel): void`
- `broadcastGraphUpdate(string $graphId, Domain\Graph $graph): void`
- `streamAnalysisProgress(string $jobId, array $progress): void`

### 5.3 Data Source

#### `DataSources\OpenAlexDataSource`
- `fetchEntity(string $id, string $type): Domain\Node`
- `fetchReferences(string $workId): Domain\Edge[]`
- `fetchCitations(string $workId): Domain\Edge[]`
- `findSimilar(Domain\Graph $graph, string $method, int $limit = 50): array{nodes: Node[], edges: Edge[]}`
    - **Implemented:** `'related'` → expands via OpenAlex `related_works`
    - **Not implemented:** other methods return empty arrays

### 5.4 Exporters

All implement `Contracts\ExporterInterface::export(Domain\Graph $graph): array`.

- `Exporters\CytoscapeJsonExporter` — **implemented** (Cytoscape.js)
- `Exporters\D3JsonExporter` — **implemented** (nodes/links + meta)
- `Exporters\GraphMLExporter` — **TODO** (returns `[]`)
- `Exporters\GexfExporter` — **TODO** (returns `[]`)
- `Exporters\RealtimeExporter` — wraps selected base exporter and emits diffs metadata

### 5.5 Domain Model

- `Domain\Node` — `__construct(string $id, string $type, array $attributes = [], array $metrics = [])`
- `Domain\Edge` — `__construct(string $source, string $target, ?float $weight = null, ?string $type = null)`
- `Domain\Graph`
    - `public array $nodes`
    - `public array $edges`
    - `public array $adjacencyList`
    - `public array $metrics`
    - `addNode(Node $node): void`
    - `addEdge(Edge $edge): void`
    - `getSuccessors(string $nodeId): string[]`
    - `getPredecessors(string $nodeId): string[]`
- Temporal: `GraphSnapshot`, `TemporalGraph`

---

## 6) Feature How-Tos

### 6.1 Build from a seed entity (Work)
```php
use Mbsoft\ScholarGraph\Services\GraphBuilder;

$builder = GraphBuilder::fromWork('W123456789'); // Seeds node + citations + references
```

### 6.2 Expand by related works
```php
$builder->findSimilar('related', 25); // Uses OpenAlex "related_works"
```

### 6.3 Run analyses
```php
$builder->calculateCentrality('pagerank'); // Stores scores under 'pagerank'
$builder->detectCommunities('louvain');    // Placeholder: connected-component labeling
```

### 6.4 Export formats
```php
$cyto = $builder->toArray('cytoscape'); // or ->asCytoscapeJson()
$d3   = $builder->toArray('d3');        // or ->asD3Json()
```
> `GraphML`/`GEXF` exporters exist but currently return `[]`.

### 6.5 Pathfinding
```php
$path = $builder->findPath('W123', 'W789', 'dijkstra'); // or 'astar'
```

### 6.6 Temporal snapshots (alpha)
```php
use Mbsoft\ScholarGraph\Services\GraphBuilder;

$temporal = GraphBuilder::temporal('work', 'W123456789')
    ->timeWindows([30, 90, 180, 365])
    ->analyzeEvolution()
    ->getEvolutionMetrics();
```
> There is a reference to a missing `buildGraphForPeriod(...)` in temporal code; prefer `buildWithDateRange(...)`. Treat temporal features as **experimental**.

---

## 7) Testing & Local Verification

### Composer scripts (from `composer.json`)
```bash
composer analyse        # phpstan analyse
composer test           # pest
composer test-coverage  # pest --coverage
composer format         # pint
```

**Note:** The repo ZIP does **not** include a `tests/` directory. To verify locally in your app, create a simple Pest test:

```php
// tests/Unit/GraphBuilderTest.php
use Mbsoft\ScholarGraph\Services\GraphBuilder;

it('builds a graph and exports cytoscape json', function () {
    $json = GraphBuilder::fromWork('W_FAKE')
        ->findSimilar('related', 3)
        ->calculateCentrality('pagerank')
        ->asCytoscapeJson();

    expect($json)->toHaveKey('elements');
    expect($json['elements'])->toHaveKeys(['nodes', 'edges']);
});
```

---

## 8) Troubleshooting

1) **Centrality name vs algorithm binding**  
   `Analyzer::applyCentrality($graph, $name)` uses the **injected** `CentralityAlgorithmInterface` (provider binds to **PageRank**). Passing `'betweenness'` will store under that key but still compute PageRank unless you rebind the interface.

2) **Async flow calls missing GraphCache methods**  
   `AsyncGraphBuilder` and `GraphBuildingJob` call `$cache->get()`/`$cache->set()`, but `GraphCache` only implements `remember()`.  
   **Fix:** Add `get(string $key, $default = null)` and `set(string $key, $value, $ttl = null)` to `GraphCache`, or swap to the raw cache repository.

3) **CLI command not registered**  
   `src/Commands/ScholarGraphCommand.php` exists (signature `scholar-graph`) but is not registered in the service provider. Register it in your app’s `Console\Kernel` to use it.

4) **Similarity methods**  
   In `OpenAlexDataSource::findSimilar(...)` only `'related'` is implemented. Other methods return empty arrays.

5) **Realtime broadcasting**  
   `RealtimeGraphManager` uses `BroadcastManager` (`connection()->channel()->broadcast(...)`). Ensure `SCHOLAR_GRAPH_REALTIME_ENABLED=true` and a compatible `BROADCAST_DRIVER` if you wire this up.

6) **Migrations**  
   A migration stub exists but is not required for runtime (graph is in-memory). You can ignore migrations unless you extend persistence.

---

## 9) Extension Points

### 9.1 Implement a custom data source
```php
namespace App\Scholar\DataSources;

use Mbsoft\ScholarGraph\Contracts\DataSourceInterface;
use Mbsoft\ScholarGraph\Domain\{Graph, Node, Edge};

final class MyDataSource implements DataSourceInterface
{
    public function fetchEntity(string $id, string $type): Node { /* ... */ }
    public function fetchReferences(string $workId): array { return []; /* Edge[] */ }
    public function fetchCitations(string $workId): array { return []; /* Edge[] */ }
    public function findSimilar(Graph $graph, string $method, int $limit = 50): array {
        return ['nodes' => [], 'edges' => []];
    }
}
```
Bind in your app:
```php
use Mbsoft\ScholarGraph\Contracts\DataSourceInterface;
use App\Scholar\DataSources\MyDataSource;

$this->app->bind(DataSourceInterface::class, MyDataSource::class);
```

### 9.2 Custom exporter
```php
namespace App\Scholar\Exporters;

use Mbsoft\ScholarGraph\Contracts\ExporterInterface;
use Mbsoft\ScholarGraph\Domain\Graph;

final class MyExporter implements ExporterInterface
{
    public function export(Graph $graph): array { /* ... */ }
}
```
Register similarly to built-ins, then:
```php
$builder->toArray('myformat'); // after binding "scholar-graph.exporter.myformat"
```

### 9.3 Swap algorithms
- Rebind `CommunityDetectionInterface` to `Algorithms\Community\LeidenDetector` (or your class).
- Rebind `CentralityAlgorithmInterface` to `Algorithms\Centrality\BetweennessCalculator` (or your class).

---

## 10) Appendices

### A) Env & Config Matrix (`config/scholar-graph.php`)
- **Cache**:  
  `SCHOLAR_GRAPH_CACHE_STORE` → `cache.store`  
  `SCHOLAR_GRAPH_CACHE_TTL` → `cache.ttl`  
  `SCHOLAR_GRAPH_CACHE_PREFIX` → `cache.prefix`
- **Queue**:  
  `SCHOLAR_GRAPH_QUEUE_CONNECTION` → `queue.connection`  
  `SCHOLAR_GRAPH_QUEUE` → `queue.queue`  
  `SCHOLAR_GRAPH_QUEUE_TIMEOUT` → `queue.timeout`  
  `SCHOLAR_GRAPH_RETRY_AFTER` → `queue.retry_after`
- **Exporters**:  
  `SCHOLAR_GRAPH_DEFAULT_EXPORTER` → `exporters.default`  
  Supported: `cytoscape`, `graphml`, `gexf`, `d3`
- **OpenAlex**:  
  `OPENALEX_MAILTO` → `openalex.mailto`  
  `OPENALEX_BASE_URL` → `openalex.base_url`  
  `OPENALEX_RATE_LIMIT` → `openalex.rate_limit`  
  `OPENALEX_BATCH_SIZE` → `openalex.batch_size`
- **Realtime**:  
  `SCHOLAR_GRAPH_REALTIME_ENABLED` → `realtime.enabled`  
  `BROADCAST_DRIVER` → `realtime.broadcast_driver`  
  `realtime.channel_prefix` default: `scholar-graph`
- **Temporal**:  
  `temporal.default_time_window` default: `365`  
  `temporal.snapshot_intervals` default: `[30,90,180,365]`  
  `temporal.trend_analysis_periods` default: `[1,2,5,10]`

### B) Public Method Index (condensed)

**Services\GraphBuilder (static)**  
`fromWork(string)`, `fromAuthor(string)`, `fromConcept(string)`, `fromMultiple(array)`, `temporal(string,string)`, `getJobStatus(string)`, `getJobResult(string)`

**Services\GraphBuilder (instance)**  
`seed(string,string)`, `seedMultiple(array)`, `async(bool)`, `process()`, `findSimilar(string='cocitation', int=50)`, `expandByAuthors(int=20)`, `expandByConcepts(int=30)`, `expandByInstitutions(int=15)`, `calculateCentrality(string='pagerank')`, `detectCommunities(string='louvain')`, `calculateAllCentralities()`, `findPath(string,string,string='dijkstra')`, `asCytoscapeJson()`, `toArray(string='cytoscape')`, `asD3Json()`, `getSummaryStatistics()`, `identifyInfluencers(string='pagerank', int=10)`, `getGraph()`

**Services\Analyzer** — `applyCentrality(Graph,string='pagerank')`, `applyCommunities(Graph,string='louvain')`  
**Services\DiscoveryService** — `findSimilar(Graph,string,int=50)`  
**DataSources\OpenAlexDataSource** — `fetchEntity(string,string)`, `fetchReferences(string)`, `fetchCitations(string)`, `findSimilar(Graph,string,int=50)`  
**Exporters\*** — `export(Graph): array`  
**Domain\Graph** — `addNode(Node)`, `addEdge(Edge)`, `getSuccessors(string): string[]`, `getPredecessors(string): string[]`  
**Temporal** — `TemporalGraphBuilder`, `TemporalAnalyzer`

### C) License & Credits
- **License:** MIT (`LICENSE.md`)
- **Author:** Mouadh Bekhouche
- **Homepage (composer.json):** `https://github.com/mbsoft31/scholar-graph`

---

### Decision checklists

**Cache store**
- ✅ Dev simplicity → `file`
- 🔄 Shared/stateful builds → `redis`
- ⏱ Heavier memoization → increase `SCHOLAR_GRAPH_CACHE_TTL`

**Exporter**
- ✅ Front-end graph (Cytoscape) → `cytoscape`
- ✅ D3 force-directed → `d3`
- ⏳ GraphML/GEXF → implement stubs first

**Centrality**
- ✅ Default PageRank (bound)
- 🔁 Switch to Betweenness → rebind interface

**Async**
- ⛔ Incomplete in current repo (needs `GraphCache::get/set`)
- 🛠 Add methods or use raw cache repository
