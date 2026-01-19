<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\StudentDocument\StoreStudentDocumentRequest;
use App\Http\Requests\StudentDocument\UpdateStudentDocumentRequest;
use App\Http\Resources\StudentDocumentResource;
use App\Models\StudentDocument;
use App\Services\MediaAssignmentService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Student Documents
 */
class StudentDocumentController extends AdminController
{
    public function __construct(
        protected MediaAssignmentService $mediaService
    ) {}

    public function index()
    {
        $documents = QueryBuilder::for(StudentDocument::class)
            ->allowedFilters([
                AllowedFilter::partial('file_number'),
                AllowedFilter::exact('student_id'),
                AllowedFilter::exact('document_type_id'),
                AllowedFilter::exact('file_status'),
            ])
            ->allowedSorts(['file_number', 'file_status', 'submitted_at', 'created_at'])
            ->with(['student', 'documentType', 'media'])
            ->paginate(request()->query('per_page', 15));

        return StudentDocumentResource::collection($documents);
    }

    public function store(StoreStudentDocumentRequest $request)
    {
        $data = $request->validated();
        $tempUploadId = $data['temp_upload_id'] ?? null;
        unset($data['temp_upload_id']);

        // Auto-generate file_number
        $data['file_number'] = 'DOC-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $document = StudentDocument::create($data);

        // Attach file from temp upload if provided
        if ($tempUploadId) {
            $this->mediaService->assign($document, $tempUploadId, 'document');
        }

        $document->load(['student', 'documentType', 'media']);

        return $this->resource(
            new StudentDocumentResource($document),
            'Student document created successfully',
            201
        );
    }

    public function show(string $id)
    {
        $document = StudentDocument::with(['student', 'documentType', 'media'])
            ->findOrFail($id);

        return new StudentDocumentResource($document);
    }

    public function update(UpdateStudentDocumentRequest $request, string $id)
    {
        $document = StudentDocument::findOrFail($id);

        $data = $request->validated();
        $tempUploadId = $data['temp_upload_id'] ?? null;
        unset($data['temp_upload_id']);

        $document->update($data);

        // Replace file if new temp upload provided
        if ($tempUploadId) {
            $this->mediaService->replace($document, $tempUploadId, 'document');
        }

        $document->load(['student', 'documentType', 'media']);

        return $this->resource(
            new StudentDocumentResource($document),
            'Student document updated successfully'
        );
    }

    public function destroy(string $id)
    {
        $document = StudentDocument::findOrFail($id);
        $document->delete();

        return $this->success(null, 'Student document deleted successfully');
    }
}
