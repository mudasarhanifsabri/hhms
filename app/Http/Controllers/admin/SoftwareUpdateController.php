<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Throwable;
use Symfony\Component\Process\Process;

class SoftwareUpdateController extends Controller
{
    public function index()
    {
        return view('admin.software-update.index', [
            'branch' => $this->run(['git', 'branch', '--show-current'], 10)['output'] ?: 'unknown',
            'lastCommit' => $this->run(['git', 'log', '-1', '--pretty=%h - %s'], 10)['output'] ?: 'unknown',
            'hasGit' => File::isDirectory(base_path('.git')),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'confirm_update' => ['accepted'],
        ], [
            'confirm_update.accepted' => 'Please confirm that you want to update software from GitHub.',
        ]);

        if (! File::isDirectory(base_path('.git'))) {
            return back()->with('error', 'This installation is not connected to a Git repository.');
        }

        $commands = [
            'Current branch' => ['git', 'branch', '--show-current'],
            'Before commit' => ['git', 'log', '-1', '--pretty=%h - %s'],
            'Backup local code changes' => ['git', 'stash', 'push', '-m', 'HHMS automatic backup before software update'],
            'Fetch GitHub' => ['git', 'fetch', 'origin'],
            'Pull latest code' => ['git', 'pull', '--ff-only'],
            'Check PHP version' => [PHP_BINARY, '-r', 'if (PHP_VERSION_ID < 80300) { fwrite(STDERR, "HHMS requires PHP 8.3 or newer. Current PHP: " . PHP_VERSION . PHP_EOL); exit(1); } echo "PHP " . PHP_VERSION . PHP_EOL;'],
            'Check Composer' => [$this->composerBinary(), '--version', '--no-ansi'],
            'Install PHP packages' => [$this->composerBinary(), 'install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction', '--no-progress'],
            'Run migrations' => [PHP_BINARY, 'artisan', 'migrate', '--force'],
            'Clear Laravel cache' => [PHP_BINARY, 'artisan', 'optimize:clear'],
            'Cache config' => [PHP_BINARY, 'artisan', 'config:cache'],
            'Cache routes' => [PHP_BINARY, 'artisan', 'route:cache'],
            'Cache views' => [PHP_BINARY, 'artisan', 'view:cache'],
            'After commit' => ['git', 'log', '-1', '--pretty=%h - %s'],
        ];

        $results = [];
        foreach ($commands as $label => $command) {
            $result = $this->run($command, $label === 'Install PHP packages' ? 900 : 300);
            $results[] = compact('label', 'command', 'result');

            if (! $result['success']) {
                return back()
                    ->with('error', "Software update stopped at: {$label}")
                    ->with('update_results', $results);
            }
        }

        return back()
            ->with('success', 'Software updated successfully from GitHub.')
            ->with('update_results', $results);
    }

    private function run(array $command, int $timeout = 60): array
    {
        File::ensureDirectoryExists(storage_path('composer'));

        $process = new Process($command, base_path());
        $process->setTimeout($timeout);
        $process->setIdleTimeout(null);
        $process->setEnv([
            'COMPOSER_ALLOW_SUPERUSER' => '1',
            'COMPOSER_HOME' => storage_path('composer'),
            'COMPOSER_MEMORY_LIMIT' => '-1',
        ]);

        try {
            $process->run();
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'code' => $process->getExitCode(),
                'output' => trim($process->getOutput()),
                'error' => $exception->getMessage() . PHP_EOL . trim($process->getErrorOutput()),
            ];
        }

        return [
            'success' => $process->isSuccessful(),
            'code' => $process->getExitCode(),
            'output' => trim($process->getOutput()),
            'error' => trim($process->getErrorOutput()),
        ];
    }

    private function composerBinary(): string
    {
        return env('COMPOSER_BINARY', 'composer');
    }
}
