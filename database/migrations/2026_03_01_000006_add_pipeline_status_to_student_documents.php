<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_documents', function (Blueprint $table) {
            $table->string('pipeline_status')->default('pending')->after('file_status');
            $table->text('pipeline_error')->nullable()->after('pipeline_status');

            $table->index('pipeline_status');
        });
    }

    public function down(): void
    {
        Schema::table('student_documents', function (Blueprint $table) {
            $table->dropIndex(['pipeline_status']);
            $table->dropColumn(['pipeline_status', 'pipeline_error']);
        });
    }
};
