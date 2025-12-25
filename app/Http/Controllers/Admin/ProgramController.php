<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Program\StoreProgram;
use App\Http\Requests\Admin\Program\UpdateProgram;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $programs=Program::all();
        return ProgramResource::collection($programs);
    }
    public function show($id)
    {
        $program=Program::findOrFail($id);
        return new ProgramResource($program);
    }
    public function store(StoreProgram $request){
        $program=Program::create($request->validated());
        return (new ProgramResource($program))->additional(['message' => 'Successfully created program']);
    }
    public function update(UpdateProgram $request,$id){
        $program=Program::findOrFail($id);
        $program->update($request->validated());
        return (new ProgramResource($program))->additional(['message' => 'Successfully updated program']);
    }
    public function destroy($id){
        $program=Program::findOrFail($id);
        $program->delete();
        return response()->json(null,204);
    }
    public function restore($id){
        $program=Program::withTrashed()->findOrFail($id);
        $program->restore();
        return (new ProgramResource($program))->additional(['message' => 'Successfully restored program']);
    }

}
