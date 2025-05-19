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
        Schema::create('worker_models', function (Blueprint $table) {
            $table->string('id_worker', 20)->primary();
            $table->string('id_user', 20)->unique();
            $table->text('bio')->nullable();
            $table->text('skill')->nullable();
            $table->integer('experience_years')->nullable();
            $table->string('location', 100)->nullable(); // ← DIUBAH dari json ke varchar(100)
            $table->integer('expected_salary')->nullable();
            $table->enum('availability', ['penuh_waktu', 'paruh_waktu', 'mingguan', 'bulanan'])->nullable();
            $table->string('profile_picture')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_models');
    }
};
