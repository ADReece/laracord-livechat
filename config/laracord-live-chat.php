<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Discord Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Discord webhook and bot integration
    |
    */
    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        'bot_token' => env('DISCORD_BOT_TOKEN'),
        'guild_id' => env('DISCORD_GUILD_ID'),
        'category_id' => env('DISCORD_CATEGORY_ID'), // Optional: Category for chat channels
        'channel_prefix' => env('DISCORD_CHANNEL_PREFIX', 'chat'), // Prefix for channel names
    ],

    /*
    |--------------------------------------------------------------------------
    | Pusher Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for real-time messaging via Pusher
    |
    */
    'pusher' => [
        'app_id' => env('PUSHER_APP_ID'),
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'cluster' => env('PUSHER_APP_CLUSTER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Widget Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the appearance and behavior of the chat widget
    |
    */
    'widget' => [
        'title' => 'Live Chat Support',
        'placeholder' => 'Type your message...',
        'welcome_message' => 'Hello! How can we help you today?',
        'offline_message' => 'We are currently offline. Please leave a message and we\'ll get back to you.',
        'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
        'theme' => 'light', // light, dark
        'avatar_url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for chat messages
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'max_messages_per_minute' => 10,
        'max_messages_per_hour' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how chat sessions and messages are stored
    |
    */
    'storage' => [
        'session_lifetime' => 24, // hours
        'message_retention' => 30, // days
    ],
];
