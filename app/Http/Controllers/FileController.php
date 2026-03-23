<?php

namespace App\Http\Controllers;

use App\Http\Requests\File\FileStoreRequest;
use App\Models\File;
use App\Models\Client;
use App\Models\Budget;
use App\Models\Employee;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function store(FileStoreRequest $request)
    {
        [$model] = $this->resolveResource($request);

        $model->files()->create($request->validated());

        return back()->with('success', 'Archivo subido correctamente.');
    }

    public function download(Request $request, File $file)
    {
        [$model, $type, $id] = $this->resolveResource($request);

        $this->verifyOwnership($file, $model, $type, $id);

        return response()->download(Storage::disk('public')->path($file->path), $file->name);
    }

    public function destroy(Request $request, File $file)
    {
        [$model, $type, $id] = $this->resolveResource($request);

        $this->verifyOwnership($file, $model, $type, $id);

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    protected function resolveResource(Request $request): array
    {
        $type = $request->query('resource');
        $id = $request->query('id');

        if (!$type || !$id) {
            abort(422, 'Faltan parámetros resource o id');
        }

        $modelClass = $this->resolveModel($type);
        $model = $modelClass::findOrFail($id);

        return [$model, $type, $id];
    }

    protected function resolveModel(string $type): string
    {
        return match ($type) {
            'client' => Client::class,
            'employee' => Employee::class,
            'budget' => Budget::class,
            'contract' => Contract::class,
            default => abort(404, 'Tipo de recurso no válido'),
        };
    }

    protected function verifyOwnership(File $file, Model $model, string $type, string|int $id): void
    {
        $expectedType = get_class($model);
        $belongs = $file->fileable_type === $expectedType && $file->fileable_id == $id;

        if (!$belongs) {
            abort(403, "El archivo no pertenece al recurso indicado ($type #$id)");
        }
    }
}
