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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade'); //foreign key to faculties table  ⭕ do you want it to delete on cascade?
            $table->string('code')->unique(); //program code
            $table->string('name_ar', 255); //arabic name
            $table->string('name_en', 255); //english name
            $table->enum('status', ['active', 'inactive'])->default('active'); 
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
