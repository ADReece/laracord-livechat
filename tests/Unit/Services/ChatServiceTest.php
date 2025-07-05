<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Services;

use ADReece\LaracordLiveChat\Tests\TestCase;
use ADReece\LaracordLiveChat\Services\ChatService;
use ADReece\LaracordLiveChat\Services\DiscordService;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Models\ChatMessage;
use ADReece\LaracordLiveChat\Events\SessionStarted;
use ADReece\LaracordLiveChat\Events\SessionClosed;
use ADReece\LaracordLiveChat\Events\MessageSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chatService;
    private $discordServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discordServiceMock = Mockery::mock(DiscordService::class);
        $this->chatService = new ChatService($this->discordServiceMock);
    }

    /** @test */
    public function it_can_create_a_session()
    {
        Event::fake();

        $this->discordServiceMock
            ->shouldReceive('createChatChannel')
            ->once()
            ->andReturn('test_channel_id');

        $sessionData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ];

        $session = $this->chatService->createSession($sessionData);

        $this->assertInstanceOf(ChatSession::class, $session);
        $this->assertEquals('John Doe', $session->customer_name);
        $this->assertEquals('john@example.com', $session->customer_email);
        $this->assertEquals('192.168.1.1', $session->ip_address);
        $this->assertEquals('active', $session->status);
        $this->assertEquals('test_channel_id', $session->discord_channel_id);

        Event::assertDispatched(SessionStarted::class, function ($event) use ($session) {
            return $event->session->id === $session->id;
        });
    }

    /** @test */
    public function it_can_send_customer_message()
    {
        Event::fake();

        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
            'discord_channel_id' => 'test_channel_id',
        ]);

        $this->discordServiceMock
            ->shouldReceive('sendNewChatMessage')
            ->once()
            ->andReturn(true);

        $message = $this->chatService->sendCustomerMessage(
            $session->id,
            'Hello, I need help!',
            'John Doe'
        );

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals($session->id, $message->session_id);
        $this->assertEquals('customer', $message->sender_type);
        $this->assertEquals('John Doe', $message->sender_name);
        $this->assertEquals('Hello, I need help!', $message->message);

        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    }

    /** @test */
    public function it_can_send_agent_message()
    {
        Event::fake();

        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $message = $this->chatService->sendAgentMessage(
            $session->id,
            'How can I help you?',
            'Support Agent'
        );

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals($session->id, $message->session_id);
        $this->assertEquals('agent', $message->sender_type);
        $this->assertEquals('Support Agent', $message->sender_name);
        $this->assertEquals('How can I help you?', $message->message);

        Event::assertDispatched(MessageSent::class);
    }

    /** @test */
    public function it_can_get_session_with_messages()
    {
        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $session->messages()->create([
            'sender_type' => 'customer',
            'message' => 'First message',
        ]);

        $session->messages()->create([
            'sender_type' => 'agent',
            'message' => 'Second message',
        ]);

        $retrievedSession = $this->chatService->getSession($session->id);

        $this->assertInstanceOf(ChatSession::class, $retrievedSession);
        $this->assertEquals(2, $retrievedSession->messages->count());
        $this->assertEquals('First message', $retrievedSession->messages->first()->message);
    }

    /** @test */
    public function it_returns_null_for_non_existent_session()
    {
        $result = $this->chatService->getSession('non-existent-id');
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_get_active_sessions()
    {
        // Create active sessions
        ChatSession::create([
            'customer_name' => 'User 1',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        ChatSession::create([
            'customer_name' => 'User 2',
            'ip_address' => '192.168.1.2',
            'status' => 'active',
        ]);

        // Create closed session
        ChatSession::create([
            'customer_name' => 'User 3',
            'ip_address' => '192.168.1.3',
            'status' => 'closed',
        ]);

        $activeSessions = $this->chatService->getActiveSessions();

        $this->assertEquals(2, $activeSessions->count());
        $this->assertTrue($activeSessions->every(fn($session) => $session->status === 'active'));
    }

    /** @test */
    public function it_can_close_session()
    {
        Event::fake();

        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
            'discord_channel_id' => 'test_channel_id',
        ]);

        $this->discordServiceMock
            ->shouldReceive('deleteChatChannel')
            ->once()
            ->andReturn(true);

        $result = $this->chatService->closeSession($session->id);

        $this->assertTrue($result);
        $this->assertEquals('closed', $session->fresh()->status);

        Event::assertDispatched(SessionClosed::class, function ($event) use ($session) {
            return $event->session->id === $session->id;
        });
    }

    /** @test */
    public function it_cannot_close_already_closed_session()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'closed',
        ]);

        $result = $this->chatService->closeSession($session->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_mark_messages_as_read()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $customerMessage = $session->messages()->create([
            'sender_type' => 'customer',
            'message' => 'Customer message',
            'is_read' => false,
        ]);

        $agentMessage = $session->messages()->create([
            'sender_type' => 'agent',
            'message' => 'Agent message',
            'is_read' => false,
        ]);

        $this->chatService->markMessagesAsRead($session->id, 'customer');

        $this->assertTrue($customerMessage->fresh()->is_read);
        $this->assertFalse($agentMessage->fresh()->is_read);
    }

    /** @test */
    public function it_can_check_if_session_is_active()
    {
        $activeSession = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $closedSession = ChatSession::create([
            'ip_address' => '192.168.1.2',
            'status' => 'closed',
        ]);

        $this->assertTrue($this->chatService->isSessionActive($activeSession->id));
        $this->assertFalse($this->chatService->isSessionActive($closedSession->id));
        $this->assertFalse($this->chatService->isSessionActive('non-existent-id'));
    }

    /** @test */
    public function it_can_get_session_stats()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        // Add some messages
        $session->messages()->create(['sender_type' => 'customer', 'message' => 'Message 1']);
        $session->messages()->create(['sender_type' => 'agent', 'message' => 'Message 2']);
        $session->messages()->create(['sender_type' => 'customer', 'message' => 'Message 3']);

        $stats = $this->chatService->getSessionStats($session->id);

        $this->assertIsArray($stats);
        $this->assertEquals(3, $stats['total_messages']);
        $this->assertEquals(2, $stats['customer_messages']);
        $this->assertEquals(1, $stats['agent_messages']);
        $this->assertEquals('active', $stats['status']);
        $this->assertArrayHasKey('duration', $stats);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
