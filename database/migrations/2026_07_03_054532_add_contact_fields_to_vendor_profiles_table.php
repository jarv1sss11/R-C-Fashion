<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('store_slug');
            $table->string('email')->nullable()->after('phone');
            $table->string('county')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email', 'county']);
        });
    }
};
