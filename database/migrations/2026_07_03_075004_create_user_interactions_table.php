<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->id();
            // Nullable: search queries have no product; a future guest-tracking
            // pass could allow nullable user_id too, but that's not built yet.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('interaction_type');
            $table->float('weight')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'interaction_type']);
            $table->index(['product_id', 'interaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_interactions');
    }
};
