<?php

namespace ADReece\LaracordLiveChat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ADReece\LaracordLiveChat\Services\DiscordMessageMonitor;

class MonitorDiscordMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(DiscordMessageMonitor $monitor)
    {
        $monitor->monitorActiveChannels();
    }
}
