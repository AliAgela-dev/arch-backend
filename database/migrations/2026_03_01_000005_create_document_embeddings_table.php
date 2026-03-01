<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_embeddings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_content_id')
                ->constrained('document_contents')
                ->onDelete('cascade');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE document_embeddings ADD COLUMN vector vector(768)');
        DB::statement('CREATE INDEX document_embeddings_vector_idx ON document_embeddings USING hnsw (vector vector_cosine_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('document_embeddings');
    }
};
