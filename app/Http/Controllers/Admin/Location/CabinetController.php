<?php

namespace App\Http\Controllers\Admin\Location;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\CabinetStoreRequest;
use App\Http\Requests\CabinetUpdateRequest;
use App\Http\Resources\CabinetResource;
use App\Models\Cabinet;
use App\Models\Drawer;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CabinetController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = (int) request()->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $cabinets = QueryBuilder::for(Cabinet::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('room_id'),
            ])
            ->allowedSorts(['name', 'created_at', 'updated_at', 'position_x', 'position_y'])
            ->with('drawers')
            ->paginate($perPage);

        return CabinetResource::collection($cabinets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CabinetStoreRequest $request)
    {
        $input = $request->validated();

        $cabinet = DB::transaction(function () use ($input) {
            $cabinet = Cabinet::create($input);

            // Auto-generate drawers
            $drawer_count = Cabinet::DRAWER_COUNT;

            for ($i = 1; $i <= $drawer_count; $i++) {
                Drawer::create([
                    'cabinet_id' => $cabinet->id,
                    'number'     => $i,
                    'capacity'   => 100,
                    'status'     => 'active',
                ]);
            }

            return $cabinet;
        });

        $cabinet->load('drawers');

        return $this->resource(new CabinetResource($cabinet), 'Cabinet created successfully', 201);
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
    public function update(CabinetUpdateRequest $request, string $id)
    {
        $input = $request->validated();
        $cabinet = Cabinet::with('drawers')->findOrFail($id);

        // Disallow changing room_id
        if (array_key_exists('room_id', $input) && $input['room_id'] !== $cabinet->room_id) {
            return $this->error('Changing room_id is not supported via update() without move logic.', 422);
        }

        DB::transaction(function () use ($input, $cabinet) {
            if (!empty($input)) {
                $cabinet->update($input);
            }

            $drawer_count = Cabinet::DRAWER_COUNT;

            // Delete extra drawers (> canonical count)
            $cabinet->drawers()->where('number', '>', $drawer_count)->delete();

            // Create missing drawers (ensure 1..4 exist)
            $existing_numbers = $cabinet->drawers()->pluck('number')->map(fn ($n) => (int) $n)->flip()->all();

            for ($i = 1; $i <= $drawer_count; $i++) {
                if (isset($existing_numbers[$i])) {
                    continue;
                }

                Drawer::create([
                    'cabinet_id' => $cabinet->id,
                    'number'     => $i,
                    'capacity'   => 100,
                    'status'     => 'active',
                ]);
            }
        });

        $cabinet->load('drawers');

        return $this->resource(new CabinetResource($cabinet), 'Cabinet updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $cabinet = Cabinet::with('drawers')->findOrFail($id);

            // Delete drawers first
            $cabinet->drawers()->delete();

            // Delete cabinet
            $cabinet->delete();
        });

        return $this->success(null, 'Cabinet (and its drawers) deleted successfully');
    }
}
