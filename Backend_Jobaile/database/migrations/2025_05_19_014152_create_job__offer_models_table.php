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
        Schema::create('job__offer_models', function (Blueprint $table) {
            $table->unsignedBigInteger('id_job')->primary();
            $table->string('id_recruiter', 20);
            $table->string('job_title', 100);
            $table->text('desc')->nullable();
            $table->enum('status', ['open', 'closed', 'pending'])->default('open');
            $table->timestamps();

            $table->foreign('id_recruiter')->references('id_recruiter')->on('recruiter_models')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job__offer_models');
    }
};
