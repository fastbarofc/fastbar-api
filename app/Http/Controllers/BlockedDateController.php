<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBlockedDateRequest;
use App\Http\Requests\StoreBlockedDateRequest;
use App\Http\Requests\UpdateBlockedDateRequest;
use App\Models\BlockedDate;
use App\Http\Resources\BlockedDateResource;
use Carbon\Carbon;

class BlockedDateController extends Controller
{

    public function index(IndexBlockedDateRequest $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = BlockedDate::query();

        // Intervalo usado pelo calendario para trazer so os bloqueios da tela
        // atual, sem depender da paginacao padrao.
        if ($request->filled('start')) {
            $query->whereDate('date', '>=', Carbon::parse($request->input('start'))->toDateString());
        }

        if ($request->filled('end')) {
            $query->whereDate('date', '<=', Carbon::parse($request->input('end'))->toDateString());
        }

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $query->filterByField(
                        field: $field,
                        value: $value,
                    );
                }
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        // Ordem estável: o id desempata, para nenhuma linha sumir ou repetir entre páginas.
        $query->orderBy($query->getModel()->getTable() . ".id");
        $blockedDates = $query->paginate($perPage);

        return response()->json([
            'data' => BlockedDateResource::collection($blockedDates),
            'pagination' => [
                'page' => $blockedDates->currentPage(),
                'total_pages' => $blockedDates->lastPage(),
                'per_page' => $blockedDates->perPage(),
                'total' => $blockedDates->total(),
            ],
            'sortable_fields' => BlockedDate::sortableFields(),
            'filterable_fields' => BlockedDate::filterableFields()
        ], 200);
    }

    public function store(StoreBlockedDateRequest $request)
    {
        $data = $request->all();
        $blockedDate = BlockedDate::create($data);
        return response()->json(new BlockedDateResource($blockedDate), 201);
    }

    public function show(BlockedDate $blockedDate)
    {
        return response()->json(new BlockedDateResource($blockedDate), 200);
    }

    public function update(UpdateBlockedDateRequest $request, BlockedDate $blockedDate)
    {
        $data = $request->all();
        $blockedDate->update($data);
        return response()->json(new BlockedDateResource($blockedDate), 200);
    }

    public function destroy(BlockedDate $blockedDate)
    {
        $blockedDate->delete();
        return response()->json(null, 204);
    }
}
