<?php

declare(strict_types=1);

namespace Spatie\Export\Crawler\CrawlProfiles;

use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls as BaseProfile;
use Spatie\Export\Utils;

class CrawlInternalUrls extends BaseProfile
{
    public function shouldCrawl(UriInterface $url): bool
    {
        if (Utils::rewritesUrlsOnExport()) {
            $exportUri = Utils::rewriteUriRoot();
            if (parent::shouldCrawl($url)) {
                return true;
            }

            return $exportUri->getHost() === $url->getHost();
        }

        return parent::shouldCrawl($url);
    }
}
