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
        Schema::table('class_vocabularies', function (Blueprint $table) {
            // First, remove the existing primary key if it exists
            // We use 'bigIncrements' as a simple fix for the missing ID issue
            $table->id()->change(); 
        });
    }

    public function down(): void
    {
        Schema::table('class_vocabularies', function (Blueprint $table) {
            $table->uuid('id')->primary()->change();
        });
    }
};
