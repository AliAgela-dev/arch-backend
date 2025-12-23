<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Services\LocationHierarchyService;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rooms = Room::with('cabinets.drawers')->get();
        return response()->json($rooms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, LocationHierarchyService $hierarchy)
    {
        $input = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'canvas_data' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
        $room = DB::transaction(function () use ($input, $hierarchy) {
            $room = Room::create($input);

            // Closure table: self reference (room -> room, depth 0)
            $hierarchy->insertSelf($room->id, 'room');

            return $room;
        });
        return response()->json(['message' => 'Room created successfully'], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $room = Room::with('cabinets.drawers')->findOrFail($id);
        return response()->json($room);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $input = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'canvas_data' => 'sometimes|nullable|string',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        $room = Room::findOrFail($id);
        $room->update($input);
        return response()->json(['message' => 'Room updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {

            // 1) Find all descendants of this room from the closure table
            $descendants = DB::table('location_hierarchies')
                ->where('ancestor_id', $id)
                ->where('ancestor_type', 'room')
                ->where('depth', '>', 0)
                ->get(['descendant_id', 'descendant_type']);

            // Split by type
            $cabinetIds = $descendants->where('descendant_type', 'cabinet')->pluck('descendant_id')->unique()->values();
            $drawerIds  = $descendants->where('descendant_type', 'drawer')->pluck('descendant_id')->unique()->values();

            // 2) Delete domain data (children first)
            if ($drawerIds->isNotEmpty()) {
                DB::table('drawers')->whereIn('id', $drawerIds)->delete();
            }

            if ($cabinetIds->isNotEmpty()) {
                DB::table('cabinets')->whereIn('id', $cabinetIds)->delete();
            }

            DB::table('rooms')->where('id', $id)->delete();

            // 3) Delete ALL hierarchy rows that involve this room subtree
            $allNodePairs = collect()
                ->push(['id' => $id, 'type' => 'room'])
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

        return response()->json(['message' => 'Room (and its cabinets/drawers) deleted successfully']);
    }
}
