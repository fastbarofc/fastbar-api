<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServedCityRequest;
use App\Http\Resources\ServedCityResource;
use App\Models\ServedCity;
use Illuminate\Http\Request;

/** Configurações > Cidades atendidas. */
class ServedCityController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $query = ServedCity::query()->with('user');

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                foreach (json_decode($filterable, true) as $filter) {
                    $field = array_keys($filter)[0];
                    $query->filterByField(field: $field, value: $filter[$field]);
                }
            }
        }

        $orderBy = $request->input('order_by');
        $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
        if ($orderBy && in_array($orderBy, ServedCity::sortableFields(), true)) {
            $query->orderBy("served_cities.$orderBy", $direction);
        } else {
            $query->orderBy('served_cities.name');
        }

        $cities = $query->paginate($perPage);

        return response()->json([
            'data' => ServedCityResource::collection($cities),
            'pagination' => [
                'page' => $cities->currentPage(),
                'total_pages' => $cities->lastPage(),
                'per_page' => $cities->perPage(),
                'total' => $cities->total(),
            ],
            'sortable_fields' => ServedCity::sortableFields(),
            'filterable_fields' => ServedCity::filterableFields(),
        ], 200);
    }

    public function store(ServedCityRequest $request)
    {
        $city = ServedCity::create($this->payload($request));

        return response()->json(new ServedCityResource($city->load('user')), 201);
    }

    public function show(ServedCity $servedCity)
    {
        return response()->json(new ServedCityResource($servedCity->load('user')), 200);
    }

    public function update(ServedCityRequest $request, ServedCity $servedCity)
    {
        $servedCity->update($this->payload($request));

        return response()->json(new ServedCityResource($servedCity->load('user')), 200);
    }

    public function destroy(ServedCity $servedCity)
    {
        $servedCity->delete();

        return response()->json([], 204);
    }

    private function payload(ServedCityRequest $request): array
    {
        $data = $request->validated();
        $data['state'] = isset($data['state']) ? strtoupper($data['state']) : null;
        $data['served'] = $data['served'] ?? true;
        $data['segment_rules'] = array_values(array_map(
            fn ($r) => ['event_type_id' => (int) $r['event_type_id'], 'user_id' => (int) $r['user_id']],
            $data['segment_rules'] ?? []
        ));

        return $data;
    }
}
