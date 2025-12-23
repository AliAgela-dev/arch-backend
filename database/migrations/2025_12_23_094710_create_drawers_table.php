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
        Schema::create('drawers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cabinet_id');
            $table->integer('number');
            $table->string('label', 50)->nullable();
            ///
            $table->integer('capacity')->default(100);
            $table->enum('status', ['active', 'inactive']);
            $table->timestamps();

            $table->foreign('cabinet_id')->references('id')->on('cabinets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drawers');
    }
};
