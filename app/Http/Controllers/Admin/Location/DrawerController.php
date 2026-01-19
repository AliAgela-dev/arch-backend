<?php

namespace App\Http\Controllers\Admin\Location;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\DrawerStoreRequest;
use App\Http\Requests\DrawerUpdateRequest;
use App\Http\Resources\DrawerResource;
use App\Models\Drawer;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Drawers
 */
class DrawerController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = (int) request()->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $drawers = QueryBuilder::for(Drawer::class)
            ->allowedFilters([
                AllowedFilter::partial('label'),
                AllowedFilter::exact('number'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('cabinet_id'),
            ])
            ->allowedSorts(['number', 'label', 'capacity', 'created_at', 'updated_at'])
            ->with('cabinet')
            ->paginate($perPage);

        return DrawerResource::collection($drawers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DrawerStoreRequest $request)
    {
        $drawer = Drawer::create($request->validated());

        return $this->resource(new DrawerResource($drawer), 'Drawer created successfully', 201);
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
            return $this->error('Changing cabinet_id is not supported via update() without move logic.', 422);
        }

        $drawer->update($input);

        return $this->resource(new DrawerResource($drawer->fresh()), 'Drawer updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Drawer::findOrFail($id)->delete();

        return $this->success(null, 'Drawer deleted successfully');
    }
}
