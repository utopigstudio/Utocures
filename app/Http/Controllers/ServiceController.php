<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Http\Requests\Service\ServiceStoreRequest;
use App\Http\Requests\Service\ServiceIndexRequest;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(ServiceIndexRequest $request)
    {
        $colors = Service::getColors();
        $searchable = ['name', 'description'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'filter_color' => $request->validated('filter_color'),
        ];

        $services = Service::filter($filters, $searchable)->get();

        return Inertia::render('services/List', [
            'data' => $services,
            'colors' => $colors,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $colors = Service::getColors();

        return Inertia::render('services/Form', [
            'colors' => $colors,
        ]);
    }

    public function edit(Service $service)
    {
        $colors = Service::getColors();

        $service->load('tasks');

        return Inertia::render('services/Form', [
            'service' => $service,
            'colors' => $colors,
        ]);
    }

    public function store(ServiceStoreRequest $request)
    {
        $data = $request->validated();
        $tasks = $data['tasks'] ?? [];

        $service = Service::create($request->except('tasks'));

        $service->tasks()->delete();

        foreach ($tasks as $task) {
            $service->tasks()->create($task);
        }

        return to_route('services.index')->with('success', "Servicio {$service->name} creado correctamente.");
    }

    public function update(ServiceStoreRequest $request, Service $service)
    {
        $data = $request->validated();
        $tasks = $data['tasks'] ?? [];

        $service->update($request->except('tasks'));

        $existingIds = collect($tasks)->pluck('id')->filter()->toArray();
        $service->tasks()->whereNotIn('id', $existingIds)->delete();

        foreach ($tasks as $taskData) {
            if (isset($taskData['id'])) {
                $service->tasks()->where('id', $taskData['id'])->update($taskData);
            } else {
                $service->tasks()->create($taskData);
            }
        }

        return to_route('services.index')->with('success', "Servicio {$service->name} actualizado correctamente.");
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return to_route('services.index')->with('success', "Servicio {$service->name} eliminado correctamente.");
    }
}
