<?php

namespace App\Http\Requests\CentralAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use LogicException;

final class UploadCentralBrandLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['logo' => ['required', 'file', 'max:'.((int) ceil((int) config('media.max_upload_bytes') / 1024))]];
    }

    public function logo(): UploadedFile
    {
        $file = $this->file('logo');
        if (! $file instanceof UploadedFile) {
            throw new LogicException('Logo upload is missing.');
        }

return $file;
    }
}
