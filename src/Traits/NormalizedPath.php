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
        // Characters that are problematic in filesystem paths
        $problematicChars = [
            '?' => '%3F',
            '=' => '%3D',
            '&' => '%26',
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
