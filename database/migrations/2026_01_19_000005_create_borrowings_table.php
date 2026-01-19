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
        Schema::create('borrowings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Foreign keys
            $table->uuid('user_id');
            $table->uuid('student_document_id');
            
            // Status and workflow
            $table->string('status', 50)->index();
            
            // Timestamps for workflow tracking
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('borrowed_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('returned_at')->nullable();
            
            // Notes and reasons
            $table->text('notes')->nullable(); // Borrower's notes
            $table->text('admin_notes')->nullable(); // Admin/archivist notes
            $table->text('rejection_reason')->nullable();
            
            // Standard timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
                
            $table->foreign('student_document_id')
                ->references('id')
                ->on('student_documents')
                ->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
