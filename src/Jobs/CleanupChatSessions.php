<?php

namespace Swoopy\LaracordLiveChat\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Swoopy\LaracordLiveChat\Services\ChatService;

class CleanupChatSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ChatService $chatService)
    {
        $chatService->cleanup();
    }
}
