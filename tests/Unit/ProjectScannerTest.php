<?php

use App\Services\ProjectScanner;

function removeProjectScannerFixture(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $children = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($children as $child) {
        $child->isDir() ? rmdir($child->getPathname()) : unlink($child->getPathname());
    }

    rmdir($path);
}

it('finds direct child projects with node_modules only', function () {
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'modtrash-project-scanner-'.bin2hex(random_bytes(8));

    mkdir($root.DIRECTORY_SEPARATOR.'project-a'.DIRECTORY_SEPARATOR.'node_modules', 0777, true);
    mkdir($root.DIRECTORY_SEPARATOR.'project-b', 0777, true);
    mkdir($root.DIRECTORY_SEPARATOR.'group'.DIRECTORY_SEPARATOR.'project-c'.DIRECTORY_SEPARATOR.'node_modules', 0777, true);

    try {
        $scanner = new ProjectScanner;

        $candidates = $scanner->scan($root);

        expect($candidates)
            ->toHaveCount(1)
            ->and($candidates[0]->projectName)->toBe('project-a')
            ->and($candidates[0]->projectPath)->toBe($root.DIRECTORY_SEPARATOR.'project-a')
            ->and($candidates[0]->nodeModulesPath)->toBe($root.DIRECTORY_SEPARATOR.'project-a'.DIRECTORY_SEPARATOR.'node_modules')
            ->and($scanner->checkedProjects())->toBe(3);
    } finally {
        removeProjectScannerFixture($root);
    }
});
