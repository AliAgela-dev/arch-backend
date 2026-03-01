<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE document_contents
            ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (to_tsvector('simple', coalesce(content, ''))) STORED
        ");

        DB::statement('CREATE INDEX document_contents_search_idx ON document_contents USING gin(search_vector)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS document_contents_search_idx');
        DB::statement('ALTER TABLE document_contents DROP COLUMN IF EXISTS search_vector');
    }
};
