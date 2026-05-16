<?php

namespace App\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

class NodeModulesSizer
{
    public function sizeInBytes(string $path): ?int
    {
        if (! is_dir($path)) {
            return null;
        }

        $bytes = 0;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
            );

            foreach ($iterator as $item) {
                if ($item->isLink()) {
                    continue;
                }

                if ($item->isFile()) {
                    $bytes += $item->getSize();
                }
            }
        } catch (UnexpectedValueException) {
            return null;
        }

        return $bytes;
    }
}
