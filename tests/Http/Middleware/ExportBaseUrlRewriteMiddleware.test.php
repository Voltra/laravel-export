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

    it('properly rewrites URLs on export', function () use ($testRoot) {
        config()->set('export.base_url', 'https://www.test.org/a-ok');
        Route::get('/test', fn () => Blade::render('{{ url("/ok") }}'))->middleware('web');
        $expected = 'https://www.test.org/a-ok/ok';

        resolve(Exporter::class)
            ->cleanBeforeExport(true)
            ->crawl(false)
            ->paths(['/test'])
            ->export();

        $resultFile = $testRoot('dist/test/index.html');
        expect(file_exists($resultFile))->toBeTrue();

        $content = file_get_contents($resultFile);
        expect($content)->toEqual($expected);
    });

    it('properly rewires URL requests to their local equivalent on crawl', function () use($testRoot) {
        config()->set('export.base_url', 'https://www.test.org/b-ok');

        Route::get('/', fn () => Blade::render('<a href="{{ url("/ok") }}">Test</a>'))->middleware('web');
        Route::get('/ok', fn () => Blade::render('<p>SUCCESS</p>'))->middleware('web');

        resolve(Exporter::class)
            ->cleanBeforeExport(true)
            ->crawl(true)
            ->export();

        $resultFile = $testRoot('dist/index.html');
        expect(file_exists($resultFile))->toBeTrue();
        $content = file_get_contents($resultFile);
        expect($content)->toEqual('<a href="https://www.test.org/b-ok/ok">Test</a>');

        $okFile = $testRoot('dist/ok/index.html');
        expect(file_exists($okFile))->toBeTrue();
        $content = file_get_contents($okFile);
        expect($content)->toEqual('<p>SUCCESS</p>');
    });
});
