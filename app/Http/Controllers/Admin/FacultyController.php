<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\Faculty\StoreFacultyRequest;
use App\Http\Requests\Admin\Faculty\UpdateFacultyRequest;
use App\Http\Resources\FacultyResource;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FacultyController extends Controller
{
    public function index()
    {
        $faculties= QueryBuilder::for(Faculty::class)->allowedFilters([
        AllowedFilter::partial('name_ar'),
        AllowedFilter::partial('name_en'),
        AllowedFilter::exact('status'),])->allowedSorts(
            [
                'name_ar',
                'name_en',
                'created_at'
            ])->paginate(10); //i paginated by 10

        return FacultyResource::collection($faculties);
    }
    public function show($id)
    {
        $faculty = Faculty::findOrFail($id);
        return response()->json(new FacultyResource($faculty),200);
    }
    public function store(StoreFacultyRequest $request)
    {

        $faculty = Faculty::create($request->validated());
        return (new FacultyResource($faculty))->additional(['message' => 'Successfully created faculty']);
    }
    public function update(UpdateFacultyRequest $request, $id)
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->update($request->validated());
        return (new FacultyResource($faculty))->additional(['message' => 'Successfully updated faculty']);
    }
    public function destroy($id)
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->delete();
        return response()->json(['message' => 'Successfully deleted faculty'], 204);
    }
    public function restore($id)
    {
        $faculty = Faculty::withTrashed()->findOrFail($id);
        $faculty->restore();
        return(new FacultyResource($faculty))->additional(['message' => 'Successfully restored faculty']);
    }

    
}
