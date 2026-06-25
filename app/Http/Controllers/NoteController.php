<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\NoteStoreRequest;
use App\Models\EmployeeTimeRecord;
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
        $data = $request->validated();

        $this->validateIncidentPayload($request, $model, $data);

        $model->notes()->create([
            ...$data,
            'type' => $data['type'] ?? Note::TYPE_GENERAL,
            'user_id' => $request->user()->id
        ]);

        return back()->with('success', 'Nota creada correctamente.');
    }

    public function update(NoteStoreRequest $request, Note $note)
    {
        [$model, $type, $id] = $this->resolveResource($request);

        $this->verifyOwnership($note, $model, $type, $id);

        $note->update($request->safe()->only('content'));

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

    protected function validateIncidentPayload(Request $request, Model $model, array $data): void
    {
        if (($data['type'] ?? Note::TYPE_GENERAL) !== Note::TYPE_INCIDENT) {
            return;
        }

        if (!$model instanceof Client) {
            abort(422, 'Las incidencias solo pueden registrarse sobre clientes.');
        }

        $timeRecord = EmployeeTimeRecord::with('assignedHour')->findOrFail($data['employee_time_record_id']);

        if (!$timeRecord->assignedHour || $timeRecord->assignedHour->client_id !== $model->id) {
            abort(422, 'El registro horario indicado no pertenece al cliente.');
        }

        if ($request->user()?->employee && $timeRecord->employee_id !== $request->user()->employee->id) {
            abort(403, 'Solo puedes registrar incidencias en tus propios registros horarios.');
        }
    }
}
