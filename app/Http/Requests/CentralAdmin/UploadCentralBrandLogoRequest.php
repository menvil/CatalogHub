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
        $maxKilobytes = (int) ceil((int) config('media.max_upload_bytes') / 1024);

        return ['logo' => ['required', 'file', 'max:'.$maxKilobytes]];
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
