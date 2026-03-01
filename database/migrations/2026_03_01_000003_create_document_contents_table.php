<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_document_id')
                ->constrained('student_documents')
                ->onDelete('cascade');
            $table->longText('content');
            $table->integer('page_number');
            $table->timestamps();

            $table->index(['student_document_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_contents');
    }
};
