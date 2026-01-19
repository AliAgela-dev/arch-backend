<?php

namespace App\Services;

use App\Models\TempUpload;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class MediaAssignmentService
{
    /**
     * Assign temp upload(s) to a model.
     *
     * @param HasMedia $model Target model with Spatie Media Library
     * @param string|array $tempUploadIds Single ID or array of IDs
     * @param string $collection Media collection name
     * @return void
     */
    public function assign(HasMedia $model, string|array $tempUploadIds, string $collection = 'default'): void
    {
        $ids = is_array($tempUploadIds) ? $tempUploadIds : [$tempUploadIds];

        foreach ($ids as $tempUploadId) {
            $tempUpload = TempUpload::notExpired()->find($tempUploadId);

            if (!$tempUpload) {
                continue;
            }

            $media = $tempUpload->getFirstMedia('temp');

            if ($media) {
                // Copy media to target model
                $media->copy($model, $collection);
            }

            // Delete temp upload and its media
            $tempUpload->delete();
        }
    }

    /**
     * Replace existing media with new temp upload(s).
     *
     * @param HasMedia $model Target model
     * @param string|array $tempUploadIds Single ID or array of IDs
     * @param string $collection Media collection name
     * @return void
     */
    public function replace(HasMedia $model, string|array $tempUploadIds, string $collection = 'default'): void
    {
        // Clear existing media in collection
        $model->clearMediaCollection($collection);

        // Assign new media
        $this->assign($model, $tempUploadIds, $collection);
    }

    /**
     * Assign a single temp upload to a model.
     *
     * @param HasMedia $model Target model
     * @param string $tempUploadId Temp upload ID
     * @param string $collection Media collection name
     * @return bool Success status
     */
    public function assignSingle(HasMedia $model, string $tempUploadId, string $collection = 'default'): bool
    {
        $tempUpload = TempUpload::notExpired()->find($tempUploadId);

        if (!$tempUpload) {
            return false;
        }

        $media = $tempUpload->getFirstMedia('temp');

        if (!$media) {
            return false;
        }

        // Copy media to target model
        $media->copy($model, $collection);

        // Delete temp upload
        $tempUpload->delete();

        return true;
    }
}
