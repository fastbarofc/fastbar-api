<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHowDidYouHearRequest;
use Illuminate\Http\Request;
use App\Http\Resources\HowDidYouHearResource;
use App\Http\Requests\UpdateHowDidYouHearRequest;
use App\Models\HowDidYouHear;

class HowDidYouHearController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = HowDidYouHear::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as $field => $value) {
                $query->where($field, $value);
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

        $how_you_did_hear = HowDidYouHear::paginate($perPage);

        return response()->json([
            'data' => HowDidYouHearResource::collection($how_you_did_hear),
            'pagination' => [
                'current_page' => $how_you_did_hear->currentPage(),
                'total_pages' => $how_you_did_hear->lastPage(),
                'per_page' => $how_you_did_hear->perPage(),
                'total' => $how_you_did_hear->total(),
            ],
            'sortable_fields' => ['name'],
            'filterable_fields' => ['name'],
        ], 200);
    }

    public function show(HowDidYouHear $how_you_did_hear)
    {
        return response()->json(new HowDidYouHearResource($how_you_did_hear), 200);
    }

    public function store(StoreHowDidYouHearRequest $request)
    {
        $data = $request->all();
        $how_you_did_hear = HowDidYouHear::create($data);
        return response()->json(new HowDidYouHearResource($how_you_did_hear), 201);
    }

    public function update(UpdateHowDidYouHearRequest $request, HowDidYouHear $how_you_did_hear)
    {
        $data = $request->all();
        $data['slug'] = slug($data['name']);
        $how_you_did_hear->update($data);
        return response()->json(new HowDidYouHearResource($how_you_did_hear), 200);
    }

    public function destroy(HowDidYouHear $how_you_did_hear)
    {
        $how_you_did_hear->delete();
        return response([], 204);
    }

}
