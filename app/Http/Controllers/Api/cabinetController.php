<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use Illuminate\Http\Request;
use App\Services\LocationHierarchyService;
use Illuminate\Support\Facades\DB;


class CabinetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cabinets = Cabinet::with('drawers')->get();
        return response()->json($cabinets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, LocationHierarchyService $hierarchy)
    {
        $input = $request->validate([
            'room_id' => 'required|string|exists:rooms,id',
            'name' => 'required|string|max:255',
            'position_x' => 'required|numeric',
            'position_y' => 'required|numeric',
            'drawer_count' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',]);

        $cabinet = DB::transaction(function () use ($input, $hierarchy) {
            // 1) Create cabinet row
            $cabinet = Cabinet::create($input);

            // 2) Closure table: cabinet self row (cabinet -> cabinet, depth 0)
            $hierarchy->insertSelf($cabinet->id, 'cabinet');

            // 3) Closure table: link Room -> Cabinet
            // Inserts:
            // - room -> cabinet (depth 1)
            // - all ancestors of room -> cabinet (none, because room is root)
            $hierarchy->linkParentChild(
                $cabinet->room_id,
                'room',
                $cabinet->id,
                'cabinet'
            );

            return $cabinet;
        });

        return response()->json(['message' => 'Cabinet created successfully'], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cabinet=Cabinet::with('drawers')->findOrFail($id);
        return response()->json($cabinet);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $input = $request->validate([
            'room_id' => 'sometimes|required|string|exists:rooms,id',
            'name' => 'sometimes|required|string|max:255',
            'position_x' => 'sometimes|required|numeric',
            'position_y' => 'sometimes|required|numeric',
            'drawer_count' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
        $cabinet = Cabinet::findOrFail($id);
        $cabinet->update($input);
        return response()->json(['message' => 'Cabinet updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {

            // 1) Find all descendants of this cabinet (usually drawers)
            $descendants = DB::table('location_hierarchies')
                ->where('ancestor_id', $id)
                ->where('ancestor_type', 'cabinet')
                ->where('depth', '>', 0)
                ->get(['descendant_id', 'descendant_type']);

            $drawerIds = $descendants->where('descendant_type', 'drawer')->pluck('descendant_id')->unique()->values();

            // 2) Delete domain data (drawers first)
            if ($drawerIds->isNotEmpty()) {
                DB::table('drawers')->whereIn('id', $drawerIds)->delete();
            }

            DB::table('cabinets')->where('id', $id)->delete();

            // 3) Clean closure rows for this cabinet subtree (cabinet + its drawers)
            $allNodePairs = collect()
                ->push(['id' => $id, 'type' => 'cabinet'])
                ->merge($descendants->map(fn ($d) => ['id' => $d->descendant_id, 'type' => $d->descendant_type]));

            DB::table('location_hierarchies')
                ->where(function ($q) use ($allNodePairs) {
                    foreach ($allNodePairs as $node) {
                        $q->orWhere(function ($qq) use ($node) {
                            $qq->where('ancestor_id', $node['id'])
                               ->where('ancestor_type', $node['type']);
                        });
                    }
                })
                ->orWhere(function ($q) use ($allNodePairs) {
                    foreach ($allNodePairs as $node) {
                        $q->orWhere(function ($qq) use ($node) {
                            $qq->where('descendant_id', $node['id'])
                               ->where('descendant_type', $node['type']);
                        });
                    }
                })
                ->delete();
        });

        return response()->json(['message' => 'Cabinet (and its drawers) deleted successfully']);
    }
}
