<?php

namespace Mbsoft\ScholarGraph;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Mbsoft\ScholarGraph\Contracts\{
    DataSourceInterface,
    CentralityAlgorithmInterface,
    CommunityDetectionInterface,
    ExporterInterface,
    AsyncProcessorInterface,
    TemporalAnalyzerInterface
};
use Mbsoft\ScholarGraph\DataSources\OpenAlexDataSource;
use Mbsoft\ScholarGraph\Algorithms\Centrality\PageRankCalculator;
use Mbsoft\ScholarGraph\Algorithms\Community\LouvainDetector;
use Mbsoft\ScholarGraph\Algorithms\Pathfinding\{DijkstraAlgorithm, AStarAlgorithm};
use Mbsoft\ScholarGraph\Exporters\CytoscapeJsonExporter;
use Mbsoft\ScholarGraph\Services\{
    GraphBuilder,
    AsyncGraphBuilder,
    Analyzer,
    DiscoveryService,
    GraphCache,
    TemporalAnalyzer
};
use Mbsoft\ScholarGraph\Exporters\RealtimeExporter;

class ScholarGraphServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scholar-graph.php', 'scholar-graph');

        // Core Services
        $this->app->singleton('scholar-graph.cache', function ($app) {
            $cache = $app->make(CacheFactory::class);
            $store = config('scholar-graph.cache.store', 'file');
            return $cache->store($store);
        });

        $this->app->singleton(GraphCache::class, function ($app) {
            return new GraphCache(
                $app->make('scholar-graph.cache'),
                config('scholar-graph.cache.ttl', 86400)
            );
        });

        // Data Sources
        $this->app->bind(DataSourceInterface::class, OpenAlexDataSource::class);

        // Algorithm Bindings
        $this->app->bind(CentralityAlgorithmInterface::class, PageRankCalculator::class);
        $this->app->bind(CommunityDetectionInterface::class, LouvainDetector::class);
        $this->app->bind(TemporalAnalyzerInterface::class, TemporalAnalyzer::class);

        // Pathfinding Algorithms
        $this->app->bind(DijkstraAlgorithm::class);
        $this->app->bind(AStarAlgorithm::class);

        // Services
        $this->app->singleton(Analyzer::class);
        $this->app->singleton(DiscoveryService::class);
        $this->app->singleton(AsyncGraphBuilder::class);
        $this->app->bind(AsyncProcessorInterface::class, AsyncGraphBuilder::class);
        $this->app->singleton(RealtimeExporter::class);

        // Exporters
        $this->registerExporters();

        // Core GraphBuilder
        $this->app->singleton(GraphBuilder::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/scholar-graph.php' => config_path('scholar-graph.php'),
        ], 'scholar-graph-config');
    }

    private function registerExporters(): void
    {
        $exporters = [
            'cytoscape' => \Mbsoft\ScholarGraph\Exporters\CytoscapeJsonExporter::class,
            'graphml' => \Mbsoft\ScholarGraph\Exporters\GraphMLExporter::class,
            'gexf' => \Mbsoft\ScholarGraph\Exporters\GexfExporter::class,
            'd3' => \Mbsoft\ScholarGraph\Exporters\D3JsonExporter::class,
        ];

        foreach ($exporters as $name => $class) {
            $this->app->bind("scholar-graph.exporter.{$name}", $class);
        }

        $this->app->bind(ExporterInterface::class, function ($app) {
            $default = config('scholar-graph.exporters.default', 'cytoscape');
            return $app->make("scholar-graph.exporter.{$default}");
        });
    }
}
