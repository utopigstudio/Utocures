<?php

namespace App\Models\Traits;

trait Downloadable
{
    public function downloadPdf()
    {
        $documentService = new \App\Services\Document($this);
        return $documentService->download();
    }
}
