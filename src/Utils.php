<?php

declare(strict_types=1);

namespace Spatie\Export;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Uri as SupportUri;
use Spatie\Export\Http\Middleware\ExportBaseUrlRewriteMiddleware;

abstract class Utils
{
    public static function getConfigTimeout(): ?int
    {
        // We don't use our default value of 60 right here as it
        // breaks the behavior of process timeouts with null for no timeout
        $timeout = config()->get('export.timeout', null);

        if (! is_int($timeout) && $timeout !== null) {
            return Constants::DEFAULT_TIMEOUT;
        }

        return $timeout;
    }

    public static function configureExportKernel(Kernel $kernel)
    {
        $kernel->bootstrap();

        $kernel->getApplication()
            ->get('router')
            ->prependMiddlewareToGroup('web', ExportBaseUrlRewriteMiddleware::class);
    }

    public static function rewritesUrlsOnExport(): bool
    {
        return filled(config('export.base_url'));
    }

    public static function rewriteUriRoot(): Uri
    {
        return self::cleanUri(new Uri(config('export.base_url', '')));
    }

    public static function cleanUri(Uri $uri): Uri
    {
        return $uri->withHost(
            rtrim($uri->getHost(), '/')
        );
    }

    public static function isAssetFile(string $path): bool
    {
        $fullPath = public_path($path);

        return static::fileExists($fullPath);
    }

    public static function isAssetUrl(Uri $url, ?Uri $baseUrl = null): bool
    {
        if ($baseUrl === null) {
            $baseUrl = SupportUri::of(URL::formatRoot(
                $url->getScheme(),
                $url->getHost(),
            ));
        }

        // Let's say $url = "https://my.website.com/laravel/static/my/route"
        // and baseUrl = "https://my.website.com/laravel/static"
        // then the "app relative" path is "/my/route"
        $path = str($url)->after((string) $baseUrl)->toString();

        if (static::isAssetFile($path)) {
            return true;
        }

        $assetPath = asset($path);

        return $assetPath === $url->__toString()
            && static::fileExists($assetPath);
    }

    protected static function fileExists(string $path)
    {
        return File::exists($path)
        && File::isFile($path)
        && ! File::isDirectory($path);
    }
}
