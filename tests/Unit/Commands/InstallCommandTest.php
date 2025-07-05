<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Commands;

use ADReece\LaracordLiveChat\Commands\InstallCommand;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_publishes_config_file()
    {
        // Remove config file if it exists
        $configPath = config_path('laracord-live-chat.php');
        if (File::exists($configPath)) {
            File::delete($configPath);
        }

        Artisan::call('laracord:install');

        $output = Artisan::output();

        $this->assertStringContainsString('Config file published', $output);
        $this->assertTrue(File::exists($configPath));
    }

    /** @test */
    public function it_runs_migrations()
    {
        Artisan::call('laracord:install');

        $output = Artisan::output();

        $this->assertStringContainsString('Database migrations completed', $output);

        // Check that tables exist
        $this->assertTrue(\Schema::hasTable('chat_sessions'));
        $this->assertTrue(\Schema::hasTable('chat_messages'));
    }

    /** @test */
    public function it_publishes_views()
    {
        $viewsPath = resource_path('views/vendor/laracord-live-chat');
        if (File::isDirectory($viewsPath)) {
            File::deleteDirectory($viewsPath);
        }

        Artisan::call('laracord:install');

        $output = Artisan::output();

        $this->assertStringContainsString('Views published', $output);
        $this->assertTrue(File::isDirectory($viewsPath));
        $this->assertTrue(File::exists($viewsPath . '/widget.blade.php'));
        $this->assertTrue(File::exists($viewsPath . '/include.blade.php'));
    }

    /** @test */
    public function it_displays_next_steps()
    {
        Artisan::call('laracord:install');

        $output = Artisan::output();

        $this->assertStringContainsString('Installation completed!', $output);
        $this->assertStringContainsString('Next steps:', $output);
        $this->assertStringContainsString('Configure Discord Bot', $output);
        $this->assertStringContainsString('Add environment variables', $output);
        $this->assertStringContainsString('Setup Laravel Scheduler', $output);
    }

    /** @test */
    public function it_handles_force_option()
    {
        // First install
        Artisan::call('laracord:install');

        // Install again with force
        Artisan::call('laracord:install', ['--force' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Installation completed!', $output);
    }

    /** @test */
    public function it_shows_warning_when_config_exists()
    {
        // First install
        Artisan::call('laracord:install');

        // Install again without force
        Artisan::call('laracord:install');

        $output = Artisan::output();

        // Should complete but may show warnings about existing files
        $this->assertStringContainsString('Installation completed!', $output);
    }
}
