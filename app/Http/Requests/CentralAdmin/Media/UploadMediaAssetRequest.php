<?php

namespace App\Http\Requests\CentralAdmin\Media;

use App\Rules\Media\ValidMediaDimensions;
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
                'mimetypes:'.implode(',', config('media.allowed_upload_mimes', [])),
                'max:10240',
                new ValidMediaDimensions(
                    maxWidth: (int) config('media.max_upload_width'),
                    maxHeight: (int) config('media.max_upload_height'),
                    maxPixels: (int) config('media.max_upload_pixels'),
                ),
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
