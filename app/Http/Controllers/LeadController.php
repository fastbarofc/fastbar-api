<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadRequest;
use App\Http\Resources\LeadActivityResource;
use App\Http\Resources\LeadCommentResource;
use App\Http\Resources\LeadResource;
use App\Models\Client;
use App\Models\CompetingCompany;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\Lead;
use App\Models\LeadCover;
use App\Models\LeadActivity;
use App\Models\LeadComment;
use App\Models\LostReason;
use App\Models\User;
use App\Services\LeadDashboardService;
use App\Services\LeadRulesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Comercial > Funil de vendas: quadro Kanban de leads.
 */
class LeadController extends Controller
{
    private const RELATIONS = ['user', 'eventType', 'lostReason', 'partner', 'client', 'event', 'competingCompany'];
    private const FULL_RELATIONS = ['user', 'eventType', 'lostReason', 'partner', 'client', 'event', 'competingCompany', 'comments.user', 'activities.user'];

    /** Permissão que deixa ver a carteira de todos os consultores (gerência). */
    private const SEE_ALL_PERMISSION = 'comercial-funil-ver-todos';

    public function __construct(protected LeadRulesService $rules)
    {
    }

    /**
     * Todos os leads do quadro (sem paginação), já ordenados por etapa e posição.
     * Filtros: search (nome, telefone, e-mail, instagram, cidade ou ID), user_id, source.
     * Quem não tem a permissão de ver todos só recebe a própria carteira.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $seeAll = $this->canSeeAll($user);

        $query = Lead::query()->with(array_merge(self::RELATIONS, ['activities']))->withCount(['comments', 'activities']);

        if (!$seeAll) {
            $query->whereIn('user_id', $this->portfolioUserIds($user));
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('instagram', 'like', "%$search%")
                    ->orWhere('city', 'like', "%$search%");
                $digits = preg_replace('/\D/', '', $search);
                if ($digits !== '' && strlen($digits) >= 4) {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') like ?", ["%$digits%"]);
                }
                if (ctype_digit($search)) {
                    $q->orWhere('leads.id', (int) $search);
                }
            });
        }

        if ($seeAll && $request->filled('user_id')) {
            $request->input('user_id') === 'none'
                ? $query->whereNull('user_id')
                : $query->where('user_id', $request->input('user_id'));
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        $leads = $query->orderBy('position')->orderByDesc('id')->get();

        return response()->json([
            'data' => LeadResource::collection($leads),
            'stages' => Lead::STAGES,
            'sources' => Lead::SOURCES,
            'can_see_all' => $seeAll,
            'covering' => $seeAll ? [] : LeadCover::activeOn()->where('substitute_user_id', $user->id)->with('absentUser:id,name')->get()->map(fn ($c) => ['user_id' => $c->absent_user_id, 'name' => $c->absentUser?->name, 'ends_at' => $c->ends_at?->toDateString()])->values(),
            'stage_requirements' => Lead::STAGE_REQUIREMENTS,
            'stage_max_days' => Lead::stageMaxDays(),
            'no_answer_rule' => Lead::noAnswerRule(),
            'can_approve' => $this->rules->userCan($user, LeadRulesService::APPROVE_PERMISSION),
            'commercial_rules' => [
                'viability_min_default' => (float) $this->rules->rules()->viability_min_default,
                'viability_min_by_event_type' => (object) ($this->rules->rules()->viability_min_by_event_type ?? []),
                'max_discount_percent' => $this->rules->rules()->max_discount_percent,
                'proposal_validity_days' => $this->rules->rules()->proposal_validity_days,
                'first_contact_hours' => $this->rules->rules()->first_contact_hours,
            ],
        ], 200);
    }

    public function store(LeadRequest $request)
    {
        $data = $request->validated();
        if (!$this->canSeeAll($request->user())) {
            $data['user_id'] = $request->user()->id;
        }
        // Distribuição automática: cidade atendida define o consultor quando a gerência não escolheu um.
        $data = $this->rules->distribute($data);
        $autoAssigned = !empty($data['auto_assigned']);
        unset($data['auto_assigned'], $data['assigned_by_rotation']);
        if (empty($data['user_id'])) {
            $data['user_id'] = $request->user()?->id;
        }
        $data = $this->cleanLost($data);
        $this->guardStageChange(null, 'novo', $data['stage'], $data);
        $data['stage_changed_at'] = now();
        // Lead novo entra no topo da coluna.
        Lead::where('stage', $data['stage'])->increment('position');
        $data['position'] = 0;

        $lead = Lead::create($data);
        $this->rules->syncApproval($lead, null, $request->user());
        if ($autoAssigned) {
            $this->rules->notifyAssigned($lead, $request->user());
        }
        $this->afterStageChange($lead, null);

        return response()->json(new LeadResource($lead->load(self::FULL_RELATIONS)), 201);
    }

    public function show(Request $request, Lead $lead)
    {
        $this->guardOwnership($request->user(), $lead);

        return response()->json(new LeadResource($lead->load(self::FULL_RELATIONS)), 200);
    }

    public function update(LeadRequest $request, Lead $lead)
    {
        $this->guardOwnership($request->user(), $lead);
        $data = $this->cleanLost($request->validated());
        if (!$this->canSeeAll($request->user())) {
            $data['user_id'] = $lead->user_id ?: $request->user()->id;
        }
        $before = $lead->getAttributes();
        // Nova data do evento: a pré-reserva vale de novo para a data nova.
        if (array_key_exists('event_date', $data) && (string) ($data['event_date'] ?? '') !== (string) ($lead->event_date?->toDateString() ?? '')) {
            $data['pre_reserve_released_at'] = null;
            $data['pre_reserve_released_by'] = null;
        }
        $data = $this->rules->distribute($data);
        $autoAssigned = !empty($data['auto_assigned']) && (int) ($data['user_id'] ?? 0) !== (int) $lead->user_id;
        unset($data['auto_assigned'], $data['assigned_by_rotation']);
        $fromStage = $lead->stage;
        if ($data['stage'] !== $fromStage) {
            $this->guardStageChange($lead, $fromStage, $data['stage'], array_merge($lead->getAttributes(), $data));
            $data['stage_changed_at'] = now();
            Lead::where('stage', $data['stage'])->increment('position');
            $data['position'] = 0;
        }

        $lead->update($data);
        $this->rules->syncApproval($lead, $before, $request->user());
        if ($autoAssigned) {
            $this->rules->notifyAssigned($lead->fresh('user', 'servedCity'), $request->user());
        }
        $this->afterStageChange($lead, $fromStage);

        return response()->json(new LeadResource($lead->load(self::FULL_RELATIONS)), 200);
    }

    public function destroy(Request $request, Lead $lead)
    {
        $this->guardOwnership($request->user(), $lead);
        $lead->delete();

        return response()->json([], 204);
    }

    /**
     * Arrastar o cartão: muda a etapa e grava a nova ordem da coluna de destino.
     * Body: stage, ids (ordem completa da coluna de destino), lost_reason_id, lost_note e competitor quando "perdido".
     */
    public function move(Request $request, Lead $lead)
    {
        $this->guardOwnership($request->user(), $lead);
        $data = $request->validate(
            [
                'stage'          => ['required', Rule::in(Lead::STAGES)],
                'ids'            => 'nullable|array',
                'ids.*'          => 'integer',
                'lost_reason_id' => ['nullable', 'required_if:stage,perdido', 'exists:lost_reasons,id'],
                'lost_note'      => 'nullable|string',
                'competitor'     => 'nullable|string|max:255',
                'competing_company_id' => 'nullable|exists:competing_companies,id',
            ],
            ['lost_reason_id.required_if' => 'Informe o motivo da perda.']
        );

        $fromStage = $lead->stage;
        if ($data['stage'] !== $fromStage) {
            $this->guardStageChange($lead, $fromStage, $data['stage'], array_merge($lead->getAttributes(), $data));
        }

        DB::transaction(function () use ($lead, $data, $fromStage) {
            $changes = ['stage' => $data['stage']];
            if ($data['stage'] !== $fromStage) {
                $changes['stage_changed_at'] = now();
            }
            if ($data['stage'] === 'perdido') {
                $changes['lost_reason_id'] = $data['lost_reason_id'] ?? null;
                $changes['lost_note'] = $data['lost_note'] ?? null;
                $changes['competing_company_id'] = $data['competing_company_id'] ?? null;
                $changes['competitor'] = $this->competitorName($data);
            } else {
                $changes['lost_reason_id'] = null;
                $changes['lost_note'] = null;
                $changes['competitor'] = null;
                $changes['competing_company_id'] = null;
            }
            $lead->update($changes);

            $ids = $data['ids'] ?? [$lead->id];
            if (!in_array($lead->id, $ids, true)) {
                array_unshift($ids, $lead->id);
            }
            foreach (array_values($ids) as $index => $id) {
                Lead::where('id', $id)->where('stage', $data['stage'])->update(['position' => $index]);
            }
        });

        $this->afterStageChange($lead, $fromStage);

        return response()->json(new LeadResource($lead->fresh(array_merge(self::RELATIONS, ['activities']))), 200);
    }

    /** Gerência aprova ou recusa desconto/viabilidade fora da regra. */
    public function approval(Request $request, Lead $lead)
    {
        $data = $request->validate(
            [
                'decision' => ['required', Rule::in(['aprovado', 'recusado'])],
                'note'     => 'nullable|string',
            ],
            ['decision.required' => 'Informe se aprova ou recusa.', 'decision.in' => 'Decisão inválida.']
        );
        if ($lead->approval_status !== 'pendente' && $data['decision'] === $lead->approval_status) {
            return response()->json(new LeadResource($lead->load(self::FULL_RELATIONS)), 200);
        }

        $this->rules->decide($lead, $data['decision'], $data['note'] ?? null, $request->user());

        return response()->json(new LeadResource($lead->fresh(self::FULL_RELATIONS)), 200);
    }

    /**
     * Pré-reserva: "Liberar data" (released = true) ou reservar de novo (released = false).
     * A reserva não cai sozinha quando a proposta vence; só sai por aqui ou com Ganho/Perdido.
     */
    public function preReserve(Request $request, Lead $lead)
    {
        $this->guardOwnership($request->user(), $lead);
        $data = $request->validate(['released' => 'required|boolean']);
        $lead->forceFill($data['released']
            ? ['pre_reserve_released_at' => now(), 'pre_reserve_released_by' => $request->user()?->id]
            : ['pre_reserve_released_at' => null, 'pre_reserve_released_by' => null])->save();

        return response()->json(new LeadResource($lead->fresh(self::FULL_RELATIONS)), 200);
    }

    /** Gráficos do Comercial: um gráfico por chamada, com período e consultor. */
    public function dashboard(Request $request, LeadDashboardService $dashboard)
    {
        $data = $request->validate([
            'chart' => ['required', Rule::in(LeadDashboardService::CHARTS)],
            'period' => ['nullable', Rule::in(array_keys(LeadDashboardService::PERIODS))],
            'user_id' => 'nullable',
            'with_deals' => 'nullable|boolean',
        ]);
        $scope = $this->scopeQuery($request->user());
        if (!empty($data['user_id'])) {
            $data['user_id'] === 'none' ? $scope->whereNull('user_id') : $scope->where('user_id', (int) $data['user_id']);
        }

        return response()->json($dashboard->chart($data['chart'], $data['period'] ?? 'este_ano', $scope, (bool) ($data['with_deals'] ?? false)) + [
            'periods' => LeadDashboardService::PERIODS,
            'can_see_all' => $this->canSeeAll($request->user()),
        ], 200);
    }

    /** Fila de aprovações pendentes (gerência vê todas; consultor vê as suas). */
    public function approvals(Request $request)
    {
        $query = $this->scopeQuery($request->user())->where('approval_status', 'pendente')->whereNotIn('stage', ['ganho', 'perdido']);
        $leads = $query->with(self::RELATIONS)->orderBy('updated_at')->get();

        return response()->json(['data' => LeadResource::collection($leads)], 200);
    }

    /** Alertas dos leads do escopo do usuário. */
    public function alerts(Request $request)
    {
        return response()->json(['data' => $this->rules->alerts($this->scopeQuery($request->user()), $request->user())], 200);
    }

    /** Comercial > Meu dia. */
    public function myDay(Request $request)
    {
        return response()->json($this->rules->myDay($this->scopeQuery($request->user()), $request->user()), 200);
    }

    /** Comercial > Agenda de datas: eventos, pré-reservas e bloqueios do mês (YYYY-MM). */
    public function calendar(Request $request)
    {
        $month = $request->input('month') ?: now()->format('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw ValidationException::withMessages(['month' => 'Mês inválido (use AAAA-MM).']);
        }
        // Na agenda todo mundo vê as datas tomadas (sem dados do lead dos outros além do nome do consultor).
        return response()->json($this->rules->calendar($month, Lead::query()), 200);
    }

    /** Confere uma data: livre, com eventos, pré-reservada ou bloqueada. */
    public function dateCheck(Request $request)
    {
        $data = $request->validate(['date' => 'required|date', 'lead_id' => 'nullable|integer', 'name' => 'nullable|string', 'guests' => 'nullable|integer']);
        $candidate = $request->hasAny(['name', 'guests']) ? ['name' => $data['name'] ?? null, 'guests' => isset($data['guests']) ? (int) $data['guests'] : null] : null;

        return response()->json($this->rules->dateStatus($data['date'], isset($data['lead_id']) ? (int) $data['lead_id'] : null, null, $candidate), 200);
    }

    /** Leads que o usuário pode ver: todos (gerência) ou só a própria carteira. */
    private function scopeQuery($user)
    {
        $query = Lead::query();
        if (!$this->canSeeAll($user)) {
            $query->whereIn('user_id', $this->portfolioUserIds($user));
        }

        return $query;
    }

    /** Carteira do consultor: a própria e, durante uma substituição (férias), a do colega ausente. */
    private function portfolioUserIds($user): array
    {
        return array_values(array_unique(array_merge([(int) $user->id], LeadCover::coveredUserIds((int) $user->id))));
    }

    /** Usuários da equipe que podem ser consultores do lead. */
    public function consultants()
    {
        $users = User::query()
            ->where('is_employee', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $users], 200);
    }

    public function storeComment(Request $request, Lead $lead)
    {
        $this->guardOwnership($request->user(), $lead);
        $request->validate(
            ['comment' => 'required|string'],
            ['comment.required' => 'Escreva o comentário.']
        );

        $comment = $lead->comments()->create([
            'user_id' => $request->user()?->id,
            'comment' => $request->input('comment'),
        ]);

        return response()->json(new LeadCommentResource($comment->load('user')), 201);
    }

    public function destroyComment(Request $request, Lead $lead, LeadComment $comment)
    {
        $this->guardOwnership($request->user(), $lead);
        if ($comment->lead_id !== $lead->id) {
            abort(404);
        }

        $comment->delete();

        return response()->json([], 204);
    }

    /** Registra uma atividade (tentativa de contato, reunião, visita, degustação). */
    public function storeActivity(Request $request, Lead $lead)
    {
        $this->guardOwnership($request->user(), $lead);
        $data = $request->validate(
            [
                'type'        => ['required', Rule::in(Lead::ACTIVITY_TYPES)],
                'result'      => ['nullable', Rule::in(Lead::ACTIVITY_RESULTS)],
                'happened_at' => 'nullable|date',
                'notes'       => 'nullable|string',
            ],
            [
                'type.required' => 'Informe o tipo da atividade.',
                'type.in'       => 'Tipo de atividade inválido.',
                'result.in'     => 'Resultado inválido.',
            ]
        );

        $activity = $lead->activities()->create([
            'user_id'     => $request->user()?->id,
            'type'        => $data['type'],
            'result'      => $data['result'] ?? 'realizado',
            'happened_at' => $data['happened_at'] ?? now(),
            'notes'       => $data['notes'] ?? null,
        ]);

        if (!$lead->first_contact_at && in_array($data['type'], Lead::CONTACT_TYPES, true)) {
            $lead->forceFill(['first_contact_at' => $activity->happened_at])->save();
        }

        // Degustação registrada como atividade marca o lead.
        if ($data['type'] === 'degustacao') {
            $lead->update([
                'tasting' => true,
                'tasting_date' => $lead->tasting_date ?: $activity->happened_at->toDateString(),
            ]);
        }

        return response()->json([
            'activity' => new LeadActivityResource($activity->load('user')),
            'no_answer_attempts' => $lead->noAnswerAttempts(),
        ], 201);
    }

    public function destroyActivity(Request $request, Lead $lead, LeadActivity $activity)
    {
        $this->guardOwnership($request->user(), $lead);
        if ($activity->lead_id !== $lead->id) {
            abort(404);
        }

        $activity->delete();

        return response()->json(['no_answer_attempts' => $lead->noAnswerAttempts()], 200);
    }

    // ----------------------------------------------------------------------------------------

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

    /** Consultor sem a permissão de ver todos só mexe nos próprios leads. */
    private function guardOwnership($user, Lead $lead): void
    {
        if (!$this->canSeeAll($user) && !in_array((int) $lead->user_id, $this->portfolioUserIds($user), true)) {
            abort(response()->json(['message' => 'Este lead é da carteira de outro consultor.'], 403));
        }
    }

    /**
     * Regras para mudar de etapa: motivo da perda com suas exigências e aprovação da gerência.
     * Responde 422 com a lista do que falta.
     */
    private function guardStageChange(?Lead $lead, string $fromStage, string $toStage, array $values): void
    {
        if ($toStage === 'perdido') {
            $reason = LostReason::find($values['lost_reason_id'] ?? null);
            if ($reason?->code === 'concorrente' && empty($values['competing_company_id']) && empty($values['competitor'])) {
                throw ValidationException::withMessages(['competing_company_id' => 'Escolha qual concorrente fechou o evento (cadastro em Configurações > Concorrentes).']);
            }
            if ($reason?->code === 'sem_resposta' && $lead) {
                $attempts = $lead->noAnswerAttempts();
                $rule = Lead::noAnswerRule();
                if ($attempts['count'] < $rule['attempts'] || $attempts['channels'] < $rule['channels']) {
                    throw ValidationException::withMessages([
                        'lost_reason_id' => sprintf(
                            '"Sem resposta" só depois de %d tentativas em %d canais nos últimos %d dias. Registradas: %d tentativa(s) em %d canal(is). Registre as tentativas no cartão (WhatsApp, ligação, e-mail) ou escolha outro motivo.',
                            $rule['attempts'],
                            $rule['channels'],
                            $rule['days'],
                            $attempts['count'],
                            $attempts['channels']
                        ),
                    ]);
                }
            }

            return;
        }

        if ($lead && ($block = $this->rules->approvalBlockMessage($lead, $toStage))) {
            throw ValidationException::withMessages(['approval_status' => $block]);
        }

        // Patrícia (06/11/2026): "no funil nada é obrigatório, trabalhamos com orientações".
        // Os dados de cada etapa (Lead::STAGE_REQUIREMENTS) ficam só como aviso no cartão (LeadResource.missing_fields).
    }

    /**
     * Depois da mudança de etapa: Ganho cria o cliente e o evento com os dados do lead.
     */
    private function afterStageChange(Lead $lead, ?string $fromStage): void
    {
        if ($lead->stage !== 'ganho' || $fromStage === 'ganho' || $lead->event_id) {
            return;
        }

        DB::transaction(function () use ($lead) {
            $client = $lead->client_id ? Client::find($lead->client_id) : null;
            if (!$client) {
                $client = $this->findClientByPhone($lead->phone);
            }
            if (!$client) {
                $client = Client::create([
                    'type'    => 'pf',
                    'name'    => $lead->name,
                    'phone_1' => $lead->phone ?? '',
                    'email'   => $lead->email,
                    'user_id' => $lead->user_id,
                ]);
            }

            $status = EventStatus::where('name', 'Aberto')->first();
            $event = Event::create([
                'name'            => trim(($lead->eventType?->name ? $lead->eventType->name . ' ' : '') . $lead->name),
                'client_id'       => $client->id,
                'event_type_id'   => $lead->event_type_id,
                'event_status_id' => $status?->id,
                'partner_id'      => $lead->partner_id,
                'date'            => $lead->event_date?->format('Y-m-d'),
                'event_guests'    => $lead->guests,
                'total_value'     => $lead->final_value ?? $lead->estimated_value,
                'is_draft'        => true,
            ]);

            $lead->update(['client_id' => $client->id, 'event_id' => $event->id]);
        });
    }

    private function findClientByPhone(?string $phone): ?Client
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) < 8) {
            return null;
        }
        $clean = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '.', '')";

        return Client::whereRaw(sprintf($clean, 'phone_1') . ' like ?', ["%$digits"])
            ->orWhereRaw(sprintf($clean, 'phone_2') . ' like ?', ["%$digits"])
            ->first();
    }

    private function cleanLost(array $data): array
    {
        if (($data['stage'] ?? null) !== 'perdido') {
            $data['lost_reason_id'] = null;
            $data['lost_note'] = null;
            $data['competitor'] = null;
            $data['competing_company_id'] = null;
        } elseif (!empty($data['competing_company_id'])) {
            $data['competitor'] = $this->competitorName($data);
        }

        return $data;
    }

    /** Nome do concorrente escolhido no cadastro (guardado também em texto para relatórios). */
    private function competitorName(array $data): ?string
    {
        if (!empty($data['competing_company_id'])) {
            return CompetingCompany::find($data['competing_company_id'])?->name ?? ($data['competitor'] ?? null);
        }

        return $data['competitor'] ?? null;
    }
}
