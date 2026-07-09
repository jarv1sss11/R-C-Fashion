<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a lightweight product_type dimension (e.g. "jacket", "shoes", "watch")
 * alongside the existing category_id, which only encodes department
 * (Men/Women/Kids/Sports/Accessories — 5 flat rows, no subcategory
 * hierarchy). Nothing in the schema currently distinguishes a shoe from a
 * jacket from a watch within the same department; this column is step one
 * of closing that gap. Deliberately does not touch category_id, the
 * categories table, or parent_id — those stay exactly as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->nullable()->after('style');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_type');
        });
    }
};
