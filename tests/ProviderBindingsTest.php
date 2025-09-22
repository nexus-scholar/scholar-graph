<?php

use Mbsoft\ScholarGraph\ScholarGraphServiceProvider;
use Mbsoft\ScholarGraph\Contracts\ExporterInterface;
use Orchestra\Testbench\TestCase;

class ProviderBindingsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ScholarGraphServiceProvider::class];
    }

    public function test_default_exporter_binding()
    {
        $exp = $this->app->make(ExporterInterface::class);
        $this->assertNotNull($exp);
        $arr = $exp->export(new Mbsoft\ScholarGraph\Domain\Graph());
        $this->assertIsArray($arr);
    }
}
