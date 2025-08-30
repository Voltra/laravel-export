<?php

declare(strict_types=1);

namespace Spatie\Export\Crawler;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Export\Traits\NormalizedPath;

class Observer extends CrawlObserver
{
    use NormalizedPath;

    public function __construct(protected string $entry, protected \Spatie\Export\Destination $destination) {}

    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null, ?string $linkText = null): void
    {
        if (! $this->isSuccesfullOrRedirect($response->getStatusCode())) {
            if ($foundOnUrl instanceof \Psr\Http\Message\UriInterface) {
                throw new \RuntimeException("URL [{$url}] found on [{$foundOnUrl}] returned status code [{$response->getStatusCode()}]");
            }

            throw new \RuntimeException("URL [{$url}] returned status code [{$response->getStatusCode()}]");
        }

        $this->destination->write(
            $this->normalizePath($url->getPath()),
            (string) $response->getBody()
        );
    }

    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null, ?string $linkText = null): void
    {
        throw $requestException;
    }

    protected function isSuccesfullOrRedirect(int $statusCode): bool
    {
        return in_array($statusCode, [200, 301, 302]);
    }
}
