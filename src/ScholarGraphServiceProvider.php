<?php

namespace Mbsoft\ScholarGraph;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Mbsoft\ScholarGraph\Commands\ScholarGraphCommand;

class ScholarGraphServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('scholar-graph')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_scholar_graph_table')
            ->hasCommand(ScholarGraphCommand::class);
    }
}
