<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use App\Models\Configuration;

class Document
{
    public $pdf;

    public function __construct(
        private Model $model,
        private string $path = 'documents/',
        public ?string $fileName = null
    ) {
        if (!$this->model) {
            throw new \Exception("No model provided to Document helper.");
        }

        $company = Configuration::firstOrFail();
        $template = $this->setTemplate();

        if (!$this->pdf) {
            $this->pdf = Pdf::loadView($template, ['model' => $this->model, 'company' => $company]);
        }

        if (!$this->fileName) {
            $this->fileName = $this->model->id . '.pdf';
        }
    }

    public function download()
    {
        return $this->pdf->download($this->fileName);
    }

    public function stream()
    {
        return $this->pdf->stream($this->fileName);
    }

    public function save()
    {
        $path = storage_path("app/{$this->path}{$this->fileName}");
        $this->pdf->Output($path, 'F');
        return $path;
    }

    private function setTemplate()
    {
        return 'pdfs.' . strtolower(class_basename($this->model)) . '-pdf';
    }
}
