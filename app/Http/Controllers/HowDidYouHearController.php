<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHowDidYouHearRequest;
use Illuminate\Http\Request;
use App\Http\Resources\HowDidYouHearResource;
use App\Http\Requests\UpdateHowDidYouHearRequest;
use App\Models\HowDidYouHear;

class HowDidYouHearController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(HowDidYouHearController::class, 'howDidYouHear');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = HowDidYouHear::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $query->filterByField($field, $value);
                }
            }
        }

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderBy($sorteableField, $direction);
        }

        $howDidYouHear = HowDidYouHear::paginate($perPage);

        return response()->json([
            'data' => HowDidYouHearResource::collection($howDidYouHear),
            'pagination' => [
                'page' => $howDidYouHear->currentPage(),
                'total_pages' => $howDidYouHear->lastPage(),
                'per_page' => $howDidYouHear->perPage(),
                'total' => $howDidYouHear->total(),
            ],
            'sortable_fields' => ['name'],
            'filterable_fields' => ['name'],
        ], 200);
    }

    public function show(HowDidYouHear $howDidYouHear)
    {
        return response()->json(new HowDidYouHearResource($howDidYouHear), 200);
    }

    public function store(StoreHowDidYouHearRequest $request)
    {
        $data = $request->all();
        $howDidYouHear = HowDidYouHear::create($data);
        return response()->json(new HowDidYouHearResource($howDidYouHear), 201);
    }

    public function update(UpdateHowDidYouHearRequest $request, HowDidYouHear $howDidYouHear)
    {
        $data = $request->all();
        $data['slug'] = slug($data['name']);
        $howDidYouHear->update($data);
        return response()->json(new HowDidYouHearResource($howDidYouHear), 200);
    }

    public function destroy(HowDidYouHear $howDidYouHear)
    {
        $howDidYouHear->delete();
        return response([], 204);
    }

}
