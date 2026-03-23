<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\NoteStoreRequest;
use App\Models\Note;
use App\Models\Client;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(NoteStoreRequest $request)
    {
        [$model] = $this->resolveResource($request);

        $model->notes()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id
        ]);

        return back()->with('success', 'Nota creada correctamente.');
    }

    public function update(NoteStoreRequest $request, Note $note)
    {
        [$model, $type, $id] = $this->resolveResource($request);

        $this->verifyOwnership($note, $model, $type, $id);

        $note->update($request->validated());

        return back()->with('success', 'Nota actualizada correctamente.');
    }

    public function destroy(Request $request, Note $note)
    {
        [$model, $type, $id] = $this->resolveResource($request);

        $this->verifyOwnership($note, $model, $type, $id);

        $note->delete();

        return back()->with('success', 'Nota eliminada correctamente.');
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
            default => abort(404, 'Tipo de recurso no válido'),
        };
    }

    protected function verifyOwnership(Note $note, Model $model, string $type, string|int $id): void
    {
        $expectedType = get_class($model);
        $belongs = $note->noteable_type === $expectedType && $note->noteable_id == $id;

        if (!$belongs) {
            abort(403, "La nota no pertenece al recurso indicado ($type #$id)");
        }
    }
}
