<?php

declare(strict_types=1);

namespace Spatie\Export\Jobs;

use Spatie\Export\Destination;

class CleanDestination
{
    public function handle(Destination $destination)
    {
        $destination->clean();
    }
}
