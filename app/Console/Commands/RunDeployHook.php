<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Finishes a deployment on hosting with no shell access.
 *
 * DirectAdmin gives us cron but not SSH, so migrations and cache warming cannot
 * be run by hand after uploading a release. This command is driven by the
 * minute-scheduler and does nothing at all until a trigger file appears — the
 * deploy step is "upload the release, then upload an empty file over FTP".
 *
 * A web-exposed migration route would be the obvious alternative and a bad one:
 * it is an unauthenticated way to mutate the schema, permanently reachable.
 */
class RunDeployHook extends Command
{
    protected $signature = 'deploy:hook {--force : Run even if no trigger file is present}';

    protected $description = 'Finish a deployment when the trigger file is present.';

    public function handle(): int
    {
        $trigger = storage_path('app/deploy.trigger');

        if (! File::exists($trigger) && ! $this->option('force')) {
            return self::SUCCESS;
        }

        $log = storage_path('logs/deploy.log');
        $this->append($log, str_repeat('=', 60));
        $this->append($log, 'Deploy hook started at '.now()->toDateTimeString());

        try {
            /*
             * Order matters. Caches are cleared first because a cached config or
             * route file from the previous release can reference classes this
             * one no longer has, which would make the migration itself fail.
             */
            foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $step) {
                $this->runStep($step, $log);
            }

            $this->runStep('migrate', $log, ['--force' => true]);

            // Warm the caches back up: on shared hosting these are a meaningful
            // share of per-request time.
            foreach (['config:cache', 'route:cache', 'view:cache', 'event:cache'] as $step) {
                $this->runStep($step, $log);
            }

            $this->append($log, 'Deploy hook finished successfully.');
        } catch (Throwable $e) {
            /*
             * Left in place on failure, deliberately. The trigger is the record
             * that a deploy is unfinished; removing it would hide a half-applied
             * release behind a green-looking cron job.
             */
            $this->append($log, 'DEPLOY FAILED: '.$e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        File::delete($trigger);
        $this->append($log, 'Trigger removed.');

        return self::SUCCESS;
    }

    private function runStep(string $command, string $log, array $arguments = []): void
    {
        Artisan::call($command, $arguments);

        $output = trim(Artisan::output());

        $this->append($log, "> php artisan {$command}");

        if ($output !== '') {
            $this->append($log, $output);
        }
    }

    private function append(string $path, string $line): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::append($path, $line.PHP_EOL);

        $this->line($line);
    }
}
