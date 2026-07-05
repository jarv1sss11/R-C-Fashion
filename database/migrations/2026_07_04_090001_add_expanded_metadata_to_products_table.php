<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('vendor_id')->constrained()->nullOnDelete();
            $table->string('gender')->nullable()->after('category_id');
            $table->string('age_group')->nullable()->after('gender');
            $table->string('material')->nullable()->after('sizes');
            $table->string('season')->nullable()->after('material');
            $table->string('style')->nullable()->after('season');
            $table->json('tags')->nullable()->after('style');

            $table->index('gender');
            $table->index('age_group');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropIndex(['gender']);
            $table->dropIndex(['age_group']);
            $table->dropColumn(['gender', 'age_group', 'material', 'season', 'style', 'tags']);
        });
    }
};
