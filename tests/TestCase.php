<?php

declare(strict_types=1);

namespace Spatie\Export\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Export\ExportServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [ExportServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('filesystems.disks.export', [
            'driver' => 'local',
            'root' => __DIR__.'/dist',
        ]);

        $app['config']->set('export.disk', 'export');
        $app['config']->set('export.include_files', []);

        // any random key is fine
        $app['config']->set('app.key', 'base64:XkrFWC+TGnySY2LsldPXAxuHpyjh8UuoPMt6yy2gJ8U=');
    }
}
