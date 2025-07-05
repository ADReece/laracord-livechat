<?php

namespace ADReece\LaracordLiveChat\Database\Factories;

use ADReece\LaracordLiveChat\Models\ChatSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatSessionFactory extends Factory
{
    protected $model = ChatSession::class;

    public function definition(): array
    {
        return [
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->optional()->safeEmail(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'status' => 'active',
            'discord_channel_id' => $this->faker->optional()->numerify('####################'),
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waiting',
        ]);
    }

    public function withDiscordChannel(): static
    {
        return $this->state(fn (array $attributes) => [
            'discord_channel_id' => $this->faker->numerify('####################'),
        ]);
    }
}
