<?php

namespace App\Http\Controllers;

use App\Models\CommercialRule;
use App\Models\MarketRule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Configurações > Mercado por consultor (planilha da gerência: cidade × segmento × consultores). */
class MarketRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketRule::query();
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('consultant_names', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }
        if ($segment = $request->input('segment')) {
            $query->where('segment', $segment);
        }
        $rows = $query->orderBy('city')->orderBy('segment')->get();
        $users = User::pluck('name', 'id');

        return response()->json([
            'data' => $rows->map(fn (MarketRule $r) => $this->payload($r, $users))->values(),
            'segments' => CommercialRule::current()->segments ?? [],
            'consultants' => User::where('is_employee', true)->orderBy('name')->get(['id', 'name']),
        ], 200);
    }

    public function show(MarketRule $marketRule)
    {
        return response()->json($this->payload($marketRule), 200);
    }

    public function store(Request $request)
    {
        $rule = MarketRule::create($this->validated($request));

        return response()->json($this->payload($rule), 201);
    }

    public function update(Request $request, MarketRule $marketRule)
    {
        $marketRule->update($this->validated($request));

        return response()->json($this->payload($marketRule->fresh()), 200);
    }

    public function destroy(MarketRule $marketRule)
    {
        $marketRule->delete();

        return response()->json([], 204);
    }

    private function validated(Request $request): array
    {
        $segments = array_column(CommercialRule::current()->segments ?? [], 'key');
        $data = $request->validate([
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|size:2',
            'segment' => ['required', Rule::in($segments)],
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'consultant_names' => 'nullable|array',
            'consultant_names.*' => 'nullable|string|max:120',
            'min_guests' => 'nullable|integer|min:0',
            'min_guests_high' => 'nullable|integer|min:0',
            'distance_km' => 'nullable|integer|min:0',
            'potential' => 'nullable|string|max:40',
            'served' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'city.required' => 'Informe a cidade.',
            'segment.required' => 'Escolha o segmento.',
            'state.size' => 'Informe a sigla do estado com 2 letras (ex.: MG).',
        ]);
        $data['state'] = isset($data['state']) ? strtoupper($data['state']) : null;
        $data['user_ids'] = array_values(array_unique(array_map('intval', $data['user_ids'] ?? [])));
        // Com usuários escolhidos na tela, os nomes da planilha passam a ser os deles.
        if ($data['user_ids']) {
            $data['consultant_names'] = User::whereIn('id', $data['user_ids'])->pluck('name')->all();
        } else {
            $data['consultant_names'] = array_values(array_filter(array_map('trim', $data['consultant_names'] ?? [])));
        }
        $data['served'] = $data['served'] ?? true;

        return $data;
    }

    private function payload(MarketRule $r, $users = null): array
    {
        $rules = CommercialRule::current();

        return [
            'id' => $r->id,
            'city' => $r->city,
            'state' => $r->state,
            'segment' => $r->segment,
            'segment_name' => $rules->segmentName($r->segment),
            'user_ids' => array_map('intval', $r->user_ids ?? []),
            'consultant_names' => $r->consultant_names ?? [],
            'consultants' => $r->consultantLabels(),
            'rotation' => count($r->consultantIds()) > 1,
            'min_guests' => $r->min_guests,
            'min_guests_high' => $r->min_guests_high,
            'distance_km' => $r->distance_km,
            'potential' => $r->potential,
            'served' => (bool) $r->served,
            'notes' => $r->notes,
            'updated_at' => $r->updated_at,
        ];
    }
}
