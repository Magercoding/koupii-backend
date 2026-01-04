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
        Schema::table('tests', function (Blueprint $table) {
            // Add class_id to connect tests with classes
            $table->uuid('class_id')->nullable()->after('creator_id');
            
            // Add foreign key constraint
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            
            // Add index for performance
            $table->index(['class_id', 'creator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropIndex(['class_id', 'creator_id']);
            $table->dropColumn('class_id');
        });
    }
};