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
composer require swoopy/laracord-live-chat
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

5. Start the Discord message monitor:

```bash
# Run continuously
php artisan laracord-chat:monitor-discord

# Or run once (useful for cron jobs)
php artisan laracord-chat:monitor-discord --once
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

### Setting Up Cron Jobs (Recommended)

Add this to your crontab for automatic message monitoring:

```bash
* * * * * cd /path/to/your/project && php artisan laracord-chat:monitor-discord --once
```

## Configuration

The configuration file `config/laracord-live-chat.php` allows you to customize:

- Discord bot token and server settings
- Channel naming and organization
- Pusher configuration
- Chat widget appearance
- Rate limiting

## License

The MIT License (MIT).
