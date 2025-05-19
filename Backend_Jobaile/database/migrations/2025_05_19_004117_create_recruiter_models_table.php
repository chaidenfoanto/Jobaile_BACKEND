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
        Schema::create('recruiter_models', function (Blueprint $table) {
            $$table->string('id_recruiter', 20)->primary();
            $table->string('id_user', 20)->unique();
            $table->string('house_type', 100)->nullable();
            $table->integer('family_size')->nullable();
            $table->string('location_address', 100)->nullable();
            $table->text('desc')->nullable();
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
        Schema::dropIfExists('recruiter_models');
    }
};
