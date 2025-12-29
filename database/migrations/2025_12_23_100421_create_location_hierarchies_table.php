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
        Schema::create('location_hierarchies', function (Blueprint $table) {
            // Closure table storing ancestor -> descendant relationships
            // for rooms, cabinets and drawers. Each row represents a
            // path from `ancestor` to `descendant` with the distance
            // in `depth` (0 means same node).

            $table->uuid('ancestor_id');
            $table->string('ancestor_type', 50);
            $table->uuid('descendant_id');
            $table->string('descendant_type', 50);
            $table->unsignedInteger('depth')->default(0);

            // Unique composite index to prevent duplicate relations
            $table->unique([
                'ancestor_id',
                'ancestor_type',
                'descendant_id',
                'descendant_type',
            ], 'lh_ancestor_descendant_unique');

            // Indexes for fast ancestor/descendant lookups
            $table->index(['ancestor_id', 'ancestor_type'], 'lh_ancestor_idx');
            $table->index(['descendant_id', 'descendant_type'], 'lh_descendant_idx');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_hierarchies');
    }
};
