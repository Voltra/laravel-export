<?php

namespace Spatie\Export\Traits;

use Illuminate\Support\Str;

trait NormalizedPath
{
    protected function normalizePath(string $path)
    {
        // Sanitize path for filesystem compatibility
        $path = $this->sanitizePathForFilesystem($path);

        if (! Str::contains(basename($path), '.')) {
            $path .= '/index.html';
        }

        return ltrim($path, '/');
    }

    protected function sanitizePathForFilesystem(string $path): string
    {
        // If there's a query string, convert it into a subdirectory
        if (strpos($path, '?') !== false) {
            $parts = explode('?', $path, 2);
            $basePath = $parts[0];
            $query = $parts[1];
            // Do not encode '=' or '&', just append as subdirectory
            $path = rtrim($basePath, '/').'/'.$query;
        }
        // Encode other problematic characters except '=' and '&' in the query string
        $problematicChars = [
            ':' => '%3A',
            '<' => '%3C',
            '>' => '%3E',
            '"' => '%22',
            '|' => '%7C',
            '*' => '%2A',
            // Don't encode forward slashes as they're path separators
        ];

        return str_replace(array_keys($problematicChars), array_values($problematicChars), $path);
    }
}
