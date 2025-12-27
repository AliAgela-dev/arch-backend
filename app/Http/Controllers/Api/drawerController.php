<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DrawerStoreRequest;
use App\Http\Requests\DrawerUpdateRequest;
use App\Http\Resources\DrawerResource;
use App\Models\Drawer;
use App\Services\LocationHierarchyService;
use Illuminate\Support\Facades\DB;

class DrawerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = (int) request()->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $drawers = Drawer::with('cabinet')->paginate($perPage);

        return DrawerResource::collection($drawers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * Required closure logic:
     * - insertSelf(drawer)
     * - linkParentChild(cabinet -> drawer)
     * This automatically produces room -> drawer (depth 2) because room is an ancestor of cabinet.
     */
    public function store(DrawerStoreRequest $request, LocationHierarchyService $hierarchy)
    {
        $input = $request->validated();

        $drawer = DB::transaction(function () use ($input, $hierarchy) {
            // 1) Create drawer
            $drawer = Drawer::create($input);

            // 2) Closure table: self row (drawer -> drawer, depth 0)
            $hierarchy->insertSelf($drawer->id, 'drawer');

            // 3) Closure table: link Cabinet -> Drawer
            // Inserts:
            // - cabinet -> drawer (depth 1)
            // - room -> drawer (depth 2) automatically (because room is ancestor of cabinet)
            $hierarchy->linkParentChild(
                $drawer->cabinet_id,
                'cabinet',
                $drawer->id,
                'drawer'
            );

            return $drawer;
        });

        return (new DrawerResource($drawer))
            ->additional(['message' => 'Drawer created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $drawer = Drawer::with('cabinet')->findOrFail($id);
        return new DrawerResource($drawer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DrawerUpdateRequest $request, string $id)
    {
        $input = $request->validated();

        $drawer = Drawer::findOrFail($id);

        if (array_key_exists('cabinet_id', $input) && $input['cabinet_id'] !== $drawer->cabinet_id) {
            return response()->json([
                'message' => 'Changing cabinet_id is not supported via update() without move logic.',
            ], 422);
        }

        $drawer->update($input);

        return (new DrawerResource($drawer->fresh()))
            ->additional(['message' => 'Drawer updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * Deletes the drawer and removes all closure table rows where the drawer is ancestor/descendant.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            // Delete drawer row
            Drawer::where('id', $id)->delete();

            // Clean closure rows that involve this drawer
            DB::table('location_hierarchies')
                ->where(function ($q) use ($id) {
                    $q->where('ancestor_id', $id)->where('ancestor_type', 'drawer');
                })
                ->orWhere(function ($q) use ($id) {
                    $q->where('descendant_id', $id)->where('descendant_type', 'drawer');
                })
                ->delete();
        });

        return response()->json(['message' => 'Drawer deleted successfully']);
    }
}
