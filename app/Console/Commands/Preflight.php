<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class Preflight extends Command
{
    protected $signature = 'procurely:preflight';

    protected $description = 'Check that this installation is configured safely for production';

    private int $failures = 0;

    private int $warnings = 0;

    public function handle(): int
    {
        $this->newLine();

        $this->line('<options=bold>Application</>');
        $this->must('APP_ENV is production', app()->isProduction(), 'it is "'.app()->environment().'"');
        $this->must('APP_DEBUG is off', ! config('app.debug'), 'debug pages expose internals');
        $this->must('APP_KEY is set', filled(config('app.key')));
        $url = (string) config('app.url');
        $this->must('APP_URL uses https', str_starts_with($url, 'https://') && ! str_contains($url, 'localhost'), 'it is "'.$url.'"');
        $this->should('Time zone is set (not UTC)', config('app.timezone') !== 'UTC', 'bid closing times would display in UTC; set APP_TIMEZONE');
        $this->should('Configuration is cached', app()->configurationIsCached(), 'run php artisan optimize');

        $this->newLine();
        $this->line('<options=bold>Database and accounts</>');
        $reachable = $this->databaseReachable();
        $this->must('Database is reachable', $reachable);

        if ($reachable) {
            $this->must('No pending migrations', $this->pendingMigrations() === [], implode(', ', $this->pendingMigrations()));
            $this->must('An active admin exists', User::where('role', 'admin')->where('is_active', true)->exists());
            $this->must('No placeholder user accounts', ! User::withTrashed()->where('email', 'like', '%@example.%')->exists(), 'accounts using @example.* addresses exist');
        }

        $this->newLine();
        $this->line('<options=bold>Content and security</>');
        $this->must('Organisation details are real', ! $this->hasPlaceholderDetails(), 'edit config/procurely.php');
        $this->must('Turnstile keys are real', $this->turnstileLooksReal(), 'missing, or Cloudflare test keys');
        $this->must('Mail is delivered (not logged)', ! in_array(config('mail.default'), ['log', 'array'], true), 'MAIL_MAILER is "'.config('mail.default').'"');
        $this->must('Mail sender address is real', ! str_contains(strtolower((string) config('mail.from.address')), 'example'), 'MAIL_FROM_ADDRESS is "'.config('mail.from.address').'"');
        $this->must('Session cookies are secure', (bool) config('session.secure'), 'set SESSION_SECURE_COOKIE=true');
        $this->should('Session lifetime is 120 minutes or less', (int) config('session.lifetime') <= 120, 'it is '.config('session.lifetime'));
        $this->should('Content-Security-Policy is enforced', config('procurely.csp') === 'enforce', 'CSP_MODE is "'.config('procurely.csp').'"');
        $this->should('ADMIN_PASSWORD is removed from .env', blank(config('procurely.admin.password')), 'remove it after seeding');

        $this->newLine();
        $this->line('<options=bold>Files and backups</>');
        foreach (['storage/app' => storage_path('app'), 'storage/logs' => storage_path('logs'), 'storage/framework' => storage_path('framework'), 'bootstrap/cache' => base_path('bootstrap/cache')] as $label => $path) {
            $this->must("Writable: {$label}", is_dir($path) && is_writable($path));
        }
        $this->should('A backup completed in the last 26 hours', $this->backupIsFresh(), 'check BACKUP_PATH and the cron job');

        $this->newLine();
        $this->line("{$this->failures} failed, {$this->warnings} warnings.");

        return $this->failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function must(string $label, bool $ok, string $hint = ''): void
    {
        $this->report($label, $ok, $hint, fail: true);
    }

    private function should(string $label, bool $ok, string $hint = ''): void
    {
        $this->report($label, $ok, $hint, fail: false);
    }

    private function report(string $label, bool $ok, string $hint, bool $fail): void
    {
        if ($ok) {
            $this->line("  <fg=green>PASS</>  {$label}");

            return;
        }

        $fail ? $this->failures++ : $this->warnings++;
        $tag = $fail ? '<fg=red>FAIL</>' : '<fg=yellow>WARN</>';
        $this->line("  {$tag}  {$label}".($hint !== '' ? " ({$hint})" : ''));
    }

    private function databaseReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return list<string> */
    private function pendingMigrations(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')]));

        return array_values(array_diff(array_keys($files), $migrator->getRepository()->getRan()));
    }

    private function hasPlaceholderDetails(): bool
    {
        $text = strtolower(config('procurely.organization').' '.config('procurely.contact.email').' '.config('procurely.contact.address'));

        return str_contains($text, 'example') || config('procurely.contact.phone') === '(555) 555-0100';
    }

    private function turnstileLooksReal(): bool
    {
        $secret = (string) config('services.turnstile.secret_key');

        return filled($secret)
            && filled(config('services.turnstile.site_key'))
            && ! preg_match('/^[123]x0{10,}/', $secret);
    }

    private function backupIsFresh(): bool
    {
        $path = config('procurely.backup.path');
        $marker = $path ? rtrim($path, '/').'/LAST_OK' : null;

        return $marker && is_file($marker) && filemtime($marker) > now()->subHours(26)->getTimestamp();
    }
}
