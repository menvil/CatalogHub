<?php

namespace App\Services\Media;

interface ImageIngestor
{
    public function ingest(ImageInput $input): NormalizedImage;
}
