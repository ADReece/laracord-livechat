<?php

namespace ADReece\LaracordLiveChat\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use ADReece\LaracordLiveChat\Jobs\MonitorDiscordMessages;
use ADReece\LaracordLiveChat\Jobs\CleanupChatSessions;

class Kernel extends ConsoleKernel
{
    /**
     * Define the package's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Discord monitoring schedule
        if (config('laracord-live-chat.scheduler.discord_monitoring.enabled', true)) {
            $frequency = config('laracord-live-chat.scheduler.discord_monitoring.frequency', 'everyMinute');

            $event = $schedule->job(MonitorDiscordMessages::class);

            // Apply the configured frequency
            switch ($frequency) {
                case 'everyTwoMinutes':
                    $event->everyTwoMinutes();
                    break;
                case 'everyFiveMinutes':
                    $event->everyFiveMinutes();
                    break;
                case 'everyMinute':
                default:
                    $event->everyMinute();
                    break;
            }

            $event->withoutOverlapping();
        }

        // Session cleanup schedule
        if (config('laracord-live-chat.scheduler.cleanup.enabled', true)) {
            $cleanupTime = config('laracord-live-chat.scheduler.cleanup.time', '02:00');

            $schedule->job(CleanupChatSessions::class)
                ->dailyAt($cleanupTime)
                ->withoutOverlapping();
        }
    }
}
