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
        Schema::create('matchmaking_models', function (Blueprint $table) {
            $table->string('id_match', 20)->primary();
            $table->string('id_worker', 20);
            $table->string('id_recruiter', 20);
            $table->unsignedBigInteger('id_job');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->enum('status_worker', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->enum('status_recruiter', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('matched_at')->useCurrent();
            $table->timestamps();

            $table->foreign('id_worker')->references('id_worker')->on('worker_models')->onDelete('cascade');
            $table->foreign('id_recruiter')->references('id_recruiter')->on('recruiter_models')->onDelete('cascade');
            $table->foreign('id_job')->references('id_job')->on('job__offer_models')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchmaking_models');
    }
};
