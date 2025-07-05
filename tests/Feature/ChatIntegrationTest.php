<?php

namespace ADReece\LaracordLiveChat\Tests\Feature;

use ADReece\LaracordLiveChat\Models\ChatMessage;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Services\ChatService;
use ADReece\LaracordLiveChat\Services\DiscordService;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;

class ChatIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private $discordService;
    private $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->discordService = Mockery::mock(DiscordService::class);
        $this->app->instance(DiscordService::class, $this->discordService);
        
        $this->chatService = app(ChatService::class);
    }

    /** @test */
    public function it_creates_complete_chat_session_workflow()
    {
        Event::fake();

        // Mock Discord service responses
        $this->discordService
            ->shouldReceive('createChannel')
            ->once()
            ->andReturn([
                'id' => '123456789',
                'name' => 'chat-john-doe-1234',
            ]);

        $this->discordService
            ->shouldReceive('sendMessage')
            ->times(2) // Initial message + customer message
            ->andReturn(['id' => 'msg123']);

        // Start a new chat session
        $sessionData = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'initial_message' => 'I need help with my order',
        ];

        $session = $this->chatService->startSession($sessionData);

        // Verify session was created
        $this->assertInstanceOf(ChatSession::class, $session);
        $this->assertEquals('John Doe', $session->customer_name);
        $this->assertEquals('john@example.com', $session->customer_email);
        $this->assertEquals('active', $session->status);
        $this->assertEquals('123456789', $session->discord_channel_id);

        // Verify initial message was created
        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'content' => 'I need help with my order',
            'sender_type' => 'customer',
        ]);

        // Send another message from customer
        $customerMessage = $this->chatService->sendMessage($session->id, [
            'content' => 'My order number is #12345',
            'sender_type' => 'customer',
        ]);

        $this->assertInstanceOf(ChatMessage::class, $customerMessage);
        $this->assertEquals('My order number is #12345', $customerMessage->content);

        // Verify events were fired
        Event::assertDispatched(\ADReece\LaracordLiveChat\Events\SessionStarted::class);
        Event::assertDispatched(\ADReece\LaracordLiveChat\Events\MessageSent::class);
    }

    /** @test */
    public function it_handles_agent_replies_from_discord()
    {
        Event::fake();

        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        // Simulate agent reply from Discord webhook
        $webhookData = [
            'id' => 'agent_msg_123',
            'content' => 'Hi John, I can help you with that order.',
            'channel_id' => '123456789',
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $webhookData);

        $response->assertStatus(200);

        // Verify agent message was stored
        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'discord_message_id' => 'agent_msg_123',
            'content' => 'Hi John, I can help you with that order.',
            'sender_type' => 'agent',
        ]);

        // Verify event was fired for real-time updates
        Event::assertDispatched(\ADReece\LaracordLiveChat\Events\MessageSent::class);
    }

    /** @test */
    public function it_closes_session_and_cleans_up_discord_channel()
    {
        Event::fake();

        $session = ChatSession::factory()->active()->create([
            'discord_channel_id' => '123456789',
        ]);

        // Add some messages to the session
        $session->messages()->createMany([
            ['content' => 'Hello', 'sender_type' => 'customer'],
            ['content' => 'Hi there!', 'sender_type' => 'agent'],
        ]);

        $this->discordService
            ->shouldReceive('deleteChannel')
            ->once()
            ->with('123456789')
            ->andReturn(true);

        // Close the session
        $closedSession = $this->chatService->closeSession($session->id);

        $this->assertEquals('closed', $closedSession->status);
        $this->assertNotNull($closedSession->closed_at);

        // Verify SessionClosed event was fired
        Event::assertDispatched(\ADReece\LaracordLiveChat\Events\SessionClosed::class);
    }

    /** @test */
    public function it_handles_end_to_end_chat_conversation()
    {
        Event::fake();

        // Mock Discord responses for the entire conversation
        $this->discordService
            ->shouldReceive('createChannel')
            ->once()
            ->andReturn(['id' => '987654321', 'name' => 'chat-alice-5678']);

        $this->discordService
            ->shouldReceive('sendMessage')
            ->times(3) // Initial + 2 customer messages
            ->andReturn(['id' => 'msg123']);

        $this->discordService
            ->shouldReceive('deleteChannel')
            ->once()
            ->andReturn(true);

        // 1. Start session
        $session = $this->chatService->startSession([
            'customer_name' => 'Alice Smith',
            'customer_email' => 'alice@example.com',
            'initial_message' => 'I have a billing question',
        ]);

        // 2. Customer sends follow-up
        $this->chatService->sendMessage($session->id, [
            'content' => 'I was charged twice for the same item',
            'sender_type' => 'customer',
        ]);

        // 3. Agent responds via Discord webhook
        $this->postJson(route('laracord.discord.webhook'), [
            'id' => 'agent_reply_1',
            'content' => 'Let me check your account for duplicate charges.',
            'channel_id' => '987654321',
            'author' => [
                'id' => 'agent789',
                'username' => 'billing_support',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ]);

        // 4. Customer responds
        $this->chatService->sendMessage($session->id, [
            'content' => 'Thank you for checking!',
            'sender_type' => 'customer',
        ]);

        // 5. Agent resolves and closes
        $this->postJson(route('laracord.discord.webhook'), [
            'id' => 'agent_reply_2',
            'content' => 'I found the duplicate charge and have issued a refund. The session will now be closed.',
            'channel_id' => '987654321',
            'author' => [
                'id' => 'agent789',
                'username' => 'billing_support',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ]);

        // 6. Close session
        $this->chatService->closeSession($session->id);

        // Verify final state
        $session->refresh();
        $this->assertEquals('closed', $session->status);
        $this->assertEquals(5, $session->messages()->count()); // 3 customer + 2 agent

        // Verify all events were fired
        Event::assertDispatched(\ADReece\LaracordLiveChat\Events\SessionStarted::class);
        Event::assertDispatched(\ADReece\LaracordLiveChat\Events\MessageSent::class);
        Event::assertDispatched(\ADReece\LaracordLiveChat\Events\SessionClosed::class);
    }

    /** @test */
    public function it_handles_session_timeout_and_cleanup()
    {
        // Create old inactive session
        $oldSession = ChatSession::factory()->create([
            'discord_channel_id' => '111111111',
            'status' => 'active',
            'last_activity' => now()->subHours(2),
        ]);

        // Create recent active session
        $activeSession = ChatSession::factory()->create([
            'discord_channel_id' => '222222222',
            'status' => 'active',
            'last_activity' => now()->subMinutes(10),
        ]);

        $this->discordService
            ->shouldReceive('deleteChannel')
            ->once()
            ->with('111111111')
            ->andReturn(true);

        // Run cleanup job
        $cleanupJob = new \ADReece\LaracordLiveChat\Jobs\CleanupChatSessions();
        $cleanupJob->handle($this->discordService);

        // Verify old session was closed
        $oldSession->refresh();
        $this->assertEquals('closed', $oldSession->status);

        // Verify active session remains active
        $activeSession->refresh();
        $this->assertEquals('active', $activeSession->status);
    }

    /** @test */
    public function it_prevents_sending_messages_to_closed_sessions()
    {
        $closedSession = ChatSession::factory()->closed()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot send message to closed session');

        $this->chatService->sendMessage($closedSession->id, [
            'content' => 'This should fail',
            'sender_type' => 'customer',
        ]);
    }

    /** @test */
    public function it_handles_concurrent_messages_correctly()
    {
        Event::fake();

        $session = ChatSession::factory()->active()->create([
            'discord_channel_id' => '333333333',
        ]);

        $this->discordService
            ->shouldReceive('sendMessage')
            ->times(3)
            ->andReturn(['id' => 'msg123']);

        // Simulate rapid message sending
        $messages = [
            'First message',
            'Second message',
            'Third message',
        ];

        foreach ($messages as $content) {
            $this->chatService->sendMessage($session->id, [
                'content' => $content,
                'sender_type' => 'customer',
            ]);
        }

        // Verify all messages were stored in correct order
        $storedMessages = $session->messages()->orderBy('created_at')->pluck('content')->toArray();
        $this->assertEquals($messages, $storedMessages);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
