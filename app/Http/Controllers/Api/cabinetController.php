<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabinet;
use Illuminate\Http\Request;
use App\Services\LocationHierarchyService;
use Illuminate\Support\Facades\DB;
use App\Models\Drawer;


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
            'room_id'      => 'required|string|exists:rooms,id',
            'name'         => 'required|string|max:255',
            'position_x'   => 'required|numeric',
            'position_y'   => 'required|numeric',
            // Drawer count is server-managed; clients must not send it.
            'drawer_count' => 'prohibited',
            'status'       => 'required|in:active,inactive',
        ]);

        $cabinet = DB::transaction(function () use ($input, $hierarchy) {

            // 1) Create cabinet row
            $cabinet = Cabinet::create($input);

            // 2) Closure table: cabinet self row (cabinet -> cabinet, depth 0)
            $hierarchy->insertSelf($cabinet->id, 'cabinet');

            // 3) Closure table: link Room -> Cabinet
            $hierarchy->linkParentChild(
                $cabinet->room_id,
                'room',
                $cabinet->id,
                'cabinet');

            // here: auto-generate drawers --------
            $drawerCount = 4;

            for ($i = 1; $i <= $drawerCount; $i++) {
                // Create drawer
                $drawer = Drawer::create([
                    'cabinet_id' => $cabinet->id,
                    'number'     => $i,
                    'capacity'   => 100,
                    'status'     => 'active',
                ]);

                // Closure table: drawer self
                $hierarchy->insertSelf($drawer->id, 'drawer');

                // Closure table: link Cabinet -> Drawer
                // This also creates Room -> Drawer automatically if your service
                // uses cabinet ancestors (which include the room).
                $hierarchy->linkParentChild(
                    $cabinet->id,
                    'cabinet',
                    $drawer->id,
                    'drawer'
                );
            }

            return $cabinet;
        });

        $cabinet->load('drawers');

        return response()->json([
            'message' => 'Cabinet created successfully',
            'cabinet' => $cabinet,
        ], 201);
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
    public function update(Request $request, string $id, LocationHierarchyService $hierarchy)
    {
        $input = $request->validate([
            'room_id'      => 'sometimes|required|string|exists:rooms,id',
            'name'         => 'sometimes|required|string|max:255',
            'position_x'   => 'sometimes|required|numeric',
            'position_y'   => 'sometimes|required|numeric',
            // Drawer count is server-managed; clients must not send it.
            'drawer_count' => 'prohibited',
            'status'       => 'sometimes|required|in:active,inactive',
        ]);

        $cabinet = Cabinet::with('drawers')->findOrFail($id);

        // Disallow changing room_id here (moving needs closure-table rebuild logic)
        if (array_key_exists('room_id', $input) && $input['room_id'] !== $cabinet->room_id) {
            return response()->json([
                'message' => 'Changing room_id is not supported via update() without move logic.',
            ], 422);
        }

        DB::transaction(function () use ($input, $cabinet, $hierarchy) {

            // Update cabinet fields (no drawer_count handling needed now)
            $updateData = $input;

            if (!empty($updateData)) {
                $cabinet->update($updateData);
            }

            
            $drawerCount = 4;

            // Delete extra drawers (> canonical count)
            $toDeleteIds = $cabinet->drawers()
                ->where('number', '>', $drawerCount)
                ->pluck('id');

            if ($toDeleteIds->isNotEmpty()) {
                DB::table('drawers')->whereIn('id', $toDeleteIds)->delete();

                // Delete closure-table rows where these drawers appear as descendant OR ancestor.
                DB::table('location_hierarchies')
                    ->where(function ($q) use ($toDeleteIds) {
                        $q->where(function ($qq) use ($toDeleteIds) {
                            $qq->whereIn('descendant_id', $toDeleteIds)
                               ->where('descendant_type', 'drawer');
                        })->orWhere(function ($qq) use ($toDeleteIds) {
                            $qq->whereIn('ancestor_id', $toDeleteIds)
                               ->where('ancestor_type', 'drawer');
                        });
                    })
                    ->delete();
            }

            // Create missing drawers (ensure 1..4 exist)
            $existingNumbers = $cabinet->drawers()->pluck('number')->map(fn ($n) => (int) $n)->all();
            $existingNumbers = array_flip($existingNumbers);

            for ($i = 1; $i <= $drawerCount; $i++) {
                if (isset($existingNumbers[$i])) {
                    continue;
                }

                $drawer = Drawer::create([
                    'cabinet_id' => $cabinet->id,
                    'number'     => $i,
                    'capacity'   => 100,
                    'status'     => 'active',
                ]);

                // Closure table: drawer self
                $hierarchy->insertSelf($drawer->id, 'drawer');

                // Closure table: link Cabinet -> Drawer
                $hierarchy->linkParentChild(
                    $cabinet->id,
                    'cabinet',
                    $drawer->id,
                    'drawer'
                );
            }
        });

        $cabinet->load('drawers');

        return response()->json([
            'message' => 'Cabinet updated successfully',
            'cabinet' => $cabinet,
        ]);
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
