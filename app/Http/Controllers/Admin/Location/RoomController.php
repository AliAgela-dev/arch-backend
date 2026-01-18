<?php

namespace App\Http\Controllers\Admin\Location;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\RoomStoreRequest;
use App\Http\Requests\RoomUpdateRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoomController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rooms = QueryBuilder::for(Room::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::partial('description'),
                AllowedFilter::exact('status'),
            ])
            ->allowedSorts(['name', 'created_at', 'updated_at'])
            ->with('cabinets.drawers')
            ->paginate(request()->query('per_page', 15));

        return RoomResource::collection($rooms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoomStoreRequest $request)
    {
        $room = Room::create($request->validated());

        return $this->resource(new RoomResource($room), 'Room created successfully', 201);
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
    public function update(RoomUpdateRequest $request, string $id)
    {
        $room = Room::findOrFail($id);
        $room->update($request->validated());

        return $this->resource(
            new RoomResource($room->fresh()->load('cabinets.drawers')),
            'Room updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $room = Room::with('cabinets.drawers')->findOrFail($id);

            // Delete drawers first
            foreach ($room->cabinets as $cabinet) {
                $cabinet->drawers()->delete();
            }

            // Delete cabinets
            $room->cabinets()->delete();

            // Delete room
            $room->delete();
        });

        return $this->success(null, 'Room (and its cabinets/drawers) deleted successfully');
    }
}
