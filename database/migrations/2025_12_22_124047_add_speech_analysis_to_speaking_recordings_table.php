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
        Schema::table('speaking_recordings', function (Blueprint $table) {
            // Speech-to-text fields
            $table->longText('transcript')->nullable()->after('duration_seconds');
            $table->decimal('confidence_score', 5, 4)->nullable()->after('transcript');
            
            // Speech quality analysis
            $table->decimal('fluency_score', 5, 2)->nullable()->after('confidence_score');
            $table->decimal('speaking_rate', 8, 2)->nullable()->after('fluency_score');
            $table->json('pause_analysis')->nullable()->after('speaking_rate');
            
            // Processing status
            $table->boolean('speech_processed')->default(false)->after('pause_analysis');
            $table->timestamp('speech_processed_at')->nullable()->after('speech_processed');
            
            // Index for better query performance
            $table->index(['speech_processed', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('speaking_recordings', function (Blueprint $table) {
            $table->dropIndex(['speech_processed', 'created_at']);
            $table->dropColumn([
                'transcript',
                'confidence_score', 
                'fluency_score',
                'speaking_rate',
                'pause_analysis',
                'speech_processed',
                'speech_processed_at'
            ]);
        });
    }
};
