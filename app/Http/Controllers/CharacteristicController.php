<?php

namespace App\Http\Controllers;

use App\Models\Characteristic;
use App\Http\Requests\Characteristic\CharacteristicIndexRequest;
use App\Http\Requests\Characteristic\CharacteristicStoreRequest;
use Inertia\Inertia;

class CharacteristicController extends Controller
{
    private const LIST_FIELDS = ['id', 'name', 'created_at'];

    public function index(CharacteristicIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir  = $request->validated('dir') ?? 'desc';
        $searchable = ['name'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
        ];

        $characteristics = Characteristic::select(self::LIST_FIELDS)
            ->with('options')
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('characteristics/List', [
            'data' => $characteristics,
            'sort' => $sort,
            'dir' => $dir,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('characteristics/Form');
    }

    public function edit(Characteristic $characteristic)
    {
        $characteristic = $characteristic->load(['options']);

        return Inertia::render('characteristics/Form', [
            'characteristic' => $characteristic,
        ]);
    }

    public function store(CharacteristicStoreRequest $request)
    {
        $characteristic = Characteristic::create($request->validated());

        if ($request->has('options')) {
            $characteristic->options()->createMany($request->validated('options'));
        }

        return to_route('characteristics.index')->with('success', "Característica {$characteristic->name} creada correctamente.");
    }

    public function update(CharacteristicStoreRequest $request, Characteristic $characteristic)
    {
        $data = $request->validated();

        $characteristic->update($data);

        $optionsData = collect($data['options'] ?? []);

        $existingIds = $characteristic->options()->pluck('id')->toArray();
        $incomingIds = $optionsData->pluck('id')->filter()->toArray();
        $toDelete = array_diff($existingIds, $incomingIds);

        if ($toDelete) {
            $characteristic->options()->whereIn('id', $toDelete)->delete();
        }

        foreach ($optionsData as $option) {
            if (!empty($option['id'])) {
                $characteristic->options()->where('id', $option['id'])->update($option);
            } else {
                $characteristic->options()->create($option);
            }
        }

        return to_route('characteristics.index')->with('success', "Característica {$characteristic->name} actualizada correctamente.");
    }

    public function destroy(Characteristic $characteristic)
    {
        $characteristic->delete();

        return to_route('characteristics.index')->with('success', "Característica {$characteristic->name} eliminada correctamente.");
    }
}
