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
        // Drop foreign key constraint
     
        
        // Drop the category_id column
        $table->dropColumn('category_id');
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        // Recreate the category_id column and foreign key in case of rollback
        $table->bigInteger('category_id')->unsigned();
    });
}
};
