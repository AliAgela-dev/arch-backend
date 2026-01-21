<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_texts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_document_id')->unique();
            $table->longText('extracted_text')->nullable();
            $table->string('ocr_status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('student_document_id')
                ->references('id')
                ->on('student_documents')
                ->onDelete('cascade');
            
            $table->index('ocr_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_texts');
    }
};
