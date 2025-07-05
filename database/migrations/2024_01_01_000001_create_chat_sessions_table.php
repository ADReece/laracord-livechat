<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('status')->default('active'); // active, closed, waiting
            $table->string('discord_channel_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_sessions');
    }
};
