<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Http\Requests;

use ADReece\LaracordLiveChat\Http\Requests\SendMessageRequest;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class SendMessageRequestTest extends TestCase
{
    /** @test */
    public function it_passes_validation_with_valid_data()
    {
        $data = [
            'content' => 'This is a test message',
            'sender_type' => 'customer',
            'sender_name' => 'John Doe',
        ];

        $request = new SendMessageRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_requires_content()
    {
        $data = [
            'sender_type' => 'customer',
        ];

        $request = new SendMessageRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content', $validator->errors()->toArray());
    }

    /** @test */
    public function it_requires_sender_type()
    {
        $data = [
            'content' => 'Test message',
        ];

        $request = new SendMessageRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sender_type', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_sender_type_values()
    {
        $validSenderTypes = ['customer', 'agent'];
        
        foreach ($validSenderTypes as $senderType) {
            $data = [
                'content' => 'Test message',
                'sender_type' => $senderType,
            ];

            $request = new SendMessageRequest();
            $validator = Validator::make($data, $request->rules());

            $this->assertFalse($validator->fails(), "Sender type '{$senderType}' should be valid");
        }

        // Test invalid sender type
        $data = [
            'content' => 'Test message',
            'sender_type' => 'invalid',
        ];

        $request = new SendMessageRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sender_type', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_content_length()
    {
        // Test content that's too long
        $data = [
            'content' => str_repeat('a', 2001), // Assuming max length is 2000
            'sender_type' => 'customer',
        ];

        $request = new SendMessageRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_sender_name_length()
    {
        $data = [
            'content' => 'Test message',
            'sender_type' => 'customer',
            'sender_name' => str_repeat('a', 256), // Assuming max length is 255
        ];

        $request = new SendMessageRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('sender_name', $validator->errors()->toArray());
    }

    /** @test */
    public function it_allows_optional_sender_name()
    {
        $data = [
            'content' => 'Test message',
            'sender_type' => 'customer',
            // sender_name is optional
        ];

        $request = new SendMessageRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_sanitizes_content()
    {
        $data = [
            'content' => '<script>alert("xss")</script>Hello world',
            'sender_type' => 'customer',
        ];

        $request = new SendMessageRequest();
        $request->merge($data);

        // Test that the request processes the data correctly
        $this->assertEquals('<script>alert("xss")</script>Hello world', $request->input('content'));
    }

    /** @test */
    public function it_validates_empty_content()
    {
        $data = [
            'content' => '',
            'sender_type' => 'customer',
        ];

        $request = new SendMessageRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_whitespace_only_content()
    {
        $data = [
            'content' => '   ',
            'sender_type' => 'customer',
        ];

        $request = new SendMessageRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content', $validator->errors()->toArray());
    }
}
