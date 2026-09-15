<?php

namespace App\Http\Controllers;

use App\Http\Requests\Resource\ResourceGeocodeRequest;
use App\Http\Requests\Resource\ResourceIndexRequest;
use App\Http\Requests\Resource\ResourceStoreRequest;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;
use RuntimeException;

class ResourceController extends Controller
{
    public function index(ResourceIndexRequest $request): Response
    {
        $filters = $request->validated();

        $resources = Resource::query()
            ->filter($filters, ['name', 'type', 'address', 'description'])
            ->when($filters['filter_type'] ?? null, fn ($query, $type) => $query->where('type', 'like', "%{$type}%"))
            ->orderBy('name')
            ->get();

        $types = Resource::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return Inertia::render('resources/List', [
            'data' => $resources,
            'types' => $types,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('resources/Form', [
            'types' => $this->resourceTypes(),
        ]);
    }

    public function edit(Resource $resource): Response
    {
        return Inertia::render('resources/Form', [
            'resource' => $resource,
            'types' => $this->resourceTypes(),
        ]);
    }

    public function store(ResourceStoreRequest $request)
    {
        $data = $request->validated();
        $data = [...$data, ...$this->coordinatesFor($data)];

        $resource = Resource::create($data);

        return to_route('resources.index')
            ->with('success', __('resources.created', ['name' => $resource->name]));
    }

    public function update(ResourceStoreRequest $request, Resource $resource)
    {
        $data = $request->validated();

        if (str($data['address'])->squish()->toString() !== str($resource->address)->squish()->toString()) {
            $data = [...$data, ...$this->coordinatesFor($data)];
        } else {
            $data['latitude'] = $resource->latitude;
            $data['longitude'] = $resource->longitude;
        }

        $resource->update($data);

        return to_route('resources.index')
            ->with('success', __('resources.updated', ['name' => $resource->name]));
    }

    public function destroy(Resource $resource)
    {
        $name = $resource->name;
        $resource->delete();

        return to_route('resources.index')
            ->with('success', __('resources.deleted', ['name' => $name]));
    }

    public function geocode(ResourceGeocodeRequest $request): JsonResponse
    {
        return response()->json($this->geocodeAddress($request->validated('address')));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{latitude: float, longitude: float}
     */
    private function coordinatesFor(array $data): array
    {
        $result = $this->geocodeAddress($data['address']);

        return [
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
        ];
    }

    /**
     * @return array{latitude: float, longitude: float, display_name: string}
     */
    private function geocodeAddress(string $address): array
    {
        try {
            $result = $this->getGeocode($address);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'address' => __('resources.geocoding_unavailable'),
            ]);
        }

        if ($result === null) {
            throw ValidationException::withMessages([
                'address' => __('resources.address_not_found'),
            ]);
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function resourceTypes(): array
    {
        return Resource::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->all();
    }

    public function getGeocode(string $address): ?array
    {
        $normalizedAddress = str($address)->squish()->toString();
        $cacheKey = 'geocoding:'.sha1(mb_strtolower($normalizedAddress));

        return Cache::remember($cacheKey, now()->addHour(), function () use ($normalizedAddress) {
            $response = Http::baseUrl(config('services.nominatim.base_url'))
                ->acceptJson()
                ->withUserAgent(config('services.nominatim.user_agent'))
                ->timeout(10)
                ->retry(2, 300, throw: false)
                ->get('/search', [
                    'q' => $normalizedAddress,
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'addressdetails' => 1,
                    'accept-language' => app()->getLocale(),
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('The geocoding provider returned an unsuccessful response.');
            }

            $result = $response->json('0');

            if (! is_array($result) || ! isset($result['lat'], $result['lon'])) {
                return null;
            }

            return [
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
                'display_name' => (string) ($result['display_name'] ?? $normalizedAddress),
            ];
        });
    }
}
