# Example Usage

## Basic Integration

### 1. Include the Chat Widget in Your Blade Template

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Your Website</title>
</head>
<body>
    <!-- Your website content -->
    
    <!-- Include the chat widget at the bottom of your page -->
    @include('laracord-live-chat::include')
</body>
</html>
```

### 2. Environment Configuration

Add these variables to your `.env` file:

```env
# Discord Configuration
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/your_webhook_url
DISCORD_BOT_TOKEN=your_bot_token_here
DISCORD_GUILD_ID=your_guild_id
DISCORD_CHANNEL_ID=your_channel_id

# Pusher Configuration (for real-time messaging)
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=your_pusher_cluster

# Laravel Broadcasting
BROADCAST_DRIVER=pusher
```

### 3. Discord Bot Setup

1. Create a Discord application at https://discord.com/developers/applications
2. Create a bot and get the bot token
3. Invite the bot to your server with appropriate permissions
4. Create a webhook in your Discord channel
5. Run the setup command:

```bash
php artisan laracord-chat:setup-discord
```

### 4. Available Artisan Commands

```bash
# Install the package (run after composer install)
php artisan laracord-chat:install

# Set up Discord slash commands
php artisan laracord-chat:setup-discord

# Clean up old sessions and messages (add to scheduler)
php artisan schedule:run
```

### 5. Schedule Cleanup (Optional)

Add this to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->job(new \Swoopy\LaracordLiveChat\Jobs\CleanupChatSessions)
             ->daily();
}
```

## Advanced Usage

### Custom Styling

You can publish and customize the views:

```bash
php artisan vendor:publish --provider="Swoopy\LaracordLiveChat\LaracordLiveChatServiceProvider" --tag="views"
```

Then edit the files in `resources/views/vendor/laracord-live-chat/`

### Programmatic Usage

```php
use Swoopy\LaracordLiveChat\Services\ChatService;

// Get the chat service
$chatService = app(ChatService::class);

// Create a session programmatically
$session = $chatService->createSession([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Mozilla/5.0...',
]);

// Send a message from customer
$message = $chatService->sendCustomerMessage(
    $session->id,
    'Hello, I need help with my order',
    'John Doe'
);

// Send a message from agent
$reply = $chatService->sendAgentMessage(
    $session->id,
    'Hi John! I can help you with that. What is your order number?',
    'Support Agent'
);

// Get session with messages
$session = $chatService->getSession($sessionId);

// Close session
$chatService->closeSession($sessionId);
```

### Event Listeners

You can listen to chat events:

```php
// In your EventServiceProvider
protected $listen = [
    \Swoopy\LaracordLiveChat\Events\SessionStarted::class => [
        YourSessionStartedListener::class,
    ],
    \Swoopy\LaracordLiveChat\Events\MessageSent::class => [
        YourMessageSentListener::class,
    ],
    \Swoopy\LaracordLiveChat\Events\SessionClosed::class => [
        YourSessionClosedListener::class,
    ],
];
```

## Discord Commands

Once set up, you can use these commands in your Discord server:

- `/reply <session_id> <message>` - Reply to a customer
- `/sessions` - List all active chat sessions  
- `/close <session_id>` - Close a chat session

## Troubleshooting

### Common Issues

1. **Chat widget not showing**: Make sure you've included the widget view and published assets
2. **Messages not sending to Discord**: Check your webhook URL and bot token
3. **Real-time updates not working**: Verify your Pusher configuration
4. **Database errors**: Make sure you've run the migrations

### Debug Mode

Add this to your `.env` for more detailed logging:

```env
LOG_LEVEL=debug
```

Check `storage/logs/laravel.log` for Discord webhook and other errors.
