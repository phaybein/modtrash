<?php

namespace App\ValueObjects;

readonly class CleanupCandidate
{
    public function __construct(
        public string $projectName,
        public string $projectPath,
        public string $nodeModulesPath,
    ) {}
}
