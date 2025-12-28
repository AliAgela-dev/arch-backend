<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\Program\StoreProgramRequest;

use App\Http\Requests\Admin\Program\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProgramController extends Controller
{
    public function index()
    {
        $programs=QueryBuilder::for(Program::class)->allowedFilters([
            AllowedFilter::partial('name_ar'),
            AllowedFilter::partial('name_en'),
            AllowedFilter::exact('status'), //im not sure if you want this but i added it for inceased functionality/flexiblity
            AllowedFilter::exact('faculty_id'), //same here
        ])->allowedSorts(
            [
                'name_ar',
                'name_en',
                'created_at'
            ])->with('faculty')->paginate(10); //i paginated by 10

        return ProgramResource::collection($programs);
            
       
    }
    public function show($id)
    {
        $program=Program::findOrFail($id);
        return new ProgramResource($program);
    }
    public function store(StoreProgramRequest $request){
        $program=Program::create($request->validated());
        return (new ProgramResource($program))->additional(['message' => 'Successfully created program']);
    }
    public function update(UpdateProgramRequest $request,$id){
        $program=Program::findOrFail($id);
        $program->update($request->validated());
        return (new ProgramResource($program))->additional(['message' => 'Successfully updated program']);
    }
    public function destroy($id){
        $program=Program::findOrFail($id);
        $program->delete();
        return response()->json(['message' => 'Successfully deleted program'], 204);
    }
    public function restore($id){
        $program=Program::withTrashed()->findOrFail($id);
        $program->restore();
        return (new ProgramResource($program))->additional(['message' => 'Successfully restored program']);
    }

}
