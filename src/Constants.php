<?php

declare(strict_types=1);

namespace Spatie\Export;

abstract class Constants
{
    const EXPORT_HEADER = 'X-Laravel-Export';
    const DEFAULT_TIMEOUT = 60;
}
