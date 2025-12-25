<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CabinetRequest;
use App\Http\Resources\CabinetResource;
use App\Models\Cabinet;
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
        return CabinetResource::collection($cabinets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CabinetRequest $request, LocationHierarchyService $hierarchy)
    {
        $input = $request->validated();

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
            $drawer_count = 4;

            for ($i = 1; $i <= $drawer_count; $i++) {
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

        return (new CabinetResource($cabinet))
            ->additional(['message' => 'Cabinet created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cabinet = Cabinet::with('drawers')->findOrFail($id);
        return new CabinetResource($cabinet);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(CabinetRequest $request, string $id, LocationHierarchyService $hierarchy)
    {
        $input = $request->validated();

        $cabinet = Cabinet::with('drawers')->findOrFail($id);

        // Disallow changing room_id here (moving needs closure-table rebuild logic)
        if (array_key_exists('room_id', $input) && $input['room_id'] !== $cabinet->room_id) {
            return response()->json([
                'message' => 'Changing room_id is not supported via update() without move logic.',
            ], 422);
        }

        DB::transaction(function () use ($input, $cabinet, $hierarchy) {

            // Update cabinet fields (no drawer_count handling needed now)
            $update_data = $input;

            if (!empty($update_data)) {
                $cabinet->update($update_data);
            }


            $drawer_count = 4;

            // Delete extra drawers (> canonical count)
            $to_delete_ids = $cabinet->drawers()
                ->where('number', '>', $drawer_count)
                ->pluck('id');

            if ($to_delete_ids->isNotEmpty()) {
                DB::table('drawers')->whereIn('id', $to_delete_ids)->delete();

                // Delete closure-table rows where these drawers appear as descendant OR ancestor.
                DB::table('location_hierarchies')
                    ->where(function ($q) use ($to_delete_ids) {
                        $q->where(function ($qq) use ($to_delete_ids) {
                            $qq->whereIn('descendant_id', $to_delete_ids)
                               ->where('descendant_type', 'drawer');
                        })->orWhere(function ($qq) use ($to_delete_ids) {
                            $qq->whereIn('ancestor_id', $to_delete_ids)
                               ->where('ancestor_type', 'drawer');
                        });
                    })
                    ->delete();
            }

            // Create missing drawers (ensure 1..4 exist)
            $existing_numbers = $cabinet->drawers()->pluck('number')->map(fn ($n) => (int) $n)->all();
            $existing_numbers = array_flip($existing_numbers);

            for ($i = 1; $i <= $drawer_count; $i++) {
                if (isset($existing_numbers[$i])) {
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

        return (new CabinetResource($cabinet))
            ->additional(['message' => 'Cabinet updated successfully']);
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

                $drawer_ids = $descendants->where('descendant_type', 'drawer')->pluck('descendant_id')->unique()->values();

                // 2) Delete domain data (drawers first)
                if ($drawer_ids->isNotEmpty()) {
                    DB::table('drawers')->whereIn('id', $drawer_ids)->delete();
                }

                DB::table('cabinets')->where('id', $id)->delete();

                // 3) Clean closure rows for this cabinet subtree (cabinet + its drawers)
                $all_node_pairs = collect()
                    ->push(['id' => $id, 'type' => 'cabinet'])
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

            return response()->json(['message' => 'Cabinet (and its drawers) deleted successfully']);
        }
}
