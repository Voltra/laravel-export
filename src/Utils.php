<?php

declare(strict_types=1);

namespace Spatie\Export;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Contracts\Http\Kernel;
use Spatie\Export\Http\Middleware\ExportBaseUrlRewriteMiddleware;

abstract class Utils
{
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
}
