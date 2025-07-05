# Laracord Live Chat

A Laravel package that enables live chat functionality where customer messages are received in Discord and responses can be sent back through Discord.

## Features

- Customer live chat interface
- **Dedicated Discord channels** created for each chat session
- **Real-time message sync** between Discord channels and customer chat
- Real-time message updates using WebSockets (Pusher)
- Message history and session management
- Customizable chat widget
- Automatic channel cleanup when chats end

## Installation

1. Install the package via Composer:

```bash
composer require adreece/laracord-live-chat
```

2. Run the installation command:

```bash
php artisan laracord-chat:install
```

3. Configure your environment variables:

```env
DISCORD_BOT_TOKEN=your_discord_bot_token
DISCORD_GUILD_ID=your_discord_server_id
DISCORD_CATEGORY_ID=your_category_id_optional
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=your_pusher_cluster
BROADCAST_DRIVER=pusher
```

4. Set up Discord bot commands:

```bash
php artisan laracord-chat:setup-discord
```

5. **Important**: Set up Laravel's task scheduler (required for Discord monitoring):

Add this to your server's crontab:
```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

6. Check that everything is working:

```bash
php artisan laracord-chat:schedule-status
```

## Usage

### Customer Chat Widget

Include the chat widget in your Blade templates:

```blade
@include('laracord-live-chat::include')
```

### How It Works

1. **Customer starts a chat** → A dedicated Discord channel is created
2. **Customer sends messages** → Messages appear in the Discord channel
3. **Agents reply in Discord** → Simply type messages in the channel
4. **Customer sees replies instantly** → Real-time updates via WebSockets
5. **Chat ends** → Discord channel is automatically deleted

### Discord Management

Available slash commands:

- `/sessions` - List active chat sessions with their channels
- `/close [session_id]` - Close a chat session and delete its channel

### Scheduler Configuration

**Important**: You must manually configure the scheduler in your Laravel application's `app/Console/Kernel.php` file.

Add the following to your `schedule` method:

```php
protected function schedule(Schedule $schedule)
{
    // Monitor Discord channels for new messages
    if (config('laracord-live-chat.scheduler.discord_monitoring.enabled', true)) {
        $schedule->job(\ADReece\LaracordLiveChat\Jobs\MonitorDiscordMessages::class)
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();
    }

    // Clean up old chat sessions
    if (config('laracord-live-chat.scheduler.cleanup.enabled', true)) {
        $schedule->job(\ADReece\LaracordLiveChat\Jobs\CleanupChatSessions::class)
                 ->dailyAt(config('laracord-live-chat.scheduler.cleanup.time', '02:00'));
    }
}
```

You can customize the scheduler behavior in your `.env` file:

```env
LARACORD_DISCORD_MONITORING_ENABLED=true
LARACORD_DISCORD_MONITORING_FREQUENCY=everyMinute  # everyMinute, everyTwoMinutes, everyFiveMinutes
LARACORD_CLEANUP_ENABLED=true
LARACORD_CLEANUP_TIME=02:00
```

Check scheduler status anytime:
```bash
php artisan laracord-chat:schedule-status
```

## Configuration

The configuration file `config/laracord-live-chat.php` allows you to customize:

- Discord bot token and server settings
- Channel naming and organization
- Pusher configuration
- Chat widget appearance
- Rate limiting

## Testing

This package includes a comprehensive test suite covering all major functionality.

### Running Tests

#### Quick Start
```bash
# Run all tests
./run-tests.sh

# Run with coverage report
./run-tests.sh --coverage

# Run specific test suites
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Feature
```

#### Manual Testing
```bash
# Install test dependencies
composer install

# Run unit tests only
./vendor/bin/phpunit tests/Unit

# Run feature tests only
./vendor/bin/phpunit tests/Feature

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Test Coverage

The test suite covers:

- **Models**: ChatSession, ChatMessage with factories and relationships
- **Services**: ChatService, DiscordService, DiscordMessageMonitor with mocked HTTP calls
- **HTTP Controllers**: ChatController, DiscordController with request/response testing
- **Jobs**: MonitorDiscordMessages, CleanupChatSessions with queue testing
- **Commands**: Install, Discord Bot setup, Monitor, Schedule Status
- **Events**: MessageSent, SessionStarted, SessionClosed with broadcasting
- **HTTP Requests**: StartSessionRequest, SendMessageRequest validation
- **Service Provider**: Configuration, routes, commands registration
- **Integration**: End-to-end chat workflows and Discord synchronization

### Test Architecture

- **Unit Tests**: Test individual components in isolation using mocks
- **Feature Tests**: Test HTTP endpoints and complete workflows
- **Integration Tests**: Test entire chat scenarios from start to finish
- **Factories**: Generate realistic test data for models
- **Mocking**: Discord API calls and external services are mocked for reliable testing

### Test Configuration

Tests use an in-memory SQLite database and mock external services:

- Discord API calls are mocked using Mockery
- Broadcasting events are faked using Laravel's Event fake
- Database transactions ensure test isolation
- Orchestra Testbench provides Laravel testing environment

## License

The MIT License (MIT).
