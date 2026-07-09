<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a maintained search_index TEXT column to products and a FULLTEXT
 * index over it.
 *
 * WHY a maintained plain-TEXT column instead of a generated/virtual column:
 *
 *  - MySQL FULLTEXT cannot index JSON columns or JSON-extracted values
 *    (tags is stored as JSON). A generated column like
 *    `JSON_UNQUOTE(JSON_EXTRACT(tags, '$[*]'))` is not indexable by FULLTEXT
 *    in MySQL 8.0 and would need `STORED` mode on MariaDB, which is fragile.
 *  - A maintained column lets us embed brand name, category name, and style
 *    text — fields that live in joined tables, which generated columns cannot
 *    reference at all.
 *  - We control exactly what goes into the index. This also means we can
 *    duplicate the product name (repeating it raises its TF-IDF weight in
 *    FULLTEXT scoring without any query-time trickery).
 *
 * The column is populated by:
 *  - php artisan search:rebuild-index   (one-off backfill / admin utility)
 *  - ProductObserver::saving()           (kept in sync on every save)
 *
 * WHY Boolean Mode FULLTEXT instead of Natural Language Mode:
 *
 *  Natural Language Mode silently suppresses any word that appears in more
 *  than 50% of rows — a single category word like "Men", "Women", "Sports"
 *  could already trip that threshold with ~154 products. Boolean Mode has no
 *  such threshold and lets us use prefix wildcards (term*) for partial-word
 *  matching, which is essential for autocomplete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('search_index')->nullable()->after('tags');
        });

        // FULLTEXT on the maintained column. A raw DDL statement is needed
        // because Blueprint::fullText() targets a single column but doesn't
        // let us use the index name we want; rawDB keeps it explicit.
        DB::statement('ALTER TABLE products ADD FULLTEXT INDEX products_search_fulltext (search_index)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products DROP INDEX products_search_fulltext');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('search_index');
        });
    }
};
