<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
            'Fetch GitHub' => ['git', 'fetch', 'origin'],
            'Pull latest code' => ['git', 'pull', '--ff-only'],
            'Install PHP packages' => ['composer', 'install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction'],
            'Run migrations' => [PHP_BINARY, 'artisan', 'migrate', '--force'],
            'Clear Laravel cache' => [PHP_BINARY, 'artisan', 'optimize:clear'],
            'Cache config' => [PHP_BINARY, 'artisan', 'config:cache'],
            'Cache routes' => [PHP_BINARY, 'artisan', 'route:cache'],
            'Cache views' => [PHP_BINARY, 'artisan', 'view:cache'],
            'After commit' => ['git', 'log', '-1', '--pretty=%h - %s'],
        ];

        $results = [];
        foreach ($commands as $label => $command) {
            $result = $this->run($command, 300);
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
        $process = new Process($command, base_path());
        $process->setTimeout($timeout);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'code' => $process->getExitCode(),
            'output' => trim($process->getOutput()),
            'error' => trim($process->getErrorOutput()),
        ];
    }
}
