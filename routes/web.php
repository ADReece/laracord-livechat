<?php

use Illuminate\Support\Facades\Route;
use ADReece\LaracordLiveChat\Http\Controllers\ChatController;
use ADReece\LaracordLiveChat\Http\Controllers\DiscordController;

// Chat API routes
Route::prefix('api/laracord-chat')->group(function () {
    Route::post('sessions', [ChatController::class, 'startSession'])->name('laracord.chat.start');
    Route::post('messages', [ChatController::class, 'sendMessage'])->name('laracord.chat.send');
    Route::get('sessions/{sessionId}', [ChatController::class, 'getSession'])->name('laracord.chat.session');
    Route::get('sessions/{sessionId}/messages', [ChatController::class, 'getMessages'])->name('laracord.chat.messages');
    Route::post('sessions/{sessionId}/close', [ChatController::class, 'closeSession'])->name('laracord.chat.close');
    Route::get('sessions/{sessionId}/stats', [ChatController::class, 'getSessionStats'])->name('laracord.chat.stats');
});

// Discord webhook route
Route::post('laracord-chat/discord/webhook', [DiscordController::class, 'handleWebhook'])->name('laracord.discord.webhook');

// Chat widget route
Route::get('laracord-chat/widget', function () {
    return view('laracord-live-chat::widget');
})->name('laracord.chat.widget');
