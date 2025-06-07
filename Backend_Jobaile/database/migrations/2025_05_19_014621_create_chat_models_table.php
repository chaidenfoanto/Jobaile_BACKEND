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
        Schema::create('chat_models', function (Blueprint $table) {
            $table->id('id_chat');
            $table->string('id_sender', 20);
            $table->string('id_receiver', 20);
            $table->text('message');
            $table->timestamp('send_at')->useCurrent();
            $table->timestamps();
            $table->foreign('id_sender')->references('id_user')->on('users')->onDelete('cascade');
            $table->foreign('id_receiver')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_models');
    }
};
