<?php

namespace Spatie\Export\Crawler\CrawlProfiles;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls as BaseProfile;
use Spatie\Export\Utils;

class CrawlInternalUrls extends BaseProfile
{
    public function shouldCrawl(UriInterface $url): bool
    {
        if (Utils::rewritesUrlsOnExport()) {
            $exportUri = Utils::rewriteUriRoot();
            return parent::shouldCrawl($url)
                || $exportUri->getHost() === $url->getHost();
        }

        return parent::shouldCrawl($url);
    }
}
