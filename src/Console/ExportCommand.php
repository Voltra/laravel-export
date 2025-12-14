<?php

declare(strict_types=1);

namespace Spatie\Export\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\Export\Exporter;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class ExportCommand extends Command
{
    protected $name = 'export';

    protected $description = 'Export the entire app to a static site';

    public function __construct()
    {
        parent::__construct();

        collect()
            ->merge(config('export.before', []))
            ->merge(config('export.after', []))
            ->keys()
            ->unique()
            ->sort()
            ->each(function (string $name) {
                $this->addOption(
                    "skip-{$name}",
                    null,
                    InputOption::VALUE_NONE,
                    "Skip the {$name} hook"
                );
            });

        $this->addOption('skip-all', null, InputOption::VALUE_NONE, 'Skip all hooks');
        $this->addOption('skip-before', null, InputOption::VALUE_NONE, 'Skip all before hooks');
        $this->addOption('skip-after', null, InputOption::VALUE_NONE, 'Skip all after hooks');
        $this->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'The timeout for the processes run during the export', 60, [
            60,
            null,
        ]);
    }

    public function handle(Exporter $exporter)
    {
        $this->runBeforeHooks();

        $this->info('Exporting site...');

        $exporter->export();

        if (config('export.disk')) {
            $this->info('Files were saved to disk `'.config('export.disk').'`');
        } else {
            $this->info('Files were saved to `dist`');
        }

        $this->runAfterHooks();
    }

    protected function runBeforeHooks()
    {
        if ($this->input->getOption('skip-all') || $this->input->getOption('skip-before')) {
            return;
        }

        $beforeHooks = collect(config('export.before', []))
            ->reject(fn (string $hook, string $name) => $this->input->getOption("skip-{$name}"));

        if (empty($beforeHooks)) {
            return;
        }

        $this->info('Running before hooks...');

        $this->runHooks($beforeHooks);
    }

    protected function runAfterHooks()
    {
        if ($this->input->getOption('skip-all') || $this->input->getOption('skip-after')) {
            return;
        }

        $afterHooks = collect(config('export.after', []))
            ->reject(fn (string $hook, string $name) => $this->input->getOption("skip-{$name}"));

        if (empty($afterHooks)) {
            return;
        }

        $this->info('Running after hooks...');

        $this->runHooks($afterHooks);
    }

    protected function runHooks(Collection $hooks)
    {
        $timeout = $this->input->getOption('timeout') ?? config('export.timeout', 60);

        if (!is_int($timeout) && $timeout !== null) {
            $timeout = 60;
        }

        foreach ($hooks as $name => $command) {
            $this->comment("[{$name}]", 'v');

            if (method_exists(Process::class, 'fromShellCommandline')) {
                $process = Process::fromShellCommandline($command, null, null, null, $timeout);
            } else {
                $process = new Process($command, null, null, null, $timeout);
            }

            $process->mustRun();

            foreach ($process as $data) {
                $this->output->write($data);
            }
        }
    }
}
