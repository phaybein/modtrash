<?php

namespace App\Commands;

use App\Services\MacOsTrashManager;
use App\Services\NodeModulesSizer;
use App\Services\ProjectScanner;
use App\ValueObjects\CleanupCandidate;
use LaravelZero\Framework\Commands\Command;

class CleanupCommand extends Command
{
    protected $signature = 'cleanup {path : Parent folder to scan}';

    protected $description = 'Find direct child projects with node_modules and move them to Trash after confirmation';

    public function __construct(
        private readonly ProjectScanner $scanner,
        private readonly NodeModulesSizer $sizer,
        private readonly MacOsTrashManager $trashManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->normalizePath((string) $this->argument('path'));

        if (! is_dir($path)) {
            $this->error("Folder does not exist: {$path}");

            return self::FAILURE;
        }

        if (! is_readable($path)) {
            $this->error("Folder is not readable: {$path}");

            return self::FAILURE;
        }

        if (! $this->trashManager->isSupported()) {
            $this->error('modtrash currently supports macOS only.');

            return self::FAILURE;
        }

        if (! $this->trashManager->isReady()) {
            $this->error('Trash is not available or writable for the current user.');

            return self::FAILURE;
        }

        $result = [
            'checked' => 0,
            'found' => 0,
            'moved' => 0,
            'skipped' => 0,
            'failed' => 0,
            'freed' => 0,
        ];

        $candidates = $this->scanner->scan($path);
        $result['checked'] = $this->scanner->checkedProjects();
        $result['found'] = count($candidates);

        if ($candidates === []) {
            $this->info('No direct child projects with node_modules were found.');
            $this->displaySummary($result);

            return self::SUCCESS;
        }

        foreach ($candidates as $candidate) {
            $size = $this->sizer->sizeInBytes($candidate->nodeModulesPath);

            $this->newLine();
            $this->line("Project: {$candidate->projectName}");
            $this->line("Path: {$candidate->projectPath}");
            $this->line("node_modules: {$candidate->nodeModulesPath}");
            $this->line('Size: '.$this->formatSize($size));

            $choice = $this->askForChoice();

            if ($choice === 'q') {
                $this->warn('Stopped by user.');
                break;
            }

            if ($choice !== 'y') {
                $result['skipped']++;

                continue;
            }

            if ($this->moveCandidateToTrash($candidate)) {
                $result['moved']++;
                $result['freed'] += $size ?? 0;

                continue;
            }

            $result['failed']++;
        }

        $this->displaySummary($result);

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function normalizePath(string $path): string
    {
        if ($path === '~') {
            return (string) getenv('HOME');
        }

        if (str_starts_with($path, '~/')) {
            return getenv('HOME').substr($path, 1);
        }

        return $path;
    }

    private function askForChoice(): string
    {
        while (true) {
            $answer = strtolower(trim((string) $this->ask('Move node_modules to Trash? [y/N/q]')));

            if ($answer === '') {
                return 'n';
            }

            if (in_array($answer, ['y', 'n', 'q'], true)) {
                return $answer;
            }

            $this->warn('Please enter y, n, or q.');
        }
    }

    private function moveCandidateToTrash(CleanupCandidate $candidate): bool
    {
        try {
            $destination = $this->trashManager->move($candidate);
            $this->info("Moved to Trash: {$destination}");

            return true;
        } catch (\Throwable $throwable) {
            $this->error("Failed to move {$candidate->nodeModulesPath}: {$throwable->getMessage()}");

            return false;
        }
    }

    private function displaySummary(array $result): void
    {
        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Projects checked', (string) $result['checked']],
            ['node_modules found', (string) $result['found']],
            ['Moved to Trash', (string) $result['moved']],
            ['Skipped', (string) $result['skipped']],
            ['Failed', (string) $result['failed']],
            ['Estimated moved size', $this->formatSize($result['freed'])],
        ]);
    }

    private function formatSize(?int $bytes): string
    {
        if ($bytes === null) {
            return 'unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return sprintf('%s %s', round($size, $unit === 0 ? 0 : 1), $units[$unit]);
    }
}
