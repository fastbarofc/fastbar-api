<?php

namespace App\Services;

use App\Models\BlockedDate;
use App\Models\CommercialRule;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\Lead;
use App\Models\LeadCover;
use App\Models\MarketRule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Regras comerciais da Fase 2 do funil: distribuição automática, aprovação de desconto/viabilidade,
 * alertas, "Meu dia" e agenda de datas com pré-reserva.
 */
class LeadRulesService
{
    public const APPROVE_PERMISSION = 'comercial-funil-aprovar';

    public function __construct(protected NotificationService $notifications)
    {
    }

    public function rules(): CommercialRule
    {
        return CommercialRule::current();
    }

    // ------------------------------------------------------------------ distribuição

    /**
     * Escolhe o consultor do lead que chega sem consultor:
     * 1) Mercado por consultor: consultor da cidade e do segmento (mais de um = rodízio entre eles; "não atende" = ninguém);
     * 2) rodízio geral de Regras comerciais, um lead para cada, pulando quem está de férias.
     * Devolve os dados ajustados (user_id, auto_assigned, assigned_by_rotation).
     */
    public function distribute(array $data): array
    {
        $eventTypeId = !empty($data['event_type_id']) ? (int) $data['event_type_id'] : null;

        // 1) Mercado por consultor (cidade + segmento), a única lista de cidades. Mais de um consultor = rodízio entre eles.
        if (empty($data['user_id']) && !empty($data['city'])) {
            $market = MarketRule::findFor($data['city'], $this->rules()->segmentFor($eventTypeId));
            if ($market && $market->served) {
                $ids = $market->consultantIds();
                $next = count($ids) > 1 ? $this->nextFrom($ids, 'market:' . $market->id) : ($ids[0] ?? null);
                if ($next) {
                    $data['user_id'] = $next;
                    $data['auto_assigned'] = true;
                    $data['assigned_by_rotation'] = count($ids) > 1;
                }
            } elseif ($market && !$market->served) {
                // Cidade não atendida neste segmento: fica com a gerência decidir (aviso no cartão).
                return $data;
            }
        }

        // 2) Sem regra para a cidade (ou sem cidade): rodízio geral de Regras comerciais.
        if (empty($data['user_id']) && $this->rotationApplies(!empty($data['city']), $eventTypeId)) {
            $next = $this->nextInRotation();
            if ($next) {
                $data['user_id'] = $next;
                $data['auto_assigned'] = true;
                $data['assigned_by_rotation'] = true;
            }
        }

        return $data;
    }

    /** O lead entra no rodízio? Tipo de evento dentro da lista (vazia = todos); sem cidade/tipo só se a regra deixar. */
    public function rotationApplies(bool $hasCity, ?int $eventTypeId): bool
    {
        $rules = $this->rules();
        if (empty($rules->rotation_user_ids)) {
            return false;
        }
        $unknownOk = (bool) $rules->rotation_include_unknown;
        if (!$hasCity && !$unknownOk) {
            return false;
        }
        $types = array_map('intval', $rules->rotation_event_type_ids ?? []);
        if (!$eventTypeId) {
            return $unknownOk || !$types;
        }

        return !$types || in_array($eventTypeId, $types, true);
    }

    /** Próximo da fila de uma regra do Mercado por consultor (um lead para cada), pulando quem está de férias. */
    public function nextFrom(array $ids, string $key): ?int
    {
        return DB::transaction(function () use ($ids, $key) {
            $rules = CommercialRule::query()->orderBy('id')->lockForUpdate()->first() ?? $this->rules();
            $absent = LeadCover::absentUserIds();
            $ids = array_values(array_filter(array_unique(array_map('intval', $ids)), fn ($id) => !in_array($id, $absent, true)));
            if (!$ids) {
                return null;
            }
            $pointers = $rules->rotation_pointers ?? [];
            $lastIndex = array_search((int) ($pointers[$key] ?? 0), $ids, true);
            $next = $ids[$lastIndex === false ? 0 : ($lastIndex + 1) % count($ids)];
            $pointers[$key] = $next;
            $rules->forceFill(['rotation_pointers' => $pointers])->saveQuietly();

            return $next;
        });
    }

    /** Próximo consultor do rodízio (depois do último que recebeu), pulando quem está de férias hoje. */
    public function nextInRotation(): ?int
    {
        return DB::transaction(function () {
            $rules = CommercialRule::query()->orderBy('id')->lockForUpdate()->first() ?? $this->rules();
            $ids = array_values(array_unique(array_map('intval', $rules->rotation_user_ids ?? [])));
            $active = User::whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $absent = LeadCover::absentUserIds();
            $ids = array_values(array_filter($ids, fn ($id) => in_array($id, $active, true) && !in_array($id, $absent, true)));
            if (!$ids) {
                return null;
            }
            $lastIndex = array_search((int) $rules->rotation_last_user_id, $ids, true);
            $next = $ids[$lastIndex === false ? 0 : ($lastIndex + 1) % count($ids)];
            $rules->forceFill(['rotation_last_user_id' => $next])->saveQuietly();

            return $next;
        });
    }

    public function notifyAssigned(Lead $lead, ?User $actor): void
    {
        $consultant = $lead->user;
        if (!$consultant || ($actor && (int) $actor->id === (int) $consultant->id)) {
            return;
        }
        $where = $lead->servedCity ? " (cidade: {$lead->servedCity->name})" : '';
        $this->notify('lead_assigned', $consultant, 'Novo lead na sua carteira', "O lead \"{$lead->name}\" foi distribuído para você{$where}.", $lead);
        foreach (LeadCover::substitutesOf((int) $consultant->id) as $substituteId) {
            $substitute = User::find($substituteId);
            if ($substitute && (!$actor || (int) $actor->id !== $substituteId)) {
                $this->notify('lead_assigned', $substitute, 'Novo lead para você atender (substituição)', "O lead \"{$lead->name}\" entrou na carteira de {$consultant->name}, que você está substituindo{$where}.", $lead);
            }
        }
    }

    // ------------------------------------------------------------------ aprovação

    /** Motivos pelos quais o lead precisa de aprovação da gerência (vazio = não precisa). */
    public function approvalReasons(Lead|array $values): array
    {
        $values = $values instanceof Lead ? $values->getAttributes() : $values;
        $rules = $this->rules();
        $reasons = [];

        $eventTypeId = isset($values['event_type_id']) ? (int) $values['event_type_id'] : null;
        $min = $rules->viabilityMinFor($eventTypeId);
        $viability = $values['expected_viability'] ?? null;
        if ($viability !== null && $viability !== '' && (float) $viability < $min) {
            $reasons[] = sprintf('viabilidade prevista %s%% abaixo do mínimo de %s%%', $this->pct($viability), $this->pct($min));
        }

        $maxDiscount = $rules->max_discount_percent;
        $discount = $values['discount_percent'] ?? null;
        if ($maxDiscount !== null && $discount !== null && $discount !== '' && (float) $discount > (float) $maxDiscount) {
            $reasons[] = sprintf('desconto de %s%% acima do permitido de %s%%', $this->pct($discount), $this->pct($maxDiscount));
        }

        return $reasons;
    }

    /**
     * Depois de gravar o lead: abre, mantém ou limpa o pedido de aprovação conforme os valores.
     * $before = valores anteriores (null em lead novo).
     */
    public function syncApproval(Lead $lead, ?array $before, ?User $actor): void
    {
        $reasons = $this->approvalReasons($lead);
        $status = $lead->approval_status;

        if (!$reasons) {
            if ($status === 'pendente') {
                $lead->forceFill(['approval_status' => null, 'approval_request_note' => null])->save();
            }

            return;
        }

        $valuesChanged = $before !== null && (
            $this->num($before['expected_viability'] ?? null) !== $this->num($lead->expected_viability)
            || $this->num($before['discount_percent'] ?? null) !== $this->num($lead->discount_percent)
        );

        if ($status === null || ($status !== 'pendente' && $valuesChanged)) {
            $lead->forceFill([
                'approval_status' => 'pendente',
                'approval_request_note' => implode('; ', $reasons),
                'approval_note' => null,
                'approved_by' => null,
                'approved_at' => null,
            ])->save();
            $this->notifyApprovers($lead, $actor);
        }
    }

    /** Barra avançar para assinatura/ganho com aprovação pendente ou recusada. */
    public function approvalBlockMessage(Lead $lead, string $toStage): ?string
    {
        if (!in_array($toStage, ['aguardando_assinatura', 'ganho'], true)) {
            return null;
        }
        if ($lead->approval_status === 'pendente') {
            return 'Este lead aguarda aprovação da gerência (' . $lead->approval_request_note . '). Só avança para assinatura depois de aprovado.';
        }
        if ($lead->approval_status === 'recusado') {
            return 'A gerência recusou o desconto/viabilidade deste lead' . ($lead->approval_note ? " ({$lead->approval_note})" : '') . '. Ajuste os valores para pedir de novo.';
        }

        return null;
    }

    public function decide(Lead $lead, string $decision, ?string $note, User $approver): Lead
    {
        $lead->forceFill([
            'approval_status' => $decision,
            'approval_note' => $note,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ])->save();

        if ($lead->user && (int) $lead->user_id !== (int) $approver->id) {
            $title = $decision === 'aprovado' ? 'Desconto/viabilidade aprovado' : 'Desconto/viabilidade recusado';
            $message = "Lead \"{$lead->name}\": {$approver->name} " . ($decision === 'aprovado' ? 'aprovou' : 'recusou') . ' o pedido' . ($note ? " — {$note}" : '.');
            $this->notify('lead_approval_decided', $lead->user, $title, $message, $lead);
        }

        return $lead;
    }

    public function approvers(): Collection
    {
        return User::query()->where('is_employee', true)->get()->filter(fn (User $u) => $this->userCan($u, self::APPROVE_PERMISSION))->values();
    }

    public function userCan(User $user, string $slug): bool
    {
        $slugs = method_exists($user, 'effectivePermissions')
            ? $user->effectivePermissions()->pluck('slug')->all()
            : $user->getAllPermissions()->pluck('slug')->all();

        return in_array($slug, $slugs, true);
    }

    private function notifyApprovers(Lead $lead, ?User $actor): void
    {
        foreach ($this->approvers() as $approver) {
            if ($actor && (int) $approver->id === (int) $actor->id) {
                continue;
            }
            $this->notify(
                'lead_approval_requested',
                $approver,
                'Aprovação pendente no funil',
                "Lead \"{$lead->name}\"" . ($lead->user ? " ({$lead->user->name})" : '') . ': ' . $lead->approval_request_note . '.',
                $lead
            );
        }
    }

    // ------------------------------------------------------------------ alertas

    /** Alertas dos leads do escopo (carteira ou todos). Cada item: lead resumido + tipo + texto. */
    public function alerts(Builder $query, User $user): array
    {
        $rules = $this->rules();
        $leads = $query->whereNotIn('stage', ['ganho', 'perdido'])->with(['user', 'eventType', 'activities'])->get();
        $alerts = [];

        foreach ($leads as $lead) {
            foreach ($this->alertsFor($lead, $rules) as $alert) {
                $alerts[] = $alert;
            }
        }

        if ($this->userCan($user, self::APPROVE_PERMISSION)) {
            $pending = Lead::where('approval_status', 'pendente')->whereNotIn('stage', ['ganho', 'perdido'])->with('user')->get();
            foreach ($pending as $lead) {
                $alerts[] = $this->alert($lead, 'aprovacao_pendente', 'Aguardando sua aprovação: ' . $lead->approval_request_note . '.', 'alta');
            }
        }

        usort($alerts, fn ($a, $b) => [$this->severityRank($a['severity']), $a['lead']['name']] <=> [$this->severityRank($b['severity']), $b['lead']['name']]);

        return $alerts;
    }

    public function alertsFor(Lead $lead, ?CommercialRule $rules = null): array
    {
        $rules ??= $this->rules();
        $out = [];
        $today = Carbon::today();

        if ($lead->stage === 'novo' && !$lead->first_contact_at && $lead->activities->isEmpty()) {
            $hours = $this->businessHoursSince($lead->created_at, $rules);
            if ($hours >= $rules->first_contact_hours) {
                $out[] = $this->alert($lead, 'sem_primeiro_contato', sprintf('Sem primeiro contato há %d horas em horário comercial (limite: %d).', $hours, $rules->first_contact_hours), 'alta');
            }
        }

        if ($lead->isStalled()) {
            $out[] = $this->alert($lead, 'parado', sprintf('Parado há %d dias na etapa (prazo: %d).', $lead->daysInStage(), Lead::stageMaxDays()[$lead->stage] ?? 0), 'media');
        }

        $validUntil = $lead->proposalValidUntil();
        if ($validUntil && in_array($lead->stage, ['proposta_enviada', 'follow_up', 'negociacao'], true)) {
            if ($validUntil->isPast()) {
                $out[] = $this->alert($lead, 'proposta_vencida', 'Proposta vencida em ' . $validUntil->format('d/m/Y') . ($lead->holdsPreReserve() ? '. A data continua pré-reservada: renove a proposta ou clique em "Liberar data" no cartão.' : '.'), 'alta');
            } elseif ($validUntil->lte($today->copy()->addDays(2)->endOfDay())) {
                $out[] = $this->alert($lead, 'proposta_vencendo', 'Proposta vence em ' . $validUntil->format('d/m/Y') . '.', 'media');
            }
        }

        if (!in_array($lead->stage, ['novo', 'nutricao', 'licitacao'], true) && !$lead->next_contact_date && !($lead->meeting_date && Carbon::parse($lead->meeting_date)->gte($today))) {
            $out[] = $this->alert($lead, 'sem_proximo_passo', 'Sem próximo contato marcado.', 'baixa');
        } elseif ($lead->next_contact_date && Carbon::parse($lead->next_contact_date)->lt($today)) {
            $out[] = $this->alert($lead, 'contato_atrasado', 'Próximo contato era em ' . Carbon::parse($lead->next_contact_date)->format('d/m/Y') . '.', 'media');
        }

        foreach ($this->marketWarnings($lead, $rules) as $warning) {
            $out[] = $this->alert($lead, 'fora_do_perfil', $warning, 'media');
        }

        if ($lead->event_date) {
            $status = $this->dateStatus($lead->event_date->format('Y-m-d'), $lead->id, $rules, ['name' => $lead->name, 'guests' => $lead->guests]);
            if (!$status['available']) {
                $out[] = $this->alert($lead, 'conflito_data', 'Data do evento ' . $lead->event_date->format('d/m/Y') . ': ' . $status['message'], 'alta');
            }
        }

        return $out;
    }

    /** Avisos do Mercado por consultor: cidade não atendida no segmento ou abaixo do mínimo de convidados. */
    public function marketWarnings(Lead $lead, ?CommercialRule $rules = null): array
    {
        $rules ??= $this->rules();
        $segment = $rules->segmentFor($lead->event_type_id ? (int) $lead->event_type_id : null);
        $market = MarketRule::findFor($lead->city, $segment);
        if (!$market) {
            return [];
        }
        $name = $rules->segmentName($segment);
        if (!$market->served) {
            return ["{$market->city} não é atendida em {$name}" . ($market->notes ? " ({$market->notes})" : '') . '.'];
        }
        $min = $market->minGuestsFor($lead->event_date?->toDateString(), $rules);
        if ($min && $lead->guests && (int) $lead->guests < $min) {
            $high = $lead->event_date && $rules->isHighSeason($lead->event_date->toDateString());

            return [sprintf('%d convidados está abaixo do mínimo de %d para %s em %s%s.', $lead->guests, $min, $name, $market->city, $high ? ' na alta temporada' : '')];
        }

        return [];
    }

    /** Horas em horário comercial (dias e horário de Regras comerciais) desde a data. */
    public function businessHoursSince(?Carbon $since, ?CommercialRule $rules = null): int
    {
        if (!$since) {
            return 0;
        }
        $rules ??= $this->rules();
        [$open, $close] = $rules->businessWindow();
        $days = $rules->businessDays();
        $now = now();
        $minutes = 0;
        $day = $since->copy()->startOfDay();
        for ($i = 0; $day->lte($now) && $i < 400; $i++, $day->addDay()) {
            if (!in_array($day->dayOfWeekIso, $days, true)) {
                continue;
            }
            $from = max($day->copy()->addMinutes($open)->getTimestamp(), $since->getTimestamp());
            $to = min($day->copy()->addMinutes($close)->getTimestamp(), $now->getTimestamp());
            if ($to > $from) {
                $minutes += intdiv($to - $from, 60);
            }
        }

        return intdiv($minutes, 60);
    }

    // ------------------------------------------------------------------ Meu dia

    public function myDay(Builder $query, User $user): array
    {
        $today = Carbon::today()->toDateString();
        $rules = $this->rules();
        $open = (clone $query)->whereNotIn('stage', ['ganho', 'perdido'])->with(['user', 'eventType', 'activities'])->get();

        $contactsToday = $open->filter(fn ($l) => $l->next_contact_date && $l->next_contact_date->toDateString() === $today);
        $contactsLate = $open->filter(fn ($l) => $l->next_contact_date && $l->next_contact_date->toDateString() < $today);
        $meetingsToday = $open->filter(fn ($l) => $l->meeting_date && $l->meeting_date->toDateString() === $today);
        $tastingsToday = $open->filter(fn ($l) => $l->tasting_date && $l->tasting_date->toDateString() === $today);
        $newNoContact = $open->filter(fn ($l) => $l->stage === 'novo' && !$l->first_contact_at && $l->activities->isEmpty());
        $proposalsExpiring = $open->filter(function ($l) {
            $until = $l->proposalValidUntil();

            return $until && in_array($l->stage, ['proposta_enviada', 'follow_up', 'negociacao'], true) && $until->lte(Carbon::today()->addDays(2)->endOfDay());
        });
        $stalled = $open->filter(fn ($l) => $l->isStalled());
        $pendingApprovals = $this->userCan($user, self::APPROVE_PERMISSION)
            ? Lead::where('approval_status', 'pendente')->whereNotIn('stage', ['ganho', 'perdido'])->with(['user', 'eventType'])->get()
            : collect();

        $summary = [
            'abertos' => $open->count(),
            'quentes' => $open->where('temperature', 'quente')->count(),
            'parados' => $stalled->count(),
            'novos_sem_contato' => $newNoContact->count(),
            'contatos_hoje' => $contactsToday->count(),
            'contatos_atrasados' => $contactsLate->count(),
            'reunioes_hoje' => $meetingsToday->count(),
            'propostas_vencendo' => $proposalsExpiring->count(),
            'aprovacoes_pendentes' => $pendingApprovals->count(),
            'ganhos_mes' => (clone $query)->where('stage', 'ganho')->whereBetween('stage_changed_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count(),
        ];

        $brief = fn (Collection $c) => $c->sortBy('name')->values()->map(fn (Lead $l) => $this->brief($l))->all();

        return [
            'date' => $today,
            'summary' => $summary,
            'groups' => [
                ['key' => 'novos_sem_contato', 'label' => 'Leads novos sem primeiro contato', 'leads' => $brief($newNoContact)],
                ['key' => 'contatos_atrasados', 'label' => 'Contatos atrasados', 'leads' => $brief($contactsLate)],
                ['key' => 'contatos_hoje', 'label' => 'Contatos de hoje', 'leads' => $brief($contactsToday)],
                ['key' => 'reunioes_hoje', 'label' => 'Reuniões de hoje', 'leads' => $brief($meetingsToday)],
                ['key' => 'degustacoes_hoje', 'label' => 'Degustações de hoje', 'leads' => $brief($tastingsToday)],
                ['key' => 'propostas_vencendo', 'label' => 'Propostas vencendo (até 2 dias)', 'leads' => $brief($proposalsExpiring)],
                ['key' => 'parados', 'label' => 'Leads parados além do prazo', 'leads' => $brief($stalled)],
                ['key' => 'aprovacoes_pendentes', 'label' => 'Aprovações pendentes (gerência)', 'leads' => $brief($pendingApprovals)],
            ],
            'high_season' => $rules->isHighSeason($today),
        ];
    }

    // ------------------------------------------------------------------ agenda de datas e capacidade

    /**
     * Itens que ocupam a agenda no período: eventos marcados (não cancelados) e leads com pré-reserva.
     * Cada item: kind, id, date, name, guests, status, user_name + classificação (complexo, pequeno, pré-evento).
     */
    public function agendaItems(string $from, string $to, ?int $excludeLeadId = null, ?CommercialRule $rules = null): Collection
    {
        $rules ??= $this->rules();
        $cancelled = EventStatus::whereIn('slug', ['cancelado', 'descartado'])->pluck('id');
        $events = Event::query()
            ->with('eventStatus')
            ->whereNotIn('event_status_id', $cancelled)
            ->whereBetween('date', [$from, $to])
            ->get()
            ->map(fn (Event $e) => [
                'kind' => 'event',
                'id' => $e->id,
                'date' => Carbon::parse($e->date)->toDateString(),
                'name' => $e->name,
                'guests' => $e->event_guests !== null ? (int) $e->event_guests : null,
                'status' => $e->eventStatus?->name,
            ]);
        $leads = Lead::query()
            ->whereNotIn('stage', ['ganho', 'perdido'])
            ->whereBetween('event_date', [$from, $to])
            ->when($excludeLeadId, fn ($q) => $q->where('id', '!=', $excludeLeadId))
            ->with('user')
            ->get()
            ->filter(fn (Lead $l) => $l->holdsPreReserve())
            ->map(fn (Lead $l) => [
                'kind' => 'pre_reserve',
                'id' => $l->id,
                'date' => $l->event_date->toDateString(),
                'name' => $l->name,
                'guests' => $l->guests !== null ? (int) $l->guests : null,
                'status' => 'Pré-reserva',
                'user_name' => $l->user?->name,
                'proposal_valid_until' => $l->proposalValidUntil()?->toDateString(),
            ]);

        return $events->concat($leads)->map(fn ($i) => $i + $rules->classify($i['name'], $i['guests']))->values();
    }

    /**
     * Capacidade de uma data. $candidate = o lead que quer a data (nome e convidados), que conta junto.
     * Semana = segunda a domingo; fim de semana = sexta a domingo. Semana com evento complexo usa os limites menores.
     */
    public function capacity(string $date, Collection $items, ?array $candidate = null, ?CommercialRule $rules = null): array
    {
        $rules ??= $this->rules();
        $day = Carbon::parse($date);
        $weekStart = $day->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $day->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $weekendStart = $day->copy()->startOfWeek(Carbon::MONDAY)->addDays(4)->toDateString();
        $key = $day->toDateString();

        $dayItems = $items->where('date', $key);
        $weekItems = $items->filter(fn ($i) => $i['date'] >= $weekStart && $i['date'] <= $weekEnd);
        $weekendItems = $weekItems->filter(fn ($i) => $i['date'] >= $weekendStart);
        $cand = $candidate ? $rules->classify($candidate['name'] ?? null, $candidate['guests'] ?? null) + ['guests' => $candidate['guests'] ?? null] : null;
        $add = $cand ? 1 : 0;

        $complexWeek = $weekItems->contains(fn ($i) => $i['complex']) || ($cand['complex'] ?? false);
        $dayLimit = $complexWeek ? ($rules->complex_max_events_per_day ?: $rules->max_events_per_day) : $rules->max_events_per_day;
        $weekLimit = $complexWeek ? ($rules->complex_max_events_per_week ?: $rules->max_events_per_week) : $rules->max_events_per_week;
        $dayCount = $dayItems->count() + $add;
        $weekCount = $weekItems->count() + $add;
        $guests = (int) $dayItems->sum(fn ($i) => $i['guests'] ?? 0) + (int) ($cand['guests'] ?? 0);
        $small = $weekendItems->where('small', true)->count() + (($cand['small'] ?? false) ? 1 : 0);
        $pre = $weekendItems->where('pre_event', true)->count() + (($cand['pre_event'] ?? false) ? 1 : 0);

        // Sem candidato (agenda): avisa quando o limite já foi atingido. Com candidato: quando ele passaria do limite.
        $over = fn (?int $limit, int $count) => $limit && ($cand ? $count > $limit : $count >= $limit);
        $problems = [];
        if ($over($dayLimit, $dayCount)) {
            $problems[] = sprintf('%d de %d eventos no dia%s', $dayCount, $dayLimit, $complexWeek ? ' (semana com evento complexo)' : '');
        }
        if ($over($weekLimit, $weekCount)) {
            $problems[] = sprintf('%d de %d eventos na semana%s', $weekCount, $weekLimit, $complexWeek ? ' (semana com evento complexo)' : '');
        }
        if ($rules->max_guests_per_day && ($cand ? $guests > $rules->max_guests_per_day : $guests >= $rules->max_guests_per_day)) {
            $problems[] = sprintf('%s de %s pessoas no dia', number_format($guests, 0, ',', '.'), number_format($rules->max_guests_per_day, 0, ',', '.'));
        }
        if (($cand ? ($cand['small'] ?? false) : true) && $over($rules->max_small_per_weekend, $small)) {
            $problems[] = sprintf('%d de %d eventos pequenos (fundo de formatura) no fim de semana', $small, $rules->max_small_per_weekend);
        }
        if (($cand ? ($cand['pre_event'] ?? false) : true) && $over($rules->max_pre_events_per_weekend, $pre)) {
            $problems[] = sprintf('%d de %d pré-eventos no fim de semana', $pre, $rules->max_pre_events_per_weekend);
        }

        return [
            'events_day' => $dayCount,
            'limit_day' => $dayLimit,
            'events_week' => $weekCount,
            'limit_week' => $weekLimit,
            'guests_day' => $guests,
            'limit_guests' => $rules->max_guests_per_day,
            'complex_week' => $complexWeek,
            'small_weekend' => $small,
            'pre_events_weekend' => $pre,
            'full' => (bool) $problems,
            'problems' => $problems,
        ];
    }

    /** Agenda do mês: eventos marcados, pré-reservas (leads com proposta), interessados, datas bloqueadas e capacidade. */
    public function calendar(string $month, Builder $leadQuery): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $rules = $this->rules();

        $items = $this->agendaItems(
            $start->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
            $end->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            null,
            $rules
        );
        $events = $items->where('kind', 'event');

        $leads = (clone $leadQuery)
            ->whereNotIn('stage', ['ganho', 'perdido'])
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->with(['user', 'eventType'])
            ->get()
            ->map(fn (Lead $l) => $this->brief($l) + [
                'date' => $l->event_date->toDateString(),
                'pre_reserve' => $l->holdsPreReserve(),
                'proposal_valid_until' => $l->proposalValidUntil()?->toDateString(),
            ]);

        $blocked = BlockedDate::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->map(fn ($b) => ['date' => Carbon::parse($b->date)->toDateString(), 'description' => $b->description]);

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $dayEvents = $events->where('date', $key)->values();
            $dayLeads = $leads->where('date', $key)->values();
            $dayBlocked = $blocked->where('date', $key)->values();
            $capacity = $this->capacity($key, $items, null, $rules);
            $days[] = [
                'date' => $key,
                'weekday' => $d->dayOfWeekIso,
                'events' => $dayEvents->all(),
                'leads' => $dayLeads->all(),
                'blocked' => $dayBlocked->all(),
                'pre_reserves' => $items->where('kind', 'pre_reserve')->where('date', $key)->count(),
                'capacity' => $capacity,
                'full' => $dayBlocked->isNotEmpty() || $capacity['full'],
            ];
        }

        return [
            'month' => $month,
            'high_season' => $rules->isHighSeason($start->toDateString()),
            'max_events_per_day' => $rules->max_events_per_day,
            'pre_reserve_days' => $rules->pre_reserve_days,
            'proposal_validity_days' => $rules->proposal_validity_days,
            'days' => $days,
        ];
    }

    /**
     * Situação de uma data para o lead: bloqueio, capacidade (eventos + pré-reservas de outros leads) e interessados.
     * A pré-reserva de outro lead conta na capacidade; não trava a data sozinha.
     */
    public function dateStatus(string $date, ?int $excludeLeadId = null, ?CommercialRule $rules = null, ?array $candidate = null): array
    {
        $rules ??= $this->rules();
        $date = Carbon::parse($date)->toDateString();
        $day = Carbon::parse($date);

        if ($candidate === null && $excludeLeadId && ($lead = Lead::find($excludeLeadId))) {
            $candidate = ['name' => $lead->name, 'guests' => $lead->guests];
        }
        $candidate ??= ['name' => null, 'guests' => null];

        $items = $this->agendaItems(
            $day->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
            $day->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            $excludeLeadId,
            $rules
        );
        $dayItems = $items->where('date', $date)->values();
        $holders = $dayItems->where('kind', 'pre_reserve')->values();
        $events = $dayItems->where('kind', 'event')->values();
        $blocked = BlockedDate::whereDate('date', $date)->first();
        $interested = Lead::whereNotIn('stage', ['ganho', 'perdido'])
            ->whereDate('event_date', $date)
            ->when($excludeLeadId, fn ($q) => $q->where('id', '!=', $excludeLeadId))
            ->count() - $holders->count();
        $capacity = $this->capacity($date, $items, $candidate, $rules);

        $messages = [];
        $available = true;
        if ($blocked) {
            $available = false;
            $messages[] = 'data bloqueada' . ($blocked->description ? " ({$blocked->description})" : '');
        }
        if ($capacity['full']) {
            $available = false;
            $messages[] = 'passa do limite: ' . implode(', ', $capacity['problems']);
        }
        if ($events->isNotEmpty()) {
            $messages[] = sprintf('%d evento(s) marcado(s)', $events->count());
        }
        if ($holders->isNotEmpty()) {
            $messages[] = 'pré-reserva de ' . $holders->map(fn ($h) => $h['name'] . (!empty($h['user_name']) ? " ({$h['user_name']})" : ''))->implode(', ');
        }
        if ($interested > 0) {
            $messages[] = sprintf('%d outro(s) lead(s) interessado(s)', $interested);
        }

        return [
            'date' => $date,
            'available' => $available,
            'high_season' => $rules->isHighSeason($date),
            'events' => $events->map(fn ($e) => ['id' => $e['id'], 'name' => $e['name'], 'status' => $e['status']])->all(),
            'pre_reserves' => $holders->map(fn ($h) => ['id' => $h['id'], 'name' => $h['name'], 'user_name' => $h['user_name'] ?? null, 'proposal_valid_until' => $h['proposal_valid_until'] ?? null])->all(),
            'interested' => max(0, $interested),
            'blocked' => $blocked ? ($blocked->description ?: true) : null,
            'capacity' => $capacity,
            'message' => $messages ? ucfirst(implode('; ', $messages)) . '.' : 'Data livre.',
        ];
    }

    // ------------------------------------------------------------------ apoio

    public function brief(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'phone' => $lead->phone,
            'stage' => $lead->stage,
            'temperature' => $lead->temperature,
            'user_id' => $lead->user_id,
            'user_name' => $lead->user?->name,
            'event_type_name' => $lead->eventType?->name,
            'event_date' => $lead->event_date?->toDateString(),
            'guests' => $lead->guests,
            'next_contact_date' => $lead->next_contact_date?->toDateString(),
            'next_action' => $lead->next_action,
            'meeting_date' => $lead->meeting_date?->toDateString(),
            'estimated_value' => $lead->estimated_value !== null ? (float) $lead->estimated_value : null,
            'approval_status' => $lead->approval_status,
            'approval_request_note' => $lead->approval_request_note,
            'days_in_stage' => $lead->daysInStage(),
        ];
    }

    private function alert(Lead $lead, string $type, string $message, string $severity): array
    {
        return ['type' => $type, 'severity' => $severity, 'message' => $message, 'lead' => $this->brief($lead)];
    }

    private function severityRank(string $s): int
    {
        return ['alta' => 0, 'media' => 1, 'baixa' => 2][$s] ?? 3;
    }

    private function notify(string $type, User $user, string $title, string $message, Lead $lead): void
    {
        try {
            $this->notifications->create($type, $user, $title, $message, ['lead_id' => $lead->id], $lead);
        } catch (\Throwable) {
            // Tipo de notificação ausente não pode derrubar a gravação do lead.
        }
    }

    private function pct($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    }

    private function num($v): ?float
    {
        return $v === null || $v === '' ? null : round((float) $v, 2);
    }
}
