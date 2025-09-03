<?php

declare(strict_types=1);

namespace Spatie\Export;

interface Destination
{
    public function clean();

    public function write(string $path, string $contents);
}
