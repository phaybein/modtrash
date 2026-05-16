<?php

use App\Services\MacOsTrashManager;
use App\ValueObjects\CleanupCandidate;

function removeCleanupCommandFixture(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $children = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($children as $child) {
        $child->isDir() && ! $child->isLink()
            ? rmdir($child->getPathname())
            : unlink($child->getPathname());
    }

    rmdir($path);
}

function cleanupCommandTrashManager(): MacOsTrashManager
{
    return new class extends MacOsTrashManager
    {
        public function isSupported(): bool
        {
            return true;
        }

        public function isReady(): bool
        {
            return true;
        }

        public function move(CleanupCandidate $candidate): string
        {
            throw new RuntimeException('The skip-flow test should not move anything.');
        }
    };
}

it('skips a discovered node_modules folder when the user answers no', function () {
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'modtrash-cleanup-command-'.bin2hex(random_bytes(8));
    $nodeModules = $root.DIRECTORY_SEPARATOR.'project-a'.DIRECTORY_SEPARATOR.'node_modules';

    mkdir($nodeModules, 0777, true);
    mkdir($root.DIRECTORY_SEPARATOR.'project-b', 0777, true);
    file_put_contents($nodeModules.DIRECTORY_SEPARATOR.'package.json', '{}');

    $this->app->instance(MacOsTrashManager::class, cleanupCommandTrashManager());

    try {
        $this->artisan('cleanup', ['path' => $root])
            ->expectsOutputToContain('Project: project-a')
            ->expectsOutputToContain("node_modules: {$nodeModules}")
            ->expectsQuestion('Move node_modules to Trash? [y/N/q]', 'n')
            ->expectsTable(['Metric', 'Count'], [
                ['Projects checked', '2'],
                ['node_modules found', '1'],
                ['Moved to Trash', '0'],
                ['Skipped', '1'],
                ['Failed', '0'],
                ['Estimated moved size', '0 B'],
            ])
            ->assertExitCode(0);

        expect($nodeModules)->toBeDirectory()
            ->and($nodeModules.DIRECTORY_SEPARATOR.'package.json')->toBeFile();
    } finally {
        removeCleanupCommandFixture($root);
    }
});

it('skips a direct project node_modules folder when the user answers no', function () {
    $projectPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'modtrash-cleanup-command-direct-'.bin2hex(random_bytes(8));
    $nodeModules = $projectPath.DIRECTORY_SEPARATOR.'node_modules';

    mkdir($nodeModules, 0777, true);
    file_put_contents($nodeModules.DIRECTORY_SEPARATOR.'package.json', '{}');

    $this->app->instance(MacOsTrashManager::class, cleanupCommandTrashManager());

    try {
        $this->artisan('cleanup', ['path' => $projectPath])
            ->expectsOutputToContain('Project: '.basename($projectPath))
            ->expectsOutputToContain("node_modules: {$nodeModules}")
            ->expectsQuestion('Move node_modules to Trash? [y/N/q]', 'n')
            ->expectsTable(['Metric', 'Count'], [
                ['Projects checked', '1'],
                ['node_modules found', '1'],
                ['Moved to Trash', '0'],
                ['Skipped', '1'],
                ['Failed', '0'],
                ['Estimated moved size', '0 B'],
            ])
            ->assertExitCode(0);

        expect($nodeModules)->toBeDirectory()
            ->and($nodeModules.DIRECTORY_SEPARATOR.'package.json')->toBeFile();
    } finally {
        removeCleanupCommandFixture($projectPath);
    }
});
