<?php

namespace App\Http\Controllers;

use App\Models\CommercialRule;
use App\Models\Lead;
use App\Models\LeadCommission;
use App\Models\LeadCover;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Comercial > Relatório de comissão.
 * Negócios ganhos no mês (data em que foram para Ganho), por consultor. A gerência informa valor ideal, valor vendido,
 * brindes, cortesia e % do consultor; o consultor só vê os próprios negócios.
 *
 * Cálculo (regra da Patrícia, 27/10/2026; valores ainda a definir):
 *   base       = valor vendido − brindes − cortesia
 *   % efetivo  = % do consultor × (valor vendido ÷ valor ideal)   (sem valor ideal, vale o % do consultor)
 *   comissão   = base × % efetivo
 */
class LeadCommissionController extends Controller
{
    private const SEE_ALL_PERMISSION = 'comercial-funil-ver-todos';

    public function index(Request $request)
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'user_id' => ['nullable'],
        ]);
        $user = $request->user();
        $seeAll = $this->canSeeAll($user);
        $month = isset($data['month']) ? Carbon::createFromFormat('Y-m-d', $data['month'] . '-01') : now()->subMonthNoOverflow()->startOfMonth();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $rules = CommercialRule::current();
        $default = $rules->commission_percent_default !== null ? (float) $rules->commission_percent_default : null;

        $query = Lead::query()
            ->where('stage', 'ganho')
            ->whereBetween('stage_changed_at', [$from, $to])
            ->with(['user:id,name', 'commission', 'eventType:id,name'])
            ->orderBy('stage_changed_at');
        if (!$seeAll) {
            $query->whereIn('user_id', array_values(array_unique(array_merge([(int) $user->id], LeadCover::coveredUserIds((int) $user->id)))));
        } elseif (($data['user_id'] ?? '') === 'none') {
            $query->whereNull('user_id');
        } elseif (!empty($data['user_id'])) {
            $query->where('user_id', (int) $data['user_id']);
        }

        $rows = $query->get()->map(fn (Lead $l) => $this->row($l, $default))->values();

        $summary = $rows->groupBy('consultant')->map(fn ($g, $name) => [
            'consultant' => $name,
            'deals' => $g->count(),
            'sold' => round($g->sum('sold_value'), 2),
            'commission' => round($g->sum('commission'), 2),
            'pending' => $g->where('pending', true)->count(),
        ])->sortByDesc('sold')->values();

        return response()->json([
            'month' => $from->format('Y-m'),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'can_edit' => $seeAll,
            'default_percent' => $default,
            'rows' => $rows,
            'summary' => $summary,
            'totals' => [
                'deals' => $rows->count(),
                'sold' => round($rows->sum('sold_value'), 2),
                'commission' => round($rows->sum('commission'), 2),
                'pending' => $rows->where('pending', true)->count(),
            ],
        ]);
    }

    /** Gerência grava os valores do fechamento de um negócio ganho. */
    public function update(Request $request, Lead $lead)
    {
        $this->guardEdit($request);
        if ($lead->stage !== 'ganho') {
            return response()->json(['message' => 'Só negócios ganhos entram na comissão.'], 422);
        }
        $data = $request->validate([
            'ideal_value' => ['nullable', 'numeric', 'min:0'],
            'sold_value' => ['nullable', 'numeric', 'min:0'],
            'gifts_value' => ['nullable', 'numeric', 'min:0'],
            'courtesy_value' => ['nullable', 'numeric', 'min:0'],
            'percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        LeadCommission::updateOrCreate(['lead_id' => $lead->id], $data + ['updated_by' => $request->user()?->id]);
        $lead->load(['user:id,name', 'commission', 'eventType:id,name']);
        $default = CommercialRule::current()->commission_percent_default;

        return response()->json($this->row($lead, $default !== null ? (float) $default : null));
    }

    /** % padrão do consultor (vale para os negócios sem % próprio). */
    public function settings(Request $request)
    {
        $this->guardEdit($request);
        $data = $request->validate(['default_percent' => ['nullable', 'numeric', 'min:0', 'max:100']]);
        $rules = CommercialRule::current();
        $rules->update(['commission_percent_default' => $data['default_percent'] ?? null, 'updated_by' => $request->user()?->id]);

        return response()->json(['default_percent' => $rules->commission_percent_default !== null ? (float) $rules->commission_percent_default : null]);
    }

    private function row(Lead $l, ?float $default): array
    {
        $c = $l->commission;
        $sold = $c?->sold_value !== null ? (float) $c->sold_value : (float) ($l->final_value ?? $l->estimated_value ?? 0);
        $ideal = $c?->ideal_value !== null ? (float) $c->ideal_value : null;
        $gifts = (float) ($c?->gifts_value ?? 0);
        $courtesy = (float) ($c?->courtesy_value ?? 0);
        $percent = $c?->percent !== null ? (float) $c->percent : $default;
        $base = max($sold - $gifts - $courtesy, 0);
        $factor = $ideal && $ideal > 0 ? $sold / $ideal : 1.0;
        $effective = $percent !== null ? $percent * $factor : null;

        return [
            'lead_id' => $l->id,
            'name' => $l->name,
            'consultant' => $l->user?->name ?? ($l->imported_owner ?: 'Sem consultor'),
            'user_id' => $l->user_id,
            'event_type' => $l->eventType?->name,
            'city' => $l->city,
            'event_date' => $l->event_date?->toDateString(),
            'won_at' => $l->stage_changed_at?->toDateString(),
            'ideal_value' => $ideal,
            'sold_value' => round($sold, 2),
            'sold_is_default' => $c?->sold_value === null,
            'gifts_value' => $c?->gifts_value !== null ? (float) $c->gifts_value : null,
            'courtesy_value' => $c?->courtesy_value !== null ? (float) $c->courtesy_value : null,
            'percent' => $c?->percent !== null ? (float) $c->percent : null,
            'percent_used' => $percent,
            'ratio' => $ideal && $ideal > 0 ? round($sold * 100 / $ideal, 1) : null,
            'effective_percent' => $effective !== null ? round($effective, 3) : null,
            'base' => round($base, 2),
            'commission' => $effective !== null ? round($base * $effective / 100, 2) : 0.0,
            'pending' => $ideal === null || $percent === null,
            'notes' => $c?->notes,
        ];
    }

    private function canSeeAll($user): bool
    {
        if (!$user) {
            return false;
        }
        $slugs = method_exists($user, 'effectivePermissions')
            ? $user->effectivePermissions()->pluck('slug')->all()
            : $user->getAllPermissions()->pluck('slug')->all();

        return in_array(self::SEE_ALL_PERMISSION, $slugs, true);
    }

    private function guardEdit(Request $request): void
    {
        if (!$this->canSeeAll($request->user())) {
            abort(response()->json(['message' => 'Só a gerência comercial preenche a comissão.'], 403));
        }
    }
}
