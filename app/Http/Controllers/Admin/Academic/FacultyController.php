<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Faculty\StoreFacultyRequest;
use App\Http\Requests\Admin\Faculty\UpdateFacultyRequest;
use App\Http\Resources\FacultyResource;
use App\Models\Faculty;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FacultyController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $faculties = QueryBuilder::for(Faculty::class)
            ->allowedFilters([
                AllowedFilter::partial('name_ar'),
                AllowedFilter::partial('name_en'),
                AllowedFilter::exact('status'),
            ])
            ->allowedSorts(['name_ar', 'name_en', 'created_at'])
            ->paginate(10);

        return FacultyResource::collection($faculties);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $faculty = Faculty::findOrFail($id);

        return new FacultyResource($faculty);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFacultyRequest $request)
    {
        $faculty = Faculty::create($request->validated());

        return $this->resource(new FacultyResource($faculty), 'Successfully created faculty', 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFacultyRequest $request, $id)
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->update($request->validated());

        return $this->resource(new FacultyResource($faculty), 'Successfully updated faculty');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->delete();

        return $this->noContent();
    }

    /**
     * Restore a soft-deleted faculty.
     */
    public function restore($id)
    {
        $faculty = Faculty::withTrashed()->findOrFail($id);
        $faculty->restore();

        return $this->resource(new FacultyResource($faculty), 'Successfully restored faculty');
    }
}
