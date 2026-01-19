<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\DocumentType\StoreDocumentTypeRequest;
use App\Http\Requests\DocumentType\UpdateDocumentTypeRequest;
use App\Http\Resources\DocumentTypeResource;
use App\Models\DocumentType;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Document Types
 */
class DocumentTypeController extends AdminController
{
    public function index()
    {
        $documentTypes = QueryBuilder::for(DocumentType::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('is_required'),
            ])
            ->allowedSorts(['name', 'created_at', 'is_required'])
            ->paginate(request()->query('per_page', 15));

        return DocumentTypeResource::collection($documentTypes);
    }

    public function store(StoreDocumentTypeRequest $request)
    {
        $documentType = DocumentType::create($request->validated());

        return $this->resource(
            new DocumentTypeResource($documentType),
            'Document type created successfully',
            201
        );
    }

    public function show(string $id)
    {
        $documentType = DocumentType::findOrFail($id);

        return new DocumentTypeResource($documentType);
    }

    public function update(UpdateDocumentTypeRequest $request, string $id)
    {
        $documentType = DocumentType::findOrFail($id);
        $documentType->update($request->validated());

        return $this->resource(
            new DocumentTypeResource($documentType),
            'Document type updated successfully'
        );
    }

    public function destroy(string $id)
    {
        $documentType = DocumentType::findOrFail($id);

        if ($documentType->studentDocuments()->exists()) {
            return $this->error('Cannot delete document type with existing documents', 422);
        }

        $documentType->delete();

        return $this->success(null, 'Document type deleted successfully');
    }
}
