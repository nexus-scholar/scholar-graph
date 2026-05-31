# Scholar Graph

> **Status:** Legacy PHP scholarly-graph prototype.
>
> This repository is kept as predecessor work for the Nexus Scholar citation-network tooling line. It explored transforming OpenAlex-style scholarly metadata into graph structures for analysis, ranking, and visualization. Current graph package work is split into [`nexus-scholar/graph-core`](https://github.com/nexus-scholar/graph-core) for graph data structures and [`nexus-scholar/graph-algorithms`](https://github.com/nexus-scholar/graph-algorithms) for algorithms such as centrality and traversal.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mbsoft31/scholar-graph.svg?style=flat-square)](https://packagist.org/packages/mbsoft31/scholar-graph)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mbsoft31/scholar-graph/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mbsoft31/scholar-graph/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mbsoft31/scholar-graph/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mbsoft31/scholar-graph/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mbsoft31/scholar-graph.svg?style=flat-square)](https://packagist.org/packages/mbsoft31/scholar-graph)

## What This Repo Represents

`scholar-graph` was an early Laravel/PHP package experiment for academic knowledge graphs. The public value is not that this is the current production package; it is evidence of the research-tooling path that led to the cleaner Nexus Scholar graph packages.

The package direction includes:

- representing scholarly records as graph entities;
- connecting provider metadata to graph structures;
- exploring citation-network and bibliographic-relationship workflows;
- preparing graph data for ranking, traversal, and export.

Use this repository as historical context. For current work, prefer the newer Nexus Scholar graph packages linked above.

## Installation

You can install the package via composer:

```bash
composer require mbsoft31/scholar-graph
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="scholar-graph-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="scholar-graph-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="scholar-graph-views"
```

## Usage

```php
$scholarGraph = new Mbsoft\ScholarGraph();
echo $scholarGraph->echoPhrase('Hello, Mbsoft!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mouadh Bekhouche](https://github.com/mbsoft31)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
