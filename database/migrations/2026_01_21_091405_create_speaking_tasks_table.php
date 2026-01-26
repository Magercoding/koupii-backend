<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('speaking_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('difficulty_level')->default('beginner');
            $table->integer('time_limit_seconds')->nullable();
            $table->string('topic')->nullable();
            $table->text('situation_context')->nullable();
            $table->json('questions')->nullable();
            $table->string('sample_audio')->nullable();
            $table->json('rubric')->nullable();
            $table->boolean('is_published')->default(false);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaking_tasks');
    }
};
