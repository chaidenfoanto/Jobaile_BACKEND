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
        Schema::create('dana_models', function (Blueprint $table) {
            $table->id('id_payments')->primary();
            $table->unsignedBigInteger('id_contract');
            $table->string('merchant_trans_id')->unique();
            $table->string('acquirement_id')->nullable();
            $table->string('status')->default('pending'); // pending, success, failed
            $table->timestamps();

            $table->foreign('id_contract')
                ->references('id_contract')
                ->on('contracts_models')
                ->onDelete('cascade');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dana_models');
    }
};
