<?php

namespace Swoopy\LaracordLiveChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use HasUuids;

    protected $table = 'chat_sessions';

    protected $fillable = [
        'customer_name',
        'customer_email',
        'ip_address',
        'user_agent',
        'status',
        'discord_channel_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'session_id')->latest();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function markAsWaiting(): void
    {
        $this->update(['status' => 'waiting']);
    }
}
