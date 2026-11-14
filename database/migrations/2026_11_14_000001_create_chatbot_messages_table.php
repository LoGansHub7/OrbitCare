<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('astronaut_id')->constrained('users')->restrictOnDelete();
            $table->uuid('client_id');
            $table->string('role', 20);
            $table->text('message');
            $table->string('intent', 80)->nullable();
            $table->timestamp('created_at');
            $table->unique(['astronaut_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
