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
        Schema::create('rating_review_models', function (Blueprint $table) {
            $table->string('id_reviewer', 20);
            $table->string('id_reviewed', 20);
            $table->text('ulasan')->nullable();
            $table->unsignedTinyInteger('rating')->default(0);
            $table->timestamp('tanggal_rating')->useCurrent();
            $table->enum('role', ['worker', 'recruiter']);
            $table->timestamps();

            $table->foreign('id_reviewer')->references('id_user')->on('users')->onDelete('cascade');
            $table->foreign('id_reviewed')->references('id_user')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rating_review_models');
    }
};
