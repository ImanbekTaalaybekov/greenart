<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PhotoService
{
    public function apply(
        Model $model,
        Request $request,
        string $folder,
        string $photoModelClass,
        ?string $foreignKey = null,
        string $fieldName = 'photos',
        string $disk = 'public',
    ): void {
        if (!$request->hasFile($fieldName)) {
            return;
        }

        if ($foreignKey === null) {
            $foreignKey = Str::snake(class_basename($model)) . '_id';
        }

        /** @var UploadedFile[]|UploadedFile|null $files */
        $files = $request->file($fieldName);

        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store("$folder/{$model->getKey()}", $disk);

            $photoModelClass::create([
                $foreignKey    => $model->getKey(),
                'path'         => $path,
                'original_name'=> $file->getClientOriginalName(),
                'mime_type'    => $file->getClientMimeType(),
                'size'         => $file->getSize(),
            ]);
        }
    }
}
