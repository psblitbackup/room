<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_name');
            $table->string('visitor_phone');
            $table->string('visitor_email')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('chat_agents')->nullOnDelete();
            $table->enum('status', ['pending', 'active', 'closed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
