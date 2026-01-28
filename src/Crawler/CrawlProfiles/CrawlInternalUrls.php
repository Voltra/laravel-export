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

            if ($exportUri->getHost() !== $url->getHost()) {
                return false;
            }

            // Let's say $url = "https://my.website.com/laravel/static/my/route"
            // and $exportUri = "https://my.website.com/laravel/static"
            // then the "app relative" path is "/my/route"
            $path = str($url)->after($exportUri)->toString();
            // We don't want to crawl on assets
            if (! Utils::isAssetFile($path)) {
                return true;
            }

            return asset($path) === $url->__toString();
        }

        return parent::shouldCrawl($url);
    }
}
