<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listening_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('listening_tasks', 'class_id')) {
                $table->uuid('class_id')->nullable()->after('created_by');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
            }
            if (!Schema::hasColumn('listening_tasks', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('is_published');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listening_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('listening_tasks', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropColumn('class_id');
            }
            if (Schema::hasColumn('listening_tasks', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }
};
