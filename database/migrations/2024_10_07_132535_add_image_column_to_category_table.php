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
        Schema::table('category', function (Blueprint $table) {
            // Add a new 'image' column to store the image path or URL
            $table->string('name')->nullable(); // Nullable in case some categories don't have images initially
        });
    }
    
    public function down(): void
    {
        Schema::table('category', function (Blueprint $table) {
            // Drop the 'image' column if the migration is rolled back
            $table->dropColumn('name');
        });
    }
};
