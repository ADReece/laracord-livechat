<?php

namespace ADReece\LaracordLiveChat\Tests\Feature;

use ADReece\LaracordLiveChat\Tests\TestCase;
use ADReece\LaracordLiveChat\LaracordLiveChatServiceProvider;
use ADReece\LaracordLiveChat\Jobs\MonitorDiscordMessages;
use ADReece\LaracordLiveChat\Jobs\CleanupChatSessions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SchedulerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function scheduler_automatically_registers_tasks_on_boot()
    {
        // Set configuration for enabled tasks
        config([
            'laracord-live-chat.scheduler.discord_monitoring.enabled' => true,
            'laracord-live-chat.scheduler.discord_monitoring.frequency' => 'everyMinute',
            'laracord-live-chat.scheduler.cleanup.enabled' => true,
            'laracord-live-chat.scheduler.cleanup.time' => '02:00',
        ]);

        // Get the schedule instance that would be used in production
        $schedule = app(Schedule::class);

        // Boot a fresh service provider to trigger scheduler registration
        $provider = new LaracordLiveChatServiceProvider(app());
        $provider->boot();

        // Check that tasks were registered
        $events = $schedule->events();

        $hasMonitoringJob = collect($events)->contains(function ($event) {
            return str_contains($event->command ?? '', MonitorDiscordMessages::class);
        });

        $hasCleanupJob = collect($events)->contains(function ($event) {
            return str_contains($event->command ?? '', CleanupChatSessions::class);
        });

        $this->assertTrue($hasMonitoringJob, 'MonitorDiscordMessages should be automatically scheduled');
        $this->assertTrue($hasCleanupJob, 'CleanupChatSessions should be automatically scheduled');
    }

    /** @test */
    public function scheduler_respects_configuration_changes()
    {
        // Initially disable monitoring
        config([
            'laracord-live-chat.scheduler.discord_monitoring.enabled' => false,
            'laracord-live-chat.scheduler.cleanup.enabled' => true,
        ]);

        $schedule = app(Schedule::class);
        $provider = new LaracordLiveChatServiceProvider(app());
        $provider->registerScheduledTasks($schedule);

        $events = $schedule->events();

        $hasMonitoringJob = collect($events)->contains(function ($event) {
            return str_contains($event->command ?? '', MonitorDiscordMessages::class);
        });

        $this->assertFalse($hasMonitoringJob, 'MonitorDiscordMessages should not be scheduled when disabled');

        // Now enable monitoring and test again with fresh schedule
        config(['laracord-live-chat.scheduler.discord_monitoring.enabled' => true]);

        $freshSchedule = app(Schedule::class);
        $provider->registerScheduledTasks($freshSchedule);

        $newEvents = $freshSchedule->events();

        $hasMonitoringJobNow = collect($newEvents)->contains(function ($event) {
            return str_contains($event->command ?? '', MonitorDiscordMessages::class);
        });

        $this->assertTrue($hasMonitoringJobNow, 'MonitorDiscordMessages should be scheduled when enabled');
    }

    /** @test */
    public function jobs_can_be_executed_without_errors()
    {
        // Mock the services to prevent actual Discord API calls
        $this->mock(\ADReece\LaracordLiveChat\Services\DiscordMessageMonitor::class, function ($mock) {
            $mock->shouldReceive('monitorActiveChannels')->andReturn(true);
        });

        $this->mock(\ADReece\LaracordLiveChat\Services\ChatService::class, function ($mock) {
            $mock->shouldReceive('cleanup')->andReturn(true);
        });

        // Test that jobs can be dispatched and executed
        $monitorJob = new MonitorDiscordMessages();
        $cleanupJob = new CleanupChatSessions();

        // These should not throw exceptions
        $monitorJob->handle(app(\ADReece\LaracordLiveChat\Services\DiscordMessageMonitor::class));
        $cleanupJob->handle(app(\ADReece\LaracordLiveChat\Services\ChatService::class));

        $this->assertTrue(true, 'Jobs executed without errors');
    }

    /** @test */
    public function static_schedule_with_method_works()
    {
        config([
            'laracord-live-chat.scheduler.discord_monitoring.enabled' => true,
            'laracord-live-chat.scheduler.cleanup.enabled' => true,
        ]);

        $schedule = app(Schedule::class);

        // Use the static method
        LaracordLiveChatServiceProvider::scheduleWith($schedule);

        $events = $schedule->events();

        $this->assertGreaterThan(0, count($events), 'Static scheduleWith method should register tasks');
    }

    /** @test */
    public function scheduled_events_have_proper_frequencies()
    {
        $testCases = [
            ['everyMinute', '* * * * *'],
            ['everyTwoMinutes', '*/2 * * * *'],
            ['everyFiveMinutes', '*/5 * * * *'],
        ];

        foreach ($testCases as [$frequency, $expectedCron]) {
            config([
                'laracord-live-chat.scheduler.discord_monitoring.enabled' => true,
                'laracord-live-chat.scheduler.discord_monitoring.frequency' => $frequency,
            ]);

            $schedule = app(Schedule::class);
            $provider = new LaracordLiveChatServiceProvider(app());
            $provider->registerScheduledTasks($schedule);

            $monitoringEvent = collect($schedule->events())->first(function ($event) {
                return str_contains($event->command ?? '', MonitorDiscordMessages::class);
            });

            $this->assertNotNull($monitoringEvent, "Event should exist for frequency: {$frequency}");
            $this->assertEquals($expectedCron, $monitoringEvent->expression, "Cron expression should match for frequency: {$frequency}");

            // Clear schedule for next test
            $schedule = app(Schedule::class);
        }
    }
}
