<?php

namespace App\Http\Controllers\Admin\Academic;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Program\StoreProgramRequest;
use App\Http\Requests\Admin\Program\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProgramController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $programs = QueryBuilder::for(Program::class)
            ->allowedFilters([
                AllowedFilter::partial('name_ar'),
                AllowedFilter::partial('name_en'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('faculty_id'),
            ])
            ->allowedSorts(['name_ar', 'name_en', 'created_at'])
            ->with('faculty')
            ->paginate(10);

        return ProgramResource::collection($programs);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $program = Program::findOrFail($id);

        return new ProgramResource($program);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProgramRequest $request)
    {
        $program = Program::create($request->validated());

        return $this->resource(new ProgramResource($program), 'Successfully created program', 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProgramRequest $request, $id)
    {
        $program = Program::findOrFail($id);
        $program->update($request->validated());

        return $this->resource(new ProgramResource($program), 'Successfully updated program');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        $program->delete();

        return $this->noContent();
    }

    /**
     * Restore a soft-deleted program.
     */
    public function restore($id)
    {
        $program = Program::withTrashed()->findOrFail($id);
        $program->restore();

        return $this->resource(new ProgramResource($program), 'Successfully restored program');
    }
}
