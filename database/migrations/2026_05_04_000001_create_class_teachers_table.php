<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('class_id');
            $table->uuid('teacher_id');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['class_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_teachers');
    }
};
