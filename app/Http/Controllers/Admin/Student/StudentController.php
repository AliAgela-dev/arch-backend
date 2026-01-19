<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\RequirementConditionService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags StudentsFiles
 */
class StudentController extends AdminController
{
    public function __construct(
        protected RequirementConditionService $requirementService
    ) {}

    public function index()
    {
        $students = QueryBuilder::for(Student::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::partial('student_number'),
                AllowedFilter::exact('nationality'),
                AllowedFilter::exact('faculty_id'),
                AllowedFilter::exact('program_id'),
                AllowedFilter::exact('student_status'),
                AllowedFilter::exact('location_status'),
                AllowedFilter::exact('enrollment_year'),
            ])
            ->allowedSorts(['name', 'student_number', 'enrollment_year', 'created_at'])
            ->with(['faculty', 'program', 'drawer'])
            ->paginate(request()->query('per_page', 15));

        return StudentResource::collection($students);
    }

    public function store(StoreStudentRequest $request)
    {
        $student = Student::create($request->validated());
        $student->load(['faculty', 'program', 'drawer']);

        return $this->resource(
            new StudentResource($student),
            'Student created successfully',
            201
        );
    }

    public function show(string $id)
    {
        $student = Student::with(['faculty', 'program', 'drawer', 'documents.documentType'])
            ->findOrFail($id);

        // Get required documents for this student
        $requiredDocTypes = $this->requirementService->getRequiredDocumentTypes($student);

        return (new StudentResource($student))
            ->additional(['required_document_types' => $requiredDocTypes->pluck('name', 'id')]);
    }

    public function update(UpdateStudentRequest $request, string $id)
    {
        $student = Student::findOrFail($id);
        $student->update($request->validated());
        $student->load(['faculty', 'program', 'drawer']);

        return $this->resource(
            new StudentResource($student),
            'Student updated successfully'
        );
    }

    public function destroy(string $id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return $this->success(null, 'Student deleted successfully');
    }
}
