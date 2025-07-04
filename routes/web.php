<?php

use Illuminate\Support\Facades\Route;
use ADReece\LaracordLiveChat\Http\Controllers\ChatController;
use ADReece\LaracordLiveChat\Http\Controllers\DiscordController;

// Chat API routes
Route::prefix('api/laracord-chat')->group(function () {
    Route::post('sessions', [ChatController::class, 'startSession']);
    Route::post('messages', [ChatController::class, 'sendMessage']);
    Route::get('sessions/{sessionId}', [ChatController::class, 'getSession']);
    Route::get('sessions/{sessionId}/messages', [ChatController::class, 'getMessages']);
    Route::post('sessions/{sessionId}/close', [ChatController::class, 'closeSession']);
    Route::get('sessions/{sessionId}/stats', [ChatController::class, 'getSessionStats']);
});

// Discord webhook route
Route::post('laracord-chat/discord/webhook', [DiscordController::class, 'handleWebhook']);

// Chat widget route
Route::get('laracord-chat/widget', function () {
    return view('laracord-live-chat::widget');
});
