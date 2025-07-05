<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Commands;

use ADReece\LaracordLiveChat\Commands\ScheduleStatusCommand;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

class ScheduleStatusCommandTest extends TestCase
{
    /** @test */
    public function it_shows_schedule_status()
    {
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        $this->assertStringContainsString('Laravel Scheduler Status', $output);
        $this->assertStringContainsString('Laracord Live Chat Jobs', $output);
    }

    /** @test */
    public function it_shows_configured_jobs()
    {
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        $this->assertStringContainsString('MonitorDiscordMessages', $output);
        $this->assertStringContainsString('CleanupChatSessions', $output);
    }

    /** @test */
    public function it_shows_job_frequencies()
    {
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        // Should show the configured frequencies
        $this->assertStringContainsString('every minute', $output);
        $this->assertStringContainsString('hourly', $output);
    }

    /** @test */
    public function it_shows_next_run_times()
    {
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        $this->assertStringContainsString('Next Run', $output);
        $this->assertStringContainsString(now()->format('Y-m-d'), $output);
    }

    /** @test */
    public function it_shows_cron_setup_instructions()
    {
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        $this->assertStringContainsString('Cron Configuration', $output);
        $this->assertStringContainsString('* * * * *', $output);
        $this->assertStringContainsString('schedule:run', $output);
    }

    /** @test */
    public function it_shows_configuration_status()
    {
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        $this->assertStringContainsString('Configuration Status', $output);
        $this->assertStringContainsString('Monitoring Enabled', $output);
        $this->assertStringContainsString('Cleanup Enabled', $output);
    }

    /** @test */
    public function it_shows_warning_when_jobs_disabled()
    {
        config([
            'laracord-live-chat.monitoring.enabled' => false,
            'laracord-live-chat.cleanup.enabled' => false,
        ]);

        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        $this->assertStringContainsString('⚠', $output);
        $this->assertStringContainsString('disabled', $output);
    }

    /** @test */
    public function it_shows_verbose_schedule_information()
    {
        Artisan::call('laracord:schedule-status', ['-v' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Detailed Schedule Information', $output);
        $this->assertStringContainsString('Command', $output);
        $this->assertStringContainsString('Expression', $output);
    }

    /** @test */
    public function it_validates_scheduler_configuration()
    {
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        // Should check if scheduler is properly configured
        $this->assertStringContainsString('Scheduler Check', $output);
    }

    /** @test */
    public function it_shows_last_run_information()
    {
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        $this->assertStringContainsString('Last Run', $output);
    }

    /** @test */
    public function it_handles_schedule_errors_gracefully()
    {
        // This test ensures the command doesn't crash if there are issues
        // accessing schedule information
        
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        // Should complete without errors
        $this->assertStringContainsString('Laravel Scheduler Status', $output);
    }

    /** @test */
    public function it_shows_environment_specific_information()
    {
        app()->detectEnvironment(function () {
            return 'testing';
        });

        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        $this->assertStringContainsString('Environment: testing', $output);
    }

    /** @test */
    public function it_provides_troubleshooting_tips()
    {
        Artisan::call('laracord:schedule-status');

        $output = Artisan::output();

        $this->assertStringContainsString('Troubleshooting', $output);
        $this->assertStringContainsString('Make sure cron is configured', $output);
    }
}
