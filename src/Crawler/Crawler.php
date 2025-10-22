<?php

declare(strict_types=1);

namespace Spatie\Export\Crawler;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Str;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler as SpatieCrawler;
use Spatie\Crawler\CrawlUrl;
use Spatie\Export\Utils;
use Tree\Node\Node;

class Crawler extends SpatieCrawler
{
    /**
     * @see \Spatie\Crawler\Crawler::addToDepthTree()
     */
    public function addToDepthTree(UriInterface $url, UriInterface $parentUrl, ?Node $node = null, ?UriInterface $originalUrl = null): ?Node
    {
        return parent::addToDepthTree(
            $this->reverseRewriteUrl($url),
            $parentUrl,
            $node,
            $originalUrl
        );
    }

    /**
     * @see \Spatie\Crawler\Crawler::addToCrawlQueue()
     */
    public function addToCrawlQueue(CrawlUrl $crawlUrl): SpatieCrawler
    {
        return parent::addToCrawlQueue(CrawlUrl::create(
            url: $this->reverseRewriteUrl($crawlUrl->url),
            foundOnUrl: $crawlUrl->foundOnUrl,
            id: $crawlUrl->getId(),
            linkText: $crawlUrl->linkText
        ));
    }

    /**
     * @see \Spatie\Crawler\Crawler::getCrawlRequests()
     */
    protected function getCrawlRequests(): \Generator
    {
        // $urlGen = resolve(UrlGenerator::class);

        while (
            $this->reachedCrawlLimits() === false &&
            $this->reachedTimeLimits() === false &&
            $crawlUrl = $this->crawlQueue->getPendingUrl()
        ) {
            if (
                $this->crawlProfile->shouldCrawl($crawlUrl->url) === false ||
                $this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl)
            ) {
                $this->crawlQueue->markAsProcessed($crawlUrl);

                continue;
            }

            foreach ($this->crawlObservers as $crawlObserver) {
                $crawlObserver->willCrawl($crawlUrl->url, $crawlUrl->linkText);
            }

            $this->totalUrlCount++;
            $this->currentUrlCount++;
            $this->crawlQueue->markAsProcessed($crawlUrl);

            $url = $this->reverseRewriteUrl($crawlUrl->url);

            yield $crawlUrl->getId() => new Request('GET', $url);
        }
    }

    protected function reverseRewriteUrl(Uri $uri)
    {
        if (Utils::rewritesUrlsOnExport()) {
            // We want to revert back the rewritten URL to a local URL
            // when crawling because we make request to those page.
            // This avoids making requests in production,
            // and also ensures we properly export all
            // the pages whilst keeping the rewrite
            // system in place
            $exportRoot = Utils::rewriteUriRoot();

            if (Str::startsWith($uri, $exportRoot)) {
                $appRoot = Utils::cleanUri($this->getBaseUrl());
                $asInternalUrl = str($uri)
                    ->replaceFirst($exportRoot, $appRoot)
                    ->toString();
                $asInternalUrl = preg_replace('#(?<!:)//+#', '/', $asInternalUrl);

                return Utils::cleanUri(new Uri($asInternalUrl));
            }
        }

        return Utils::cleanUri($uri);
    }
}
