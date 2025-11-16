<?php

declare(strict_types=1);

namespace Spatie\Export;

use Illuminate\Support\Str;
use Spatie\Export\Jobs\CleanDestination;
use Spatie\Export\Jobs\CrawlSite;
use Spatie\Export\Jobs\ExportPath;
use Spatie\Export\Jobs\IncludeFile;

class Exporter
{
    /** @var bool */
    protected $cleanBeforeExport = false;

    /** @var bool */
    protected $crawl = false;

    /** @var string[] */
    protected $paths = [];

    /** @var array<string, string> */
    protected $includeFiles = [];

    /** @var string[] */
    protected $excludeFilePatterns = [];

    protected ?string $baseUrl = null;

    public function __construct(
        protected \Illuminate\Contracts\Bus\Dispatcher $dispatcher,
        protected \Illuminate\Contracts\Routing\UrlGenerator $urlGenerator,
    ) {}

    public function cleanBeforeExport(bool $cleanBeforeExport): self
    {
        $this->cleanBeforeExport = $cleanBeforeExport;

        return $this;
    }

    public function crawl(bool $crawl): self
    {
        $this->crawl = $crawl;

        return $this;
    }

    public function urls(...$urls): self
    {
        $urls = is_array($urls[0]) ? $urls[0] : $urls;

        $this->paths(
            array_map(fn (string $url): string => Str::replaceFirst($this->urlGenerator->to('/'), '', $url), $urls),
        );

        return $this;
    }

    public function paths(...$paths): self
    {
        $paths = is_array($paths[0]) ? $paths[0] : $paths;

        $this->paths = array_merge($this->paths, $paths);

        return $this;
    }

    /**
     * @param  array<string, string>  $includeFiles
     */
    public function includeFiles(array $includeFiles): self
    {
        $this->includeFiles = array_merge($this->includeFiles, $includeFiles);

        return $this;
    }

    public function excludeFilePatterns(array $excludeFilePatterns): self
    {
        $this->excludeFilePatterns = array_merge($this->excludeFilePatterns, $excludeFilePatterns);

        return $this;
    }

    public function baseUrl(?string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function export()
    {
        if ($this->cleanBeforeExport) {
            $this->dispatcher->dispatchNow(
                new CleanDestination,
            );
        }

        if ($this->crawl) {
            $this->dispatcher->dispatchNow(
                new CrawlSite,
            );
        }

        foreach ($this->paths as $path) {
            $this->dispatcher->dispatchNow(
                new ExportPath($path),
            );
        }

        foreach ($this->includeFiles as $source => $target) {
            $this->dispatcher->dispatchNow(
                new IncludeFile($source, $target, $this->excludeFilePatterns),
            );
        }
    }
}
