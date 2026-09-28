<?php

namespace App\Http\Controllers;

use App\Models\Ceremonial;
use App\Models\Decorator;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PartnerDirectoryController extends Controller
{
    private const TYPES = [
        'partner'    => Partner::class,
        'decorator'  => Decorator::class,
        'ceremonial' => Ceremonial::class,
    ];

    private const SORTABLE_FIELDS = ['name', 'responsible', 'email', 'type'];

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $filters = $this->filters($request);

        $types = array_intersect_key(self::TYPES, array_flip((array) ($filters['type'] ?? [])));
        $types = $types ?: self::TYPES;

        $union = null;
        foreach ($types as $type => $model) {
            $select = $model::query()
                ->select('id', 'name', 'responsible', 'email', 'phone_1', 'phone_2')
                ->addSelect(DB::raw("'$type' as type"));

            $union = $union ? $union->unionAll($select) : $select;
        }

        $query = DB::query()->fromSub($union, 'directory');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('responsible', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        $orderBy = in_array($request->input('order_by'), self::SORTABLE_FIELDS) ? $request->input('order_by') : 'name';
        $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
        $query->orderBy($orderBy, $direction)->orderBy('type')->orderBy('id');

        $entries = $query->paginate($perPage);

        return response()->json([
            'data' => $entries->items(),
            'pagination' => [
                'page' => $entries->currentPage(),
                'total_pages' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
            'sortable_fields' => self::SORTABLE_FIELDS,
            'filterable_fields' => ['search', 'type'],
        ], 200);
    }

    private function filters(Request $request): array
    {
        $filterBy = json_decode($request->input('filter_by', '[]'), true);

        $filters = [];
        foreach (is_array($filterBy) ? $filterBy : [] as $filter) {
            if (is_array($filter) && $filter) {
                $filters[array_keys($filter)[0]] = array_values($filter)[0];
            }
        }

        return $filters;
    }
}
