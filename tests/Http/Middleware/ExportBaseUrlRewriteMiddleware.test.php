<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Spatie\Export\Exporter;
use Spatie\Export\Http\Middleware\ExportBaseUrlRewriteMiddleware;

use function Pest\Laravel\artisan;

describe(ExportBaseUrlRewriteMiddleware::class, function () {
    $testRoot = function (string $uri = '') {
        return dirname(__DIR__, levels: 2).DIRECTORY_SEPARATOR.$uri;
    };

    beforeEach(function () {
        artisan('cache:clear');
    });

    it('properly rewrites URLs on export', function () use ($testRoot) {
        config()->set('export.base_url', 'https://www.test.org/a-ok');
        Route::get('/test', fn () => Blade::render('{{ url("/ok") }}'))->middleware('web');
        $expected = 'https://www.test.org/a-ok/ok';

        //TODO: Fix HTTPS rewrite when running on HTTP only

        app(Exporter::class)
            ->crawl(false)
            ->paths(['/test'])
            ->export();

        $resultFile = $testRoot('dist/test/index.html');
        expect(file_exists($resultFile))->toBeTrue();

        $content = file_get_contents($resultFile);
        expect($content)->toEqual($expected);
    });
});
