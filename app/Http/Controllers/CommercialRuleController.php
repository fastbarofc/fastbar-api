<?php

namespace App\Http\Controllers;

use App\Models\CommercialRule;
use App\Models\EventType;
use App\Models\Lead;
use App\Models\LeadCover;
use App\Models\User;
use Illuminate\Http\Request;

/** Configurações > Regras comerciais (uma única configuração; alterações ficam no Histórico). */
class CommercialRuleController extends Controller
{
    public function show()
    {
        return response()->json($this->payload(CommercialRule::current()), 200);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'high_season_months'          => 'nullable|array',
            'high_season_months.*'        => 'integer|min:1|max:12',
            'viability_min_default'       => 'required|numeric|min:0|max:100',
            'viability_min_by_event_type' => 'nullable|array',
            'viability_min_by_event_type.*' => 'nullable|numeric|min:0|max:100',
            'max_discount_percent'        => 'nullable|numeric|min:0|max:100',
            'proposal_validity_days'      => 'required|integer|min:1|max:365',
            'pre_reserve_days'            => 'required|integer|min:1|max:365',
            'max_events_per_day'          => 'nullable|integer|min:1|max:50',
            'max_guests_per_day'          => 'nullable|integer|min:1|max:100000',
            'max_events_per_week'         => 'nullable|integer|min:1|max:200',
            'complex_max_events_per_day'  => 'nullable|integer|min:1|max:50',
            'complex_max_events_per_week' => 'nullable|integer|min:1|max:200',
            'complex_guests_threshold'    => 'nullable|integer|min:1|max:100000',
            'complex_keywords'            => 'nullable|array',
            'complex_keywords.*'          => 'nullable|string|max:80',
            'max_small_per_weekend'       => 'nullable|integer|min:1|max:50',
            'small_keywords'              => 'nullable|array',
            'small_keywords.*'            => 'nullable|string|max:80',
            'max_pre_events_per_weekend'  => 'nullable|integer|min:1|max:50',
            'pre_event_max_guests'        => 'nullable|integer|min:1|max:100000',
            'pre_event_keywords'          => 'nullable|array',
            'pre_event_keywords.*'        => 'nullable|string|max:80',
            'first_contact_hours'         => 'required|integer|min:1|max:720',
            'business_hours_start'        => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'business_hours_end'          => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/', 'after:business_hours_start'],
            'business_days'               => 'required|array|min:1',
            'business_days.*'             => 'integer|min:1|max:7',
            'rotation_user_ids'           => 'nullable|array',
            'rotation_user_ids.*'         => 'integer|exists:users,id',
            'rotation_event_type_ids'     => 'nullable|array',
            'rotation_event_type_ids.*'   => 'integer|exists:event_types,id',
            'rotation_include_unknown'    => 'boolean',
            'segments'                    => 'nullable|array',
            'segments.*.key'              => 'required|string|max:40',
            'segments.*.name'             => 'required|string|max:80',
            'segments.*.event_type_ids'   => 'nullable|array',
            'segments.*.event_type_ids.*' => 'integer|exists:event_types,id',
            'max_distance_km'             => 'nullable|integer|min:1|max:5000',
            'no_answer_attempts'          => 'required|integer|min:1|max:50',
            'no_answer_channels'          => 'required|integer|min:1|max:10',
            'no_answer_days'              => 'required|integer|min:1|max:365',
            'stage_max_days'              => 'nullable|array',
            'stage_max_days.*'            => 'nullable|integer|min:0|max:365',
            'payment_terms'               => 'nullable|string',
            'notes'                       => 'nullable|string',
        ], [
            'viability_min_default.required' => 'Informe a viabilidade mínima padrão.',
            'proposal_validity_days.required' => 'Informe a validade da proposta em dias.',
            'business_hours_start.regex' => 'Informe o início do horário comercial no formato 08:00.',
            'business_hours_end.regex' => 'Informe o fim do horário comercial no formato 18:00.',
            'business_hours_end.after' => 'O fim do horário comercial precisa ser depois do início.',
            'business_days.required' => 'Marque pelo menos um dia do horário comercial.',
        ]);

        $data['high_season_months'] = array_values(array_unique(array_map('intval', $data['high_season_months'] ?? [])));
        $data['viability_min_by_event_type'] = array_filter($data['viability_min_by_event_type'] ?? [], fn ($v) => $v !== null && $v !== '');
        $data['stage_max_days'] = array_intersect_key(
            array_filter($data['stage_max_days'] ?? [], fn ($v) => $v !== null && $v !== ''),
            array_flip(array_keys(Lead::STAGE_MAX_DAYS))
        );
        $data['business_days'] = array_values(array_unique(array_map('intval', $data['business_days'] ?? [])));
        sort($data['business_days']);
        $data['rotation_user_ids'] = array_values(array_unique(array_map('intval', $data['rotation_user_ids'] ?? [])));
        $data['rotation_event_type_ids'] = array_values(array_unique(array_map('intval', $data['rotation_event_type_ids'] ?? [])));
        foreach (['complex_keywords', 'small_keywords', 'pre_event_keywords'] as $field) {
            $data[$field] = array_values(array_unique(array_filter(array_map(fn ($w) => trim((string) $w), $data[$field] ?? []), fn ($w) => $w !== '')));
        }
        $data['updated_by'] = $request->user()?->id;

        $rules = CommercialRule::current();
        $rules->update($data);
        Lead::forgetRulesCache();

        return response()->json($this->payload($rules->fresh()), 200);
    }

    private function payload(CommercialRule $rules): array
    {
        return [
            'id' => $rules->id,
            'high_season_months' => array_map('intval', $rules->high_season_months ?? []),
            'viability_min_default' => (float) $rules->viability_min_default,
            'viability_min_by_event_type' => (object) ($rules->viability_min_by_event_type ?? []),
            'max_discount_percent' => $rules->max_discount_percent,
            'proposal_validity_days' => $rules->proposal_validity_days,
            'pre_reserve_days' => $rules->pre_reserve_days,
            'max_events_per_day' => $rules->max_events_per_day,
            'max_guests_per_day' => $rules->max_guests_per_day,
            'max_events_per_week' => $rules->max_events_per_week,
            'complex_max_events_per_day' => $rules->complex_max_events_per_day,
            'complex_max_events_per_week' => $rules->complex_max_events_per_week,
            'complex_guests_threshold' => $rules->complex_guests_threshold,
            'complex_keywords' => array_values($rules->complex_keywords ?? []),
            'max_small_per_weekend' => $rules->max_small_per_weekend,
            'small_keywords' => array_values($rules->small_keywords ?? []),
            'max_pre_events_per_weekend' => $rules->max_pre_events_per_weekend,
            'pre_event_max_guests' => $rules->pre_event_max_guests,
            'pre_event_keywords' => array_values($rules->pre_event_keywords ?? []),
            'first_contact_hours' => $rules->first_contact_hours,
            'business_hours_start' => $rules->business_hours_start ?: '08:00',
            'business_hours_end' => $rules->business_hours_end ?: '18:00',
            'business_days' => $rules->businessDays(),
            'rotation_user_ids' => array_map('intval', $rules->rotation_user_ids ?? []),
            'rotation_event_type_ids' => array_map('intval', $rules->rotation_event_type_ids ?? []),
            'rotation_include_unknown' => (bool) $rules->rotation_include_unknown,
            'segments' => array_map(fn ($s) => ['key' => $s['key'], 'name' => $s['name'], 'event_type_ids' => array_map('intval', $s['event_type_ids'] ?? [])], $rules->segments ?? []),
            'max_distance_km' => $rules->max_distance_km,
            'rotation_last_user_name' => $rules->rotation_last_user_id ? User::find($rules->rotation_last_user_id)?->name : null,
            'no_answer_attempts' => $rules->no_answer_attempts,
            'no_answer_channels' => $rules->no_answer_channels,
            'no_answer_days' => $rules->no_answer_days,
            'stage_max_days' => $rules->stageMaxDays(),
            'stage_default_max_days' => Lead::STAGE_MAX_DAYS,
            'payment_terms' => $rules->payment_terms,
            'notes' => $rules->notes,
            'updated_at' => $rules->updated_at,
            'updated_by_name' => $rules->updatedBy?->name,
            'event_types' => EventType::orderBy('name')->get(['id', 'name']),
            'consultants' => User::where('is_employee', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    // ------------------------------------------------------------------ substituição nas férias

    public function covers()
    {
        $covers = LeadCover::with(['absentUser:id,name', 'substituteUser:id,name'])
            ->whereDate('ends_at', '>=', now()->subDays(60)->toDateString())
            ->orderByDesc('starts_at')
            ->get();

        return response()->json(['data' => $covers->map(fn (LeadCover $c) => $this->coverPayload($c))->values()], 200);
    }

    public function storeCover(Request $request)
    {
        $data = $request->validate([
            'absent_user_id' => 'required|integer|exists:users,id',
            'substitute_user_id' => 'required|integer|exists:users,id|different:absent_user_id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'notes' => 'nullable|string|max:255',
        ], [
            'absent_user_id.required' => 'Escolha o consultor que vai sair de férias.',
            'substitute_user_id.required' => 'Escolha quem vai substituir.',
            'substitute_user_id.different' => 'O substituto precisa ser outra pessoa.',
            'starts_at.required' => 'Informe o início das férias.',
            'ends_at.required' => 'Informe o fim das férias.',
            'ends_at.after_or_equal' => 'O fim precisa ser no mesmo dia ou depois do início.',
        ]);
        $data['created_by'] = $request->user()?->id;
        $cover = LeadCover::create($data);

        return response()->json($this->coverPayload($cover->load(['absentUser:id,name', 'substituteUser:id,name'])), 201);
    }

    public function destroyCover(LeadCover $cover)
    {
        $cover->delete();

        return response()->json([], 204);
    }

    private function coverPayload(LeadCover $c): array
    {
        $today = now()->toDateString();

        return [
            'id' => $c->id,
            'absent_user_id' => $c->absent_user_id,
            'absent_user_name' => $c->absentUser?->name,
            'substitute_user_id' => $c->substitute_user_id,
            'substitute_user_name' => $c->substituteUser?->name,
            'starts_at' => $c->starts_at?->toDateString(),
            'ends_at' => $c->ends_at?->toDateString(),
            'notes' => $c->notes,
            'status' => $c->ends_at?->toDateString() < $today ? 'encerrada' : ($c->starts_at?->toDateString() > $today ? 'agendada' : 'ativa'),
        ];
    }
}
