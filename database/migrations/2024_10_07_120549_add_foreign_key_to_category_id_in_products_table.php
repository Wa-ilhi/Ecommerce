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
    Schema::table('products', function (Blueprint $table) {
        // Add the category_id column if it does not exist
        $table->bigInteger('category_id')->unsigned()->nullable();
        
        // Add the foreign key constraint
        $table->foreign('category_id')->references('id')->on('category')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        // Drop the foreign key constraint
        $table->dropForeign(['category_id']);
        
        // Drop the category_id column
        $table->dropColumn('category_id');
    });
}
};
