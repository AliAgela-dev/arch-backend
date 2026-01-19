<?php

namespace App\Http\Controllers\Admin\Upload;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Upload\TempUploadRequest;
use App\Http\Resources\TempUploadResource;
use App\Models\TempUpload;

/**
 * @tags Temp Uploader
 */
class TempUploadController extends AdminController
{
    public function store(TempUploadRequest $request)
    {
        $file = $request->file('file');

        $tempUpload = TempUpload::create([
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'expires_at' => now()->addHours(24),
        ]);

        $tempUpload->addMedia($file)->toMediaCollection('temp');

        return $this->resource(
            new TempUploadResource($tempUpload),
            'File uploaded successfully',
            201
        );
    }

    public function destroy(string $id)
    {
        $tempUpload = TempUpload::findOrFail($id);
        $tempUpload->delete();

        return $this->success(null, 'Upload cancelled');
    }
}
