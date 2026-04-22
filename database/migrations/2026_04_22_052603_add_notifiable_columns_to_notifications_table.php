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
        Schema::table('notifications', function (Blueprint $table) {
            $table->uuid('notifiable_id')->nullable()->after('id');
            $table->string('notifiable_type')->nullable()->after('notifiable_id');
            $table->text('data')->nullable()->after('type');
        });

        // Initialize from existing user_id
        \DB::table('notifications')->update([
            'notifiable_id' => \DB::raw('user_id'),
            'notifiable_type' => 'App\\Models\\User'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['notifiable_id', 'notifiable_type', 'data']);
        });
    }
};
