<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class AttachmentService
{
    public function uploadMany(array $files, Model $attachable, string $directory)
    {
        foreach ($files as $file) {
            $path = $file->store($directory, 'public');

            $attachable->attachments()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
            ]);
        }
    }
}
