<?php

namespace App\Http\Controllers;

use App\Services\ActivityHistoryService;
use Illuminate\Http\Request;

class ActivityHistoryController extends Controller
{
    public function __construct(private ActivityHistoryService $historyService)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeHistory($request);

        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'user_id' => 'nullable|integer',
            'event_id' => 'nullable|integer',
            'action' => 'nullable|string|in:' . implode(',', array_keys(ActivityHistoryService::ACTIONS)),
            'group' => 'nullable|string',
            'area' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $history = $this->historyService->paginate($filters, (int) ($filters['per_page'] ?? 25));

        return response()->json([
            'data' => $history->items(),
            'pagination' => [
                'page' => $history->currentPage(),
                'total_pages' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ], 200);
    }

    public function filters(Request $request)
    {
        $this->authorizeHistory($request);

        return response()->json($this->historyService->filterOptions(), 200);
    }

    private function authorizeHistory(Request $request): void
    {
        $user = $request->user();
        $allowed = $user && ($user->isAdmin() || $user->effectivePermissions()->contains('slug', 'historico-visualizar'));

        abort_unless($allowed, 403, 'Você não tem permissão para ver o histórico.');
    }
}
