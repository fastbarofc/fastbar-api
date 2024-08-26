<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLostReasonRequest;
use App\Http\Requests\UpdateLostReasonRequest;
use App\Http\Resources\LostReasonResource;
use App\Models\LostReason;
use Illuminate\Http\Request;

class LostReasonController extends Controller
{
     public function __construct()
    {
        $this->authorizeResource(LostReasonController::class, 'lost_reason');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = LostReason::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as  $field => $value) {
                    $query->filterByField($field, $value);
                }
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $lostReason = $query->paginate($perPage);

        return response()->json([
            'data' => LostReasonResource::collection($lostReason),
            'pagination' => [
                'current_page' => $lostReason->currentPage(),
                'total_pages' => $lostReason->lastPage(),
                'per_page' => $lostReason->perPage(),
                'total' => $lostReason->total(),
            ],
            'sortable_fields' => LostReason::sortableFields(),
            'filterable_fields' => LostReason::filterableFields()
        ], 200);
    }

    public function store(StoreLostReasonRequest $request)
    {
        $lostReason = LostReason::create($request->all());
        return response()->json(new LostReasonResource($lostReason), 201);
    }

    public function show(LostReason $lostReason)
    {
        return response()->json(new LostReasonResource($lostReason), 200);
    }

    public function update(UpdateLostReasonRequest $request, LostReason $lostReason)
    {
        $lostReason->update($request->all());
        return response()->json(new LostReasonResource($lostReason), 200);
    }

    public function destroy(LostReason $lostReason)
    {
        $lostReason->delete();
        return response()->json([], 204);
    }
}
