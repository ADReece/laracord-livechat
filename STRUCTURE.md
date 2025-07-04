# Package File Structure

```
laracord-live-chat/
├── src/
│   ├── Commands/
│   │   ├── InstallCommand.php
│   │   └── DiscordBotCommand.php
│   ├── Events/
│   │   ├── MessageSent.php
│   │   ├── SessionStarted.php
│   │   └── SessionClosed.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ChatController.php
│   │   │   └── DiscordController.php
│   │   └── Requests/
│   │       ├── StartSessionRequest.php
│   │       └── SendMessageRequest.php
│   ├── Jobs/
│   │   └── CleanupChatSessions.php
│   ├── Models/
│   │   ├── ChatSession.php
│   │   └── ChatMessage.php
│   ├── Services/
│   │   ├── ChatService.php
│   │   └── DiscordService.php
│   └── LaracordLiveChatServiceProvider.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000001_create_chat_sessions_table.php
│       └── 2024_01_01_000002_create_chat_messages_table.php
├── resources/
│   └── views/
│       ├── widget.blade.php
│       └── include.blade.php
├── routes/
│   └── web.php
├── config/
│   └── laracord-live-chat.php
├── composer.json
├── README.md
├── USAGE.md
└── LICENSE
```

## Installation Steps

1. **Install via Composer** (when published):
   ```bash
   composer require adreece/laracord-live-chat
   ```

2. **Run the installation command**:
   ```bash
   php artisan laracord-chat:install
   ```

3. **Configure your environment variables**:
   ```env
   DISCORD_WEBHOOK_URL=your_webhook_url
   DISCORD_BOT_TOKEN=your_bot_token
   DISCORD_GUILD_ID=your_guild_id
   PUSHER_APP_ID=your_pusher_app_id
   PUSHER_APP_KEY=your_pusher_key
   PUSHER_APP_SECRET=your_pusher_secret
   PUSHER_APP_CLUSTER=your_pusher_cluster
   BROADCAST_DRIVER=pusher
   ```

4. **Set up Discord commands**:
   ```bash
   php artisan laracord-chat:setup-discord
   ```

5. **Include the widget in your views**:
   ```blade
   @include('laracord-live-chat::include')
   ```

## Features Implemented

✅ **Customer Chat Widget**
- Modern, responsive design
- Real-time messaging with Pusher
- Session management
- Message history

✅ **Discord Integration**
- Webhook notifications for new messages and sessions
- Slash commands for replying (/reply, /sessions, /close)
- Rich embeds with customer information

✅ **Backend Services**
- Complete API for chat operations
- Database models and migrations
- Event system for extensibility

✅ **Admin Features**
- Session management
- Message statistics
- Automatic cleanup of old sessions

✅ **Developer Experience**
- Easy installation with Artisan commands
- Comprehensive documentation
- Customizable configuration
- Publishable views for customization

The package is now complete and ready for use!
