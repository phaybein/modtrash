<?php

namespace App\Services;

use App\ValueObjects\CleanupCandidate;
use RuntimeException;

class MacOsTrashManager
{
    public function isSupported(): bool
    {
        return PHP_OS_FAMILY === 'Darwin';
    }

    public function isReady(): bool
    {
        $trashPath = $this->trashPath();

        return $trashPath !== null && is_dir($trashPath) && is_writable($trashPath);
    }

    public function move(CleanupCandidate $candidate): string
    {
        if (! $this->isSupported()) {
            throw new RuntimeException('Only macOS is supported.');
        }

        if (! is_dir($candidate->nodeModulesPath)) {
            throw new RuntimeException('node_modules folder no longer exists.');
        }

        if (basename($candidate->nodeModulesPath) !== 'node_modules') {
            throw new RuntimeException('Refusing to move a folder not named node_modules.');
        }

        if (is_link($candidate->nodeModulesPath)) {
            throw new RuntimeException('Refusing to move a symlinked node_modules folder.');
        }

        $trashPath = $this->trashPath();

        if ($trashPath === null || ! is_dir($trashPath) || ! is_writable($trashPath)) {
            throw new RuntimeException('Trash is not available or writable.');
        }

        $destination = $this->uniqueDestination($trashPath, $candidate);

        if (! rename($candidate->nodeModulesPath, $destination)) {
            throw new RuntimeException('Unable to move folder to Trash.');
        }

        return $destination;
    }

    private function trashPath(): ?string
    {
        $home = getenv('HOME');

        if (! is_string($home) || $home === '') {
            return null;
        }

        return $home.DIRECTORY_SEPARATOR.'.Trash';
    }

    private function uniqueDestination(string $trashPath, CleanupCandidate $candidate): string
    {
        $safeProjectName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $candidate->projectName);
        $baseName = trim((string) $safeProjectName, '-_.') ?: 'project';
        $basePath = $trashPath.DIRECTORY_SEPARATOR.$baseName.'-node_modules';

        if (! file_exists($basePath)) {
            return $basePath;
        }

        $timestamp = date('Ymd-His');

        for ($attempt = 1; $attempt <= 100; $attempt++) {
            $candidatePath = "{$basePath}-{$timestamp}-{$attempt}";

            if (! file_exists($candidatePath)) {
                return $candidatePath;
            }
        }

        throw new RuntimeException('Unable to choose a unique Trash destination.');
    }
}
