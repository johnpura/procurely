<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PreflightTest extends TestCase
{
    use RefreshDatabase;

    private function productionConfig(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config([
            'app.debug' => false,
            'app.url' => 'https://bids.realville.gov',
            'app.timezone' => 'America/New_York',
            'mail.default' => 'smtp',
            'mail.from.address' => 'procurement@realville.gov',
            'session.secure' => true,
            'session.lifetime' => 60,
            'services.turnstile.site_key' => 'real-site-key',
            'services.turnstile.secret_key' => 'real-secret-key',
            'procurely.organization' => 'City of Realville',
            'procurely.contact.email' => 'procurement@realville.gov',
            'procurely.contact.phone' => '(508) 555-0142',
            'procurely.contact.address' => 'City Hall, 1 Main Street, Realville, MA 01000',
            'procurely.csp' => 'enforce',
            'procurely.admin.password' => null,
        ]);

        User::factory()->admin()->create(['email' => 'chief@realville.gov']);
    }

    public function test_a_correctly_configured_installation_passes(): void
    {
        $this->productionConfig();

        $this->artisan('procurely:preflight')->assertExitCode(0);
    }

    public function test_the_default_local_setup_fails(): void
    {
        $this->artisan('procurely:preflight')
            ->expectsOutputToContain('APP_ENV is production')
            ->assertExitCode(1);
    }

    #[DataProvider('unsafeSettings')]
    public function test_each_unsafe_setting_fails_the_preflight(array $override, string $expected): void
    {
        $this->productionConfig();
        config($override);

        $this->artisan('procurely:preflight')->expectsOutputToContain($expected)->assertExitCode(1);
    }

    public static function unsafeSettings(): array
    {
        return [
            'debug on' => [['app.debug' => true], 'APP_DEBUG is off'],
            'http url' => [['app.url' => 'http://bids.realville.gov'], 'APP_URL uses https'],
            'logged mail' => [['mail.default' => 'log'], 'Mail is delivered'],
            'example sender' => [['mail.from.address' => 'hello@example.com'], 'Mail sender address is real'],
            'placeholder organisation' => [['procurely.organization' => 'City of Example'], 'Organisation details are real'],
            'turnstile test keys' => [['services.turnstile.secret_key' => '1x0000000000000000000000000000000AA'], 'Turnstile keys are real'],
            'insecure cookies' => [['session.secure' => false], 'Session cookies are secure'],
        ];
    }

    public function test_placeholder_accounts_and_missing_admins_fail(): void
    {
        $this->productionConfig();
        User::factory()->create(['email' => 'someone@example.com']);

        $this->artisan('procurely:preflight')->expectsOutputToContain('No placeholder user accounts')->assertExitCode(1);
    }

    public function test_a_recent_backup_marker_satisfies_the_backup_check(): void
    {
        $this->productionConfig();
        $dir = sys_get_temp_dir().'/procurely-backup-test-'.uniqid();
        mkdir($dir);
        touch($dir.'/LAST_OK');
        config(['procurely.backup.path' => $dir]);

        $this->artisan('procurely:preflight')->expectsOutputToContain('A backup completed in the last 26 hours')->assertExitCode(0);

        unlink($dir.'/LAST_OK');
        rmdir($dir);
    }
}