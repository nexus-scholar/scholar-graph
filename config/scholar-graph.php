<?php

return [
    // Cache Configuration
    'cache' => [
        'store' => env('SCHOLAR_GRAPH_CACHE_STORE', 'file'),
        'ttl' => env('SCHOLAR_GRAPH_CACHE_TTL', 86400),
        'prefix' => env('SCHOLAR_GRAPH_CACHE_PREFIX', 'sg:'),
    ],

    // Queue Configuration for Async Processing
    'queue' => [
        'connection' => env('SCHOLAR_GRAPH_QUEUE_CONNECTION', 'default'),
        'queue' => env('SCHOLAR_GRAPH_QUEUE', 'scholar-graph'),
        'timeout' => env('SCHOLAR_GRAPH_QUEUE_TIMEOUT', 300),
        'retry_after' => env('SCHOLAR_GRAPH_RETRY_AFTER', 90),
    ],

    // Performance Settings
    'performance' => [
        'max_nodes_sync' => env('SCHOLAR_GRAPH_MAX_NODES_SYNC', 1000),
        'chunk_size' => env('SCHOLAR_GRAPH_CHUNK_SIZE', 100),
        'memory_limit' => env('SCHOLAR_GRAPH_MEMORY_LIMIT', '512M'),
    ],

    // Algorithm Settings
    'algorithms' => [
        'pagerank' => [
            'damping_factor' => 0.85,
            'max_iterations' => 100,
            'tolerance' => 1e-6,
        ],
        'louvain' => [
            'resolution' => 1.0,
            'max_iterations' => 50,
        ],
    ],

    // Export Settings
    'exporters' => [
        'default' => env('SCHOLAR_GRAPH_DEFAULT_EXPORTER', 'cytoscape'),
        'formats' => ['cytoscape', 'graphml', 'gexf', 'd3'],
    ],

    // OpenAlex Configuration
    'openalex' => [
        'mailto' => env('OPENALEX_MAILTO'),
        'base_url' => env('OPENALEX_BASE_URL', 'https://api.openalex.org'),
        'rate_limit' => env('OPENALEX_RATE_LIMIT', 100), // requests per second
        'batch_size' => env('OPENALEX_BATCH_SIZE', 25),
    ],

    // Real-time Features
    'realtime' => [
        'enabled' => env('SCHOLAR_GRAPH_REALTIME_ENABLED', false),
        'broadcast_driver' => env('BROADCAST_DRIVER', 'log'),
        'channel_prefix' => 'scholar-graph',
    ],

    // Temporal Analysis
    'temporal' => [
        'default_time_window' => 365, // days
        'snapshot_intervals' => [30, 90, 180, 365], // days
        'trend_analysis_periods' => [1, 2, 5, 10], // years
    ],
];
