<?php

use Illuminate\Support\Facades\Route;
use Spatie\Export\Exporter;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFileExists;

const HOME_CONTENT = <<<'HTML'
    <a href="feed/blog.atom" title="all blogposts">Feed</a>
    Home
    <a href="about">About</a>
    <a href="redirect">Spatie</a>
HTML;

const ABOUT_CONTENT = 'About';

const FEED_CONTENT = 'Feed';

const REDIRECT_CONTENT = <<<'HTML'
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="0;url='https://spatie.be'" />

        <title>Redirecting to https://spatie.be</title>
    </head>
    <body>
        Redirecting to <a href="https://spatie.be">https://spatie.be</a>.
    </body>
</html>
HTML;

function assertHomeExists(): void
{
    assertExportedFile(__DIR__.'/dist/index.html', HOME_CONTENT);
}

function assertAboutExists(): void
{
    assertExportedFile(__DIR__.'/dist/about/index.html', ABOUT_CONTENT);
}

function assertFeedBlogAtomExists(): void
{
    assertExportedFile(__DIR__.'/dist/feed/blog.atom', FEED_CONTENT);
}

function assertRedirectExists(): void
{
    assertExportedFile(__DIR__.'/dist/redirect/index.html', REDIRECT_CONTENT);
}

function assertExportedFile(string $path, string $content): void
{
    assertFileExists($path);
    // Normalize line endings for cross-platform compatibility
    $expectedContent = str_replace(["\r\n", "\r"], "\n", $content);
    $actualContent = str_replace(["\r\n", "\r"], "\n", file_get_contents($path));
    assertEquals($expectedContent, $actualContent);
}

function assertRequestsHasHeader(): void
{
    expect(Route::getCurrentRequest()->header('X-Laravel-Export'))->toEqual('true');
}

beforeEach(function () {
    $this->distDirectory = __DIR__.DIRECTORY_SEPARATOR.'dist';

    if (file_exists($this->distDirectory)) {
        exec(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? 'rmdir "'.$this->distDirectory.'" /s /q'
            : 'rm -r "'.$this->distDirectory.'"');
    }

    Route::get('/', function () {
        return HOME_CONTENT;
    });

    Route::get('about', function () {
        return ABOUT_CONTENT;
    });

    Route::get('feed/blog.atom', function () {
        return FEED_CONTENT;
    });

    Route::redirect('redirect', 'https://spatie.be');
});

afterEach(function () {
    assertHomeExists();
    assertAboutExists();
    assertFeedBlogAtomExists();
    assertRedirectExists();
    assertRequestsHasHeader();
});

it('crawls and exports routes', function () {
    app(Exporter::class)->export();
});

it('exports paths', function () {
    app(Exporter::class)
        ->crawl(false)
        ->paths(['/', '/about', '/feed/blog.atom', '/redirect'])
        ->export();
});

it('exports urls', function () {
    app(Exporter::class)
        ->crawl(false)
        ->urls([url('/'), url('/about'), url('/feed/blog.atom'), url('/redirect')])
        ->export();
});

it('exports mixed', function () {
    app(Exporter::class)
        ->crawl(false)
        ->paths('/')
        ->urls(url('/about'), url('/feed/blog.atom'), url('/redirect'))
        ->export();
});

it('exports included files', function () {
    app(Exporter::class)
        ->includeFiles([__DIR__.'/stubs/public' => ''])
        ->export();

    assertFileExists(__DIR__.'/dist/favicon.ico');
    assertFileExists(__DIR__.'/dist/media/image.png');

    expect(file_exists(__DIR__.'/dist/index.php'))->toBeFalse();
});

it('exports paths with query parameters', function () {
    // Set up a simple route with query parameters
    Route::get('test-categories', function () {
        $page = request('page', 1);
        return "Test Categories page {$page}";
    });

    // Also set up the default routes that afterEach expects
    Route::get('/', function () {
        return HOME_CONTENT;
    });
    Route::get('about', function () {
        return ABOUT_CONTENT;
    });
    Route::get('feed/blog.atom', function () {
        return FEED_CONTENT;
    });
    Route::redirect('redirect', 'https://spatie.be');

    app(Exporter::class)
        ->crawl(false)
        ->paths([
            '/',                      // Required by afterEach
            '/about',                 // Required by afterEach
            '/feed/blog.atom',        // Required by afterEach
            '/redirect',              // Required by afterEach
            '/test-categories?page=1',
            '/test-categories?page=2',
        ])
        ->export();

    // Check if files are created with URL-encoded names
    $expectedPath1 = __DIR__.'/dist/test-categories%3Fpage%3D1/index.html';
    $expectedPath2 = __DIR__.'/dist/test-categories%3Fpage%3D2/index.html';
    expect(file_exists($expectedPath1))->toBeTrue("Expected file not found: {$expectedPath1}");
    expect(file_exists($expectedPath2))->toBeTrue("Expected file not found: {$expectedPath2}");
    // dd($expectedPath1, $expectedPath2);
    // Verify content is correct
    expect(file_get_contents($expectedPath1))->toBe('Test Categories page 1');
    expect(file_get_contents($expectedPath2))->toBe('Test Categories page 2');
});
