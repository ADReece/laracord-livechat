<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Services;

use ADReece\LaracordLiveChat\Models\ChatMessage;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Services\DiscordMessageMonitor;
use ADReece\LaracordLiveChat\Services\DiscordService;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;

class DiscordMessageMonitorTest extends TestCase
{
    use RefreshDatabase;

    private $discordService;
    private $monitor;
    private $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->discordService = Mockery::mock(DiscordService::class);
        $this->chatService = Mockery::mock(ChatService::class); // Added missing ChatService mock
        $this->app->instance(DiscordService::class, $this->discordService);
        $this->app->instance(ChatService::class, $this->chatService); // Register ChatService mock

        $this->monitor = new DiscordMessageMonitor($this->discordService, $this->chatService); // Fixed: passing both required arguments
    }

    /** @test */
    public function it_monitors_new_messages_from_discord()
    {
        Event::fake();
        
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        $newMessages = [
            [
                'id' => 'msg1',
                'content' => 'Hello from agent',
                'author' => [
                    'id' => 'agent123',
                    'username' => 'agent',
                    'bot' => false,
                ],
                'timestamp' => Carbon::now()->toISOString(),
            ],
            [
                'id' => 'msg2',
                'content' => 'Another message',
                'author' => [
                    'id' => 'agent123',
                    'username' => 'agent',
                    'bot' => false,
                ],
                'timestamp' => Carbon::now()->addMinutes(1)->toISOString(),
            ],
        ];

        $this->discordService
            ->shouldReceive('getChannelMessages')
            ->with('123456789', Mockery::any())
            ->andReturn($newMessages);

        $result = $this->monitor->monitorMessages();

        $this->assertTrue($result);

        // Check that messages were stored
        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'discord_message_id' => 'msg1',
            'content' => 'Hello from agent',
            'sender_type' => 'agent',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'discord_message_id' => 'msg2',
            'content' => 'Another message',
            'sender_type' => 'agent',
        ]);
    }

    /** @test */
    public function it_ignores_bot_messages()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        $messages = [
            [
                'id' => 'bot_msg',
                'content' => 'Bot message',
                'author' => [
                    'id' => 'bot123',
                    'username' => 'chatbot',
                    'bot' => true,
                ],
                'timestamp' => Carbon::now()->toISOString(),
            ],
        ];

        $this->discordService
            ->shouldReceive('getChannelMessages')
            ->with('123456789', Mockery::any())
            ->andReturn($messages);

        $this->monitor->monitorMessages();

        // Bot message should not be stored
        $this->assertDatabaseMissing('chat_messages', [
            'discord_message_id' => 'bot_msg',
        ]);
    }

    /** @test */
    public function it_ignores_duplicate_messages()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        // Create existing message
        ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'discord_message_id' => 'existing_msg',
            'content' => 'Existing message',
            'sender_type' => 'agent',
        ]);

        $messages = [
            [
                'id' => 'existing_msg',
                'content' => 'Existing message',
                'author' => [
                    'id' => 'agent123',
                    'username' => 'agent',
                    'bot' => false,
                ],
                'timestamp' => Carbon::now()->toISOString(),
            ],
        ];

        $this->discordService
            ->shouldReceive('getChannelMessages')
            ->with('123456789', Mockery::any())
            ->andReturn($messages);

        $this->monitor->monitorMessages();

        // Should only have one message with this ID
        $this->assertEquals(1, ChatMessage::where('discord_message_id', 'existing_msg')->count());
    }

    /** @test */
    public function it_uses_timestamp_filtering()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        // Set last check timestamp
        $lastCheck = Carbon::now()->subMinutes(5);
        Cache::put('discord_last_check', $lastCheck->toISOString());

        $this->discordService
            ->shouldReceive('getChannelMessages')
            ->with('123456789', $lastCheck)
            ->andReturn([]);

        $this->monitor->monitorMessages();

        // Cache should be updated with current timestamp
        $this->assertNotNull(Cache::get('discord_last_check'));
    }

    /** @test */
    public function it_handles_discord_api_errors()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        $this->discordService
            ->shouldReceive('getChannelMessages')
            ->andThrow(new \Exception('Discord API error'));

        $result = $this->monitor->monitorMessages();

        $this->assertFalse($result);
    }

    /** @test */
    public function it_only_monitors_active_sessions()
    {
        $activeSession = ChatSession::factory()->create([
            'discord_channel_id' => '111111111',
            'status' => 'active',
        ]);

        $closedSession = ChatSession::factory()->create([
            'discord_channel_id' => '222222222',
            'status' => 'closed',
        ]);

        $this->discordService
            ->shouldReceive('getChannelMessages')
            ->once()
            ->with('111111111', Mockery::any())
            ->andReturn([]);

        // Should not call for closed session
        $this->discordService
            ->shouldNotReceive('getChannelMessages')
            ->with('222222222', Mockery::any());

        $this->monitor->monitorMessages();
    }

    /** @test */
    public function it_broadcasts_new_agent_messages()
    {
        Event::fake();
        
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        $newMessages = [
            [
                'id' => 'agent_msg',
                'content' => 'Agent response',
                'author' => [
                    'id' => 'agent123',
                    'username' => 'support_agent',
                    'bot' => false,
                ],
                'timestamp' => Carbon::now()->toISOString(),
            ],
        ];

        $this->discordService
            ->shouldReceive('getChannelMessages')
            ->andReturn($newMessages);

        $this->monitor->monitorMessages();

        // Check that MessageSent event was fired
        Event::assertDispatched(\ADReece\LaracordLiveChat\Events\MessageSent::class, function ($event) use ($session) {
            return $event->message->chat_session_id === $session->id &&
                   $event->message->content === 'Agent response' &&
                   $event->message->sender_type === 'agent';
        });
    }

    /** @test */
    public function it_handles_empty_message_responses()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        $this->discordService
            ->shouldReceive('getChannelMessages')
            ->andReturn([]);

        $result = $this->monitor->monitorMessages();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_processes_multiple_sessions()
    {
        $session1 = ChatSession::factory()->create([
            'discord_channel_id' => '111111111',
            'status' => 'active',
        ]);

        $session2 = ChatSession::factory()->create([
            'discord_channel_id' => '222222222',
            'status' => 'active',
        ]);

        $this->discordService
            ->shouldReceive('getChannelMessages')
            ->twice()
            ->andReturn([]);

        $result = $this->monitor->monitorMessages();

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
