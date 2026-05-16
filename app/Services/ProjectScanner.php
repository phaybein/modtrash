<?php

namespace App\Services;

use App\ValueObjects\CleanupCandidate;
use FilesystemIterator;
use UnexpectedValueException;

class ProjectScanner
{
    private int $checkedProjects = 0;

    /**
     * @return list<CleanupCandidate>
     */
    public function scan(string $rootPath): array
    {
        $this->checkedProjects = 0;
        $candidates = [];

        try {
            $children = new FilesystemIterator($rootPath, FilesystemIterator::SKIP_DOTS);
        } catch (UnexpectedValueException) {
            return [];
        }

        foreach ($children as $child) {
            if (! $child->isDir() || $child->isLink()) {
                continue;
            }

            $this->checkedProjects++;

            $projectPath = $child->getPathname();
            $nodeModulesPath = $projectPath.DIRECTORY_SEPARATOR.'node_modules';

            if (! is_dir($nodeModulesPath) || is_link($nodeModulesPath)) {
                continue;
            }

            $candidates[] = new CleanupCandidate(
                projectName: $child->getFilename(),
                projectPath: $projectPath,
                nodeModulesPath: $nodeModulesPath,
            );
        }

        return $candidates;
    }

    public function checkedProjects(): int
    {
        return $this->checkedProjects;
    }
}
