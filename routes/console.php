<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| DirectAdmin gives us one cron entry and no daemons, so a single
| minute-scheduler drives everything:
|
|   * * * * * /usr/local/bin/php ~/biertappen/artisan schedule:run >/dev/null 2>&1
|
| See docs/deployment.md.
*/

/*
 * Finishes a deployment when a trigger file is uploaded. A no-op the rest of
 * the time, which is why it is safe to run every minute.
 */
Schedule::command('deploy:hook')
    ->everyMinute()
    ->withoutOverlapping();

/*
 * Drains the database queue. There is no queue:work daemon on shared hosting,
 * so this substitutes: it exits as soon as the queue is empty, and the 50-second
 * cap keeps two runs from overlapping into the next minute.
 *
 * Mail (verification, password resets) is queued so SMTP latency never blocks a
 * web request. Lemon Squeezy webhooks are handled synchronously and do not
 * depend on this running.
 */
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

/*
 * Housekeeping. Webhook ledger rows exist to deduplicate retries, which stop
 * after a few days; keeping them forever would grow a table nothing reads.
 */
Schedule::command('model:prune', [
    '--model' => [App\Models\WebhookEvent::class],
])->weekly();
