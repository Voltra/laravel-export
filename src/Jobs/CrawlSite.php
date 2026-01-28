<?php

declare(strict_types=1);

namespace Spatie\Export\Jobs;

use Illuminate\Contracts\Routing\UrlGenerator;
use Spatie\Export\Crawler\Crawler;
use Spatie\Export\Crawler\CrawlProfiles\CrawlInternalUrls;
use Spatie\Export\Crawler\LocalClient;
use Spatie\Export\Crawler\Observer;
use Spatie\Export\Destination;
use Spatie\Export\Utils;

class CrawlSite
{
    public function handle(UrlGenerator $urlGenerator, Destination $destination): void
    {
        $entry = $urlGenerator->to('/');

        $crawler = new Crawler(new LocalClient);
        $crawler
            ->setCrawlObserver(new Observer($entry, $destination))
            ->setCrawlProfile(new CrawlInternalUrls($entry));

        $crawler->startCrawling($entry);
    }
}
