<?php

declare(strict_types=1);

namespace Spatie\Export\Jobs;

use Illuminate\Http\Request;
use Spatie\Export\Constants;
use Illuminate\Http\Response;
use Spatie\Export\Destination;
use Illuminate\Contracts\Http\Kernel;
use Spatie\Export\Traits\NormalizedPath;
use Illuminate\Contracts\Routing\UrlGenerator;
use Spatie\Export\Http\Middleware\ExportBaseUrlRewriteMiddleware;

class ExportPath
{
    use NormalizedPath;

    public function __construct(protected string $path) {}

    public function handle(Kernel $kernel, Destination $destination, UrlGenerator $urlGenerator)
    {
        $localRequest = Request::create($urlGenerator->to($this->path));

        $localRequest->headers->set(Constants::EXPORT_HEADER, 'true');

        $kernel->bootstrap();

        $kernel->getApplication()->get('router')->prependMiddlewareToGroup('web', ExportBaseUrlRewriteMiddleware::class);

        /**
         * @var Response $response
         */
        $response = $kernel->handle($localRequest);

        if (! $this->isSuccesfullOrRedirect($response->status())) {
            throw new \RuntimeException("Path [{$this->path}] returned status code [{$response->status()}]");
        }

        $destination->write($this->normalizePath($this->path), $response->content());
    }

    protected function isSuccesfullOrRedirect(int $status): bool
    {
        return in_array($status, [200, 301, 302]);
    }
}
