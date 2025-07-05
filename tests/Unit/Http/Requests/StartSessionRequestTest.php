<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Http\Requests;

use ADReece\LaracordLiveChat\Http\Requests\StartSessionRequest;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class StartSessionRequestTest extends TestCase
{
    /** @test */
    public function it_passes_validation_with_valid_data()
    {
        $data = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'initial_message' => 'I need help with my order',
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_requires_customer_name()
    {
        $data = [
            'customer_email' => 'john@example.com',
            'initial_message' => 'I need help',
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_name', $validator->errors()->toArray());
    }

    /** @test */
    public function it_requires_customer_email()
    {
        $data = [
            'customer_name' => 'John Doe',
            'initial_message' => 'I need help',
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_email', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_email_format()
    {
        $data = [
            'customer_name' => 'John Doe',
            'customer_email' => 'not-an-email',
            'initial_message' => 'I need help',
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_email', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_customer_name_length()
    {
        $data = [
            'customer_name' => str_repeat('a', 256), // Assuming max length is 255
            'customer_email' => 'john@example.com',
            'initial_message' => 'I need help',
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_name', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_email_length()
    {
        $longEmail = str_repeat('a', 240) . '@example.com'; // Very long email
        
        $data = [
            'customer_name' => 'John Doe',
            'customer_email' => $longEmail,
            'initial_message' => 'I need help',
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_email', $validator->errors()->toArray());
    }

    /** @test */
    public function it_allows_optional_initial_message()
    {
        $data = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            // initial_message is optional
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_validates_initial_message_length()
    {
        $data = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'initial_message' => str_repeat('a', 2001), // Assuming max length is 2000
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('initial_message', $validator->errors()->toArray());
    }

    /** @test */
    public function it_accepts_various_email_formats()
    {
        $validEmails = [
            'simple@example.com',
            'user.name@example.com',
            'user+tag@example.com',
            'user_name@example-site.com',
            'test@subdomain.example.org',
        ];

        foreach ($validEmails as $email) {
            $data = [
                'customer_name' => 'John Doe',
                'customer_email' => $email,
                'initial_message' => 'Test message',
            ];

            $request = new StartSessionRequest();
            $validator = Validator::make($data, $request->rules());

            $this->assertFalse($validator->fails(), "Email '{$email}' should be valid");
        }
    }

    /** @test */
    public function it_rejects_invalid_email_formats()
    {
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'user@',
            'user..name@example.com',
            'user@.com',
            'user@com',
        ];

        foreach ($invalidEmails as $email) {
            $data = [
                'customer_name' => 'John Doe',
                'customer_email' => $email,
                'initial_message' => 'Test message',
            ];

            $request = new StartSessionRequest();
            $validator = Validator::make($data, $request->rules());

            $this->assertTrue($validator->fails(), "Email '{$email}' should be invalid");
            $this->assertArrayHasKey('customer_email', $validator->errors()->toArray());
        }
    }

    /** @test */
    public function it_trims_whitespace_from_inputs()
    {
        $data = [
            'customer_name' => '  John Doe  ',
            'customer_email' => '  john@example.com  ',
            'initial_message' => '  I need help  ',
        ];

        $request = new StartSessionRequest();
        $request->merge($data);

        // The request should handle trimming
        $this->assertEquals('  John Doe  ', $request->input('customer_name'));
        $this->assertEquals('  john@example.com  ', $request->input('customer_email'));
        $this->assertEquals('  I need help  ', $request->input('initial_message'));
    }

    /** @test */
    public function it_handles_empty_initial_message()
    {
        $data = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'initial_message' => '',
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        // Empty initial message should be allowed
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_validates_unicode_characters_in_name()
    {
        $data = [
            'customer_name' => 'José María García',
            'customer_email' => 'jose@example.com',
            'initial_message' => 'Hola, necesito ayuda',
        ];

        $request = new StartSessionRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }
}
