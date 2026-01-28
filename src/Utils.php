<?php

declare(strict_types=1);

namespace Spatie\Export;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\File;
use Spatie\Export\Http\Middleware\ExportBaseUrlRewriteMiddleware;

abstract class Utils
{
    public const DEFAULT_TIMEOUT = 60;

    public static function getConfigTimeout(): int
    {
        // We don't use our default value of 60 right here as it
        // breaks the behavior of process timeouts with null for no timeout
        $timeout = config()->get('export.timeout', null);

        if (! is_int($timeout) && $timeout !== null) {
            return self::DEFAULT_TIMEOUT;
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
        return File::exists(public_path($path));
    }
}
