<?php

use App\Services\MacOsTrashManager;
use App\ValueObjects\CleanupCandidate;

function removeMacOsTrashManagerFixture(string $path): void
{
    if (! file_exists($path) && ! is_link($path)) {
        return;
    }

    if (is_link($path) || is_file($path)) {
        unlink($path);

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

function makeMacOsTrashManagerFixture(string $prefix): string
{
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$prefix.'-'.bin2hex(random_bytes(8));

    mkdir($path, 0777, true);

    return $path;
}

function withMacOsTrashManagerHome(string $home, callable $callback): mixed
{
    $originalHome = getenv('HOME');

    putenv("HOME={$home}");

    try {
        return $callback();
    } finally {
        $originalHome === false
            ? putenv('HOME')
            : putenv("HOME={$originalHome}");
    }
}

function macOsTrashManager(): MacOsTrashManager
{
    return new class extends MacOsTrashManager
    {
        public function isSupported(): bool
        {
            return true;
        }
    };
}

it('refuses to move anything not named exactly node_modules', function () {
    $root = makeMacOsTrashManagerFixture('modtrash-trash-manager');

    try {
        $projectPath = $root.DIRECTORY_SEPARATOR.'project-a';
        $wrongFolder = $projectPath.DIRECTORY_SEPARATOR.'node-modules';

        mkdir($wrongFolder, 0777, true);

        macOsTrashManager()->move(new CleanupCandidate(
            projectName: 'project-a',
            projectPath: $projectPath,
            nodeModulesPath: $wrongFolder,
        ));
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toContain('not named node_modules')
            ->and($wrongFolder)->toBeDirectory();

        return;
    } finally {
        removeMacOsTrashManagerFixture($root);
    }

    $this->fail('Expected MacOsTrashManager to reject folders not named exactly node_modules.');
});

it('refuses symlinked node_modules directories', function () {
    $root = makeMacOsTrashManagerFixture('modtrash-trash-manager');

    try {
        $projectPath = $root.DIRECTORY_SEPARATOR.'project-a';
        $realNodeModules = $root.DIRECTORY_SEPARATOR.'real-node-modules';
        $symlinkedNodeModules = $projectPath.DIRECTORY_SEPARATOR.'node_modules';

        mkdir($projectPath, 0777, true);
        mkdir($realNodeModules, 0777, true);

        if (! symlink($realNodeModules, $symlinkedNodeModules)) {
            $this->markTestSkipped('This filesystem does not support symlinks.');
        }

        macOsTrashManager()->move(new CleanupCandidate(
            projectName: 'project-a',
            projectPath: $projectPath,
            nodeModulesPath: $symlinkedNodeModules,
        ));
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toContain('symlinked node_modules')
            ->and($symlinkedNodeModules)->toBeDirectory()
            ->and($realNodeModules)->toBeDirectory();

        return;
    } finally {
        removeMacOsTrashManagerFixture($root);
    }

    $this->fail('Expected MacOsTrashManager to reject symlinked node_modules.');
});

it('moves a real node_modules folder into the Trash path under HOME', function () {
    $root = makeMacOsTrashManagerFixture('modtrash-trash-manager');
    $home = makeMacOsTrashManagerFixture('modtrash-home');

    try {
        $projectPath = $root.DIRECTORY_SEPARATOR.'project-a';
        $nodeModules = $projectPath.DIRECTORY_SEPARATOR.'node_modules';
        $packageFile = $nodeModules.DIRECTORY_SEPARATOR.'left-pad'.DIRECTORY_SEPARATOR.'package.json';
        $trashPath = $home.DIRECTORY_SEPARATOR.'.Trash';

        mkdir(dirname($packageFile), 0777, true);
        mkdir($trashPath, 0777, true);
        file_put_contents($packageFile, '{}');

        $destination = withMacOsTrashManagerHome(
            $home,
            fn () => macOsTrashManager()->move(new CleanupCandidate(
                projectName: 'project-a',
                projectPath: $projectPath,
                nodeModulesPath: $nodeModules,
            )),
        );

        expect($nodeModules)->not->toBeDirectory()
            ->and($destination)->toBe($trashPath.DIRECTORY_SEPARATOR.'project-a-node_modules')
            ->and($destination)->toBeDirectory()
            ->and($destination.DIRECTORY_SEPARATOR.'left-pad'.DIRECTORY_SEPARATOR.'package.json')->toBeFile();
    } finally {
        removeMacOsTrashManagerFixture($root);
        removeMacOsTrashManagerFixture($home);
    }
});
