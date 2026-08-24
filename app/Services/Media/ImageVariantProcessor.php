<?php

namespace App\Services\Media;

interface ImageVariantProcessor
{
    /** @return array{bytes:string,width:int,height:int} */
    public function process(string $source, MediaVariantSpecification $spec): array;
}
