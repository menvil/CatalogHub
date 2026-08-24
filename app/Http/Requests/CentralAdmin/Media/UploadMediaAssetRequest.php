<?php

namespace App\Http\Requests\CentralAdmin\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use LogicException;

final class UploadMediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.((int) ceil((int) config('media.max_upload_bytes') / 1024)),
            ],
        ];
    }

    public function uploadedFile(): UploadedFile
    {
        $file = $this->file('file');

        if (! $file instanceof UploadedFile) {
            throw new LogicException('The validated media upload is not an uploaded file.');
        }

        return $file;
    }
}
