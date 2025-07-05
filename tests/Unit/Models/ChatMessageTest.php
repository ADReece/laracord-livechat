<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Models;

use ADReece\LaracordLiveChat\Tests\TestCase;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatMessageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_chat_message()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $message = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'sender_name' => 'John Doe',
            'message' => 'Hello, I need help!',
        ]);

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals('customer', $message->sender_type);
        $this->assertEquals('John Doe', $message->sender_name);
        $this->assertEquals('Hello, I need help!', $message->message);
    }

    /** @test */
    public function it_belongs_to_a_session()
    {
        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $message = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'message' => 'Test message',
        ]);

        $this->assertInstanceOf(ChatSession::class, $message->session);
        $this->assertEquals($session->id, $message->session->id);
        $this->assertEquals('John Doe', $message->session->customer_name);
    }

    /** @test */
    public function it_can_identify_sender_type()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $customerMessage = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'message' => 'Customer message',
        ]);

        $agentMessage = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'agent',
            'message' => 'Agent message',
        ]);

        $this->assertTrue($customerMessage->isFromCustomer());
        $this->assertFalse($customerMessage->isFromAgent());

        $this->assertTrue($agentMessage->isFromAgent());
        $this->assertFalse($agentMessage->isFromCustomer());
    }

    /** @test */
    public function it_can_be_marked_as_read()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $message = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'message' => 'Test message',
            'is_read' => false,
        ]);

        $this->assertFalse($message->is_read);

        $message->markAsRead();

        $this->assertTrue($message->fresh()->is_read);
    }

    /** @test */
    public function it_casts_is_read_to_boolean()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $message = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'message' => 'Test message',
            'is_read' => 1, // Integer
        ]);

        $this->assertIsBool($message->is_read);
        $this->assertTrue($message->is_read);
    }

    /** @test */
    public function it_casts_metadata_to_array()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $metadata = ['platform' => 'web', 'device' => 'desktop'];

        $message = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'message' => 'Test message',
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($message->metadata);
        $this->assertEquals($metadata, $message->metadata);
    }
}
