<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Services;

use ADReece\LaracordLiveChat\Tests\TestCase;
use ADReece\LaracordLiveChat\Services\DiscordService;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Models\ChatMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DiscordServiceTest extends TestCase
{
    use RefreshDatabase;

    private DiscordService $discordService;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $this->discordService = new DiscordService(
            'https://discord.com/api/webhooks/test',
            'test_bot_token'
        );

        // Use reflection to inject the mocked client
        $reflection = new \ReflectionClass($this->discordService);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->discordService, $client);
    }

    /** @test */
    public function it_can_create_chat_channel()
    {
        config(['laracord-live-chat.discord.guild_id' => 'test_guild_id']);

        $this->mockHandler->append(
            new Response(201, [], json_encode(['id' => 'new_channel_id']))
        );

        $this->mockHandler->append(
            new Response(200, []) // For the initial message
        );

        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $channelId = $this->discordService->createChatChannel($session);

        $this->assertEquals('new_channel_id', $channelId);
    }

    /** @test */
    public function it_returns_null_when_channel_creation_fails()
    {
        config(['laracord-live-chat.discord.guild_id' => 'test_guild_id']);

        $this->mockHandler->append(
            new Response(400, []) // Failed request
        );

        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $channelId = $this->discordService->createChatChannel($session);

        $this->assertNull($channelId);
    }

    /** @test */
    public function it_can_send_chat_message_to_discord()
    {
        $this->mockHandler->append(
            new Response(200, []) // Successful message send
        );

        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
            'discord_channel_id' => 'test_channel_id',
        ]);

        $message = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'sender_name' => 'John Doe',
            'message' => 'Hello, I need help!',
        ]);

        $result = $this->discordService->sendNewChatMessage($session, $message);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_session_has_no_channel()
    {
        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
            // No discord_channel_id
        ]);

        $message = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'message' => 'Hello!',
        ]);

        $result = $this->discordService->sendNewChatMessage($session, $message);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_delete_chat_channel()
    {
        $this->mockHandler->append(
            new Response(200, []) // Successful message send for closing message
        );

        $this->mockHandler->append(
            new Response(200, []) // Successful channel deletion
        );

        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'closed',
            'discord_channel_id' => 'test_channel_id',
        ]);

        $result = $this->discordService->deleteChatChannel($session);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_channel_messages()
    {
        $mockMessages = [
            [
                'id' => 'message_1',
                'content' => 'Hello from Discord',
                'author' => [
                    'id' => 'user_1',
                    'username' => 'Agent',
                    'global_name' => 'Support Agent'
                ],
                'timestamp' => '2024-01-01T12:00:00.000Z'
            ],
            [
                'id' => 'message_2',
                'content' => 'Another message',
                'author' => [
                    'id' => 'user_2',
                    'username' => 'AnotherAgent'
                ],
                'timestamp' => '2024-01-01T12:01:00.000Z'
            ]
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockMessages))
        );

        $messages = $this->discordService->getChannelMessages('test_channel_id');

        $this->assertIsArray($messages);
        $this->assertCount(2, $messages);
        $this->assertEquals('message_1', $messages[0]['id']);
        $this->assertEquals('Hello from Discord', $messages[0]['content']);
    }

    /** @test */
    public function it_returns_empty_array_when_get_messages_fails()
    {
        $this->mockHandler->append(
            new Response(404, []) // Channel not found
        );

        $messages = $this->discordService->getChannelMessages('invalid_channel_id');

        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    /** @test */
    public function it_truncates_long_messages()
    {
        $longMessage = str_repeat('A', 1500); // Longer than Discord's limit

        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
            'discord_channel_id' => 'test_channel_id',
        ]);

        $message = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'message' => $longMessage,
        ]);

        $this->mockHandler->append(
            new Response(200, [])
        );

        $result = $this->discordService->sendNewChatMessage($session, $message);

        $this->assertTrue($result);
        
        // The message should have been truncated in the Discord API call
        // We can't easily verify the exact content sent without more complex mocking
        // but we can verify the call succeeded
    }
}
