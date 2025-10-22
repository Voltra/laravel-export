<?php

declare(strict_types=1);

namespace Spatie\Export\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Uri;
use Spatie\Export\Constants;
use Spatie\Export\Utils;
use Symfony\Component\HttpFoundation\Response;

class ExportBaseUrlRewriteMiddleware
{
    /**
     * @param  \Closure(Request $request): Response  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if ($this->shouldProcess($request)) {
            $originalRoot = $this->process($request);
            $response = $next($request);
            URL::useOrigin($originalRoot);

            return $response;
        }

        return $next($request);
    }

    public function shouldProcess(Request $request): bool
    {
        if (! $request->hasHeader(Constants::EXPORT_HEADER)) {
            // We do not want to process any request that has nothing
            // to do with an export job
            return false;
        }

        // If no base URL was given for us to rewrite to
        // then there's no points in processing the request
        return Utils::rewritesUrlsOnExport();
    }

    /**
     * @precondition !empty(config('export.base_url'))
     *
     * @returns string The original root url
     */
    private function process(Request $request): string
    {
        $originalRootUrl = $this->sanitizeBaseUrl(
            URL::formatRoot(
                $request->getScheme(),
                $request->getHost(),
            ),
        );

        // Ensured to not be empty by the preconditions
        $baseUrl = $this->sanitizeBaseUrl((string) Utils::rewriteUriRoot());

        $uri = Uri::of($baseUrl);

        URL::useOrigin($baseUrl);
        URL::forceScheme($uri->scheme());

        $updatedRequest = $request;
        app()->instance('request', $updatedRequest);
        Facade::clearResolvedInstance('request');

        return $originalRootUrl;
    }

    private function sanitizeBaseUrl(string $baseUrl): string
    {
        return str($baseUrl)
            ->rtrim('/')
            ->toString();
    }
}
