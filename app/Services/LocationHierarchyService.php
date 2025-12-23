<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LocationHierarchyService
{
    /**
     * Insert the self-reference row.
     *
     * Every node must reference itself with depth = 0.
     * Example: room -> room, cabinet -> cabinet, drawer -> drawer
     */
    public function insertSelf(string $id, string $type): void
    {
        DB::table('location_hierarchies')->insert([
            'ancestor_id'    => $id,
            'ancestor_type'  => $type,
            'descendant_id'  => $id,
            'descendant_type'=> $type,
            'depth'          => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * Link a parent node to a child node.
     *
     * This inserts:
     * 1) parent -> child (depth = 1)
     * 2) all ancestors of parent -> child (depth = ancestor.depth + 1)
     */
    public function linkParentChild(
        string $parentId,
        string $parentType,
        string $childId,
        string $childType
    ): void {
        // 1. Insert direct parent -> child relationship
        DB::table('location_hierarchies')->insert([
            'ancestor_id'     => $parentId,
            'ancestor_type'   => $parentType,
            'descendant_id'   => $childId,
            'descendant_type' => $childType,
            'depth'           => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // 2. Insert indirect relationships (ancestors of parent -> child)
        $ancestorRows = DB::table('location_hierarchies')
            ->where('descendant_id', $parentId)
            ->where('descendant_type', $parentType)
            ->where('depth', '>', 0)
            ->get();

        foreach ($ancestorRows as $row) {
            DB::table('location_hierarchies')->insert([
                'ancestor_id'     => $row->ancestor_id,
                'ancestor_type'   => $row->ancestor_type,
                'descendant_id'   => $childId,
                'descendant_type' => $childType,
                'depth'           => $row->depth + 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }
}
