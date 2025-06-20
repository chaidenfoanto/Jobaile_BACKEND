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
        Schema::create('contracts_models', function (Blueprint $table) {
            $table->id('id_contract');
            $table->string('id_worker', 20);
            $table->string('id_recruiter', 20);
            $table->unsignedBigInteger('id_job');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('terms');
            $table->enum('status_pay', ['pending', 'success'])->default('pending');
            $table->timestamp('sign_at')->useCurrent();
            $table->timestamps();

            $table->foreign('id_worker')->references('id_worker')->on('worker_models')->onDelete('cascade');
            $table->foreign('id_recruiter')->references('id_recruiter')->on('recruiter_models')->onDelete('cascade');
            $table->foreign('id_job')->references('id_job')->on('job__offer_models')->onDelete('cascade');

            $table->unique(['id_worker', 'id_recruiter'], 'unique_worker_recruiter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts_models');
    }
};
