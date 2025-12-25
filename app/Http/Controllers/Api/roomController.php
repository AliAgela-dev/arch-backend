<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
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
        return RoomResource::collection($rooms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoomRequest $request, LocationHierarchyService $hierarchy)
    {
        $input = $request->validated();

        $room = DB::transaction(function () use ($input, $hierarchy) {
            $room = Room::create($input);

            // Closure table: self reference (room -> room, depth 0)
            $hierarchy->insertSelf($room->id, 'room');

            return $room;
        });
        return (new RoomResource($room))
            ->additional(['message' => 'Room created successfully'])
            ->response()
            ->setStatusCode(201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $room = Room::with('cabinets.drawers')->findOrFail($id);
        return new RoomResource($room);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoomRequest $request, string $id)
    {
        $input = $request->validated();

        $room = Room::findOrFail($id);
        $room->update($input);
        return (new RoomResource($room->fresh()->load('cabinets.drawers')))
            ->additional(['message' => 'Room updated successfully']);
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
            $cabinet_ids = $descendants->where('descendant_type', 'cabinet')->pluck('descendant_id')->unique()->values();
            $drawer_ids  = $descendants->where('descendant_type', 'drawer')->pluck('descendant_id')->unique()->values();

            // 2) Delete domain data (children first)
            if ($drawer_ids->isNotEmpty()) {
                DB::table('drawers')->whereIn('id', $drawer_ids)->delete();
            }

            if ($cabinet_ids->isNotEmpty()) {
                DB::table('cabinets')->whereIn('id', $cabinet_ids)->delete();
            }

            DB::table('rooms')->where('id', $id)->delete();

            // 3) Delete ALL hierarchy rows that involve this room subtree
            $all_node_pairs = collect()
                ->push(['id' => $id, 'type' => 'room'])
                ->merge($descendants->map(fn ($d) => ['id' => $d->descendant_id, 'type' => $d->descendant_type]));

            DB::table('location_hierarchies')
                ->where(function ($q) use ($all_node_pairs) {
                    foreach ($all_node_pairs as $node) {
                        $q->orWhere(function ($qq) use ($node) {
                            $qq->where('ancestor_id', $node['id'])
                               ->where('ancestor_type', $node['type']);
                        });
                    }
                })
                ->orWhere(function ($q) use ($all_node_pairs) {
                    foreach ($all_node_pairs as $node) {
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
