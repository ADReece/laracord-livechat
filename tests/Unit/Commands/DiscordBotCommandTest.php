<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Commands;

use ADReece\LaracordLiveChat\Commands\DiscordBotCommand;
use ADReece\LaracordLiveChat\Services\DiscordService;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Mockery;

class DiscordBotCommandTest extends TestCase
{
    private $discordService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->discordService = Mockery::mock(DiscordService::class);
        $this->app->instance(DiscordService::class, $this->discordService);
    }

    /** @test */
    public function it_shows_bot_information()
    {
        $botInfo = [
            'id' => '123456789',
            'username' => 'LaracordBot',
            'discriminator' => '0001',
            'verified' => true,
        ];

        $this->discordService
            ->shouldReceive('getBotInfo')
            ->once()
            ->andReturn($botInfo);

        Artisan::call('laracord:discord-bot');

        $output = Artisan::output();

        $this->assertStringContainsString('Discord Bot Information', $output);
        $this->assertStringContainsString('Bot ID: 123456789', $output);
        $this->assertStringContainsString('Username: LaracordBot', $output);
        $this->assertStringContainsString('Verified: Yes', $output);
    }

    /** @test */
    public function it_shows_guild_information()
    {
        $botInfo = [
            'id' => '123456789',
            'username' => 'LaracordBot',
            'verified' => true,
        ];

        $guildInfo = [
            'id' => '987654321',
            'name' => 'Support Server',
            'member_count' => 150,
            'permissions' => ['SEND_MESSAGES', 'MANAGE_CHANNELS'],
        ];

        $this->discordService
            ->shouldReceive('getBotInfo')
            ->once()
            ->andReturn($botInfo);

        $this->discordService
            ->shouldReceive('getGuildInfo')
            ->once()
            ->andReturn($guildInfo);

        Artisan::call('laracord:discord-bot');

        $output = Artisan::output();

        $this->assertStringContainsString('Guild Information', $output);
        $this->assertStringContainsString('Guild ID: 987654321', $output);
        $this->assertStringContainsString('Name: Support Server', $output);
        $this->assertStringContainsString('Members: 150', $output);
    }

    /** @test */
    public function it_handles_discord_api_errors()
    {
        $this->discordService
            ->shouldReceive('getBotInfo')
            ->once()
            ->andThrow(new \Exception('Discord API error'));

        Artisan::call('laracord:discord-bot');

        $output = Artisan::output();

        $this->assertStringContainsString('Error connecting to Discord', $output);
        $this->assertStringContainsString('Discord API error', $output);
    }

    /** @test */
    public function it_checks_required_permissions()
    {
        $botInfo = [
            'id' => '123456789',
            'username' => 'LaracordBot',
            'verified' => true,
        ];

        $guildInfo = [
            'id' => '987654321',
            'name' => 'Support Server',
            'member_count' => 150,
            'permissions' => ['SEND_MESSAGES'], // Missing MANAGE_CHANNELS
        ];

        $this->discordService
            ->shouldReceive('getBotInfo')
            ->once()
            ->andReturn($botInfo);

        $this->discordService
            ->shouldReceive('getGuildInfo')
            ->once()
            ->andReturn($guildInfo);

        Artisan::call('laracord:discord-bot');

        $output = Artisan::output();

        $this->assertStringContainsString('Permission Check', $output);
        $this->assertStringContainsString('SEND_MESSAGES: ✓', $output);
        $this->assertStringContainsString('MANAGE_CHANNELS: ✗', $output);
    }

    /** @test */
    public function it_shows_webhook_url_if_configured()
    {
        config(['laracord-live-chat.discord.webhook_url' => 'https://example.com/webhook']);

        $botInfo = [
            'id' => '123456789',
            'username' => 'LaracordBot',
            'verified' => true,
        ];

        $this->discordService
            ->shouldReceive('getBotInfo')
            ->once()
            ->andReturn($botInfo);

        $this->discordService
            ->shouldReceive('getGuildInfo')
            ->once()
            ->andReturn([]);

        Artisan::call('laracord:discord-bot');

        $output = Artisan::output();

        $this->assertStringContainsString('Webhook Configuration', $output);
        $this->assertStringContainsString('https://example.com/webhook', $output);
    }

    /** @test */
    public function it_shows_configuration_warnings()
    {
        // Clear Discord config
        config([
            'laracord-live-chat.discord.bot_token' => null,
            'laracord-live-chat.discord.guild_id' => null,
        ]);

        Artisan::call('laracord:discord-bot');

        $output = Artisan::output();

        $this->assertStringContainsString('Configuration Issues', $output);
        $this->assertStringContainsString('DISCORD_BOT_TOKEN not set', $output);
        $this->assertStringContainsString('DISCORD_GUILD_ID not set', $output);
    }

    /** @test */
    public function it_tests_channel_creation()
    {
        $botInfo = [
            'id' => '123456789',
            'username' => 'LaracordBot',
            'verified' => true,
        ];

        $this->discordService
            ->shouldReceive('getBotInfo')
            ->once()
            ->andReturn($botInfo);

        $this->discordService
            ->shouldReceive('getGuildInfo')
            ->once()
            ->andReturn([]);

        $this->discordService
            ->shouldReceive('createChannel')
            ->with('test-channel-' . date('His'))
            ->once()
            ->andReturn([
                'id' => '555555555',
                'name' => 'test-channel-123456',
            ]);

        $this->discordService
            ->shouldReceive('deleteChannel')
            ->with('555555555')
            ->once()
            ->andReturn(true);

        Artisan::call('laracord:discord-bot', ['--test-channel' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Channel Test', $output);
        $this->assertStringContainsString('✓ Channel created successfully', $output);
        $this->assertStringContainsString('✓ Channel deleted successfully', $output);
    }

    /** @test */
    public function it_handles_channel_test_failures()
    {
        $botInfo = [
            'id' => '123456789',
            'username' => 'LaracordBot',
            'verified' => true,
        ];

        $this->discordService
            ->shouldReceive('getBotInfo')
            ->once()
            ->andReturn($botInfo);

        $this->discordService
            ->shouldReceive('getGuildInfo')
            ->once()
            ->andReturn([]);

        $this->discordService
            ->shouldReceive('createChannel')
            ->once()
            ->andThrow(new \Exception('Insufficient permissions'));

        Artisan::call('laracord:discord-bot', ['--test-channel' => true]);

        $output = Artisan::output();

        $this->assertStringContainsString('Channel Test', $output);
        $this->assertStringContainsString('✗ Channel creation failed', $output);
        $this->assertStringContainsString('Insufficient permissions', $output);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
