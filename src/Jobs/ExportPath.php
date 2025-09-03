<?php

declare(strict_types=1);

namespace Spatie\Export\Jobs;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Export\Destination;
use Spatie\Export\Traits\NormalizedPath;

class ExportPath
{
    use NormalizedPath;

    public function __construct(protected string $path) {}

    public function handle(Kernel $kernel, Destination $destination, UrlGenerator $urlGenerator)
    {
        $localRequest = Request::create($urlGenerator->to($this->path));

        $localRequest->headers->set('X-Laravel-Export', 'true');

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
