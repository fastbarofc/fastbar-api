<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class Lead extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    /**
     * Colunas do funil, na ordem definida pela gerência comercial (requisitos de 27/10/2026).
     * Nutrição e Licitações são caminhos laterais; Ganho e Perdido fecham o lead.
     */
    public const STAGES = [
        'novo',                  // Recebimento de leads
        'em_contato',            // Em contato / Qualificação
        'reuniao',               // Reunião agendada
        'proposta_enviada',
        'follow_up',
        'negociacao',            // Negociação / Fechamento
        'aguardando_assinatura',
        'nutricao',              // lateral: sem data
        'licitacao',             // lateral: edital
        'ganho',
        'perdido',
    ];

    /** Etapas do caminho principal, na ordem em que o lead avança. */
    public const MAIN_PATH = [
        'novo',
        'em_contato',
        'reuniao',
        'proposta_enviada',
        'follow_up',
        'negociacao',
        'aguardando_assinatura',
        'ganho',
    ];

    public const LATERAL_STAGES = ['nutricao', 'licitacao'];

    /** Canais de entrada do lead. */
    public const SOURCES = [
        'whatsapp',
        'instagram',
        'trafego_pago',
        'site',
        'cerimonial',
        'indicacao',
        'direto',
        'prospeccao',
        'sdr',
        'licitacao',
        'outro',
    ];

    public const TEMPERATURES = ['quente', 'morno', 'frio'];

    public const MEETING_RESULTS = ['realizada', 'remarcada', 'nao_compareceu'];

    public const TASTING_RESULTS = ['aprovado', 'ajustar', 'desistiu'];

    /** Aprovação da gerência (desconto acima do permitido ou viabilidade abaixo da mínima). */
    public const APPROVAL_STATUSES = ['pendente', 'aprovado', 'recusado'];

    /** Etapas em que o lead "segura" a data (pré-reserva) enquanto a proposta vale. */
    public const PRE_RESERVE_STAGES = ['proposta_enviada', 'follow_up', 'negociacao', 'aguardando_assinatura'];

    /** Tipos de atividade; os três primeiros contam como tentativa de contato. */
    public const CONTACT_TYPES = ['whatsapp', 'ligacao', 'email'];
    public const ACTIVITY_TYPES = ['whatsapp', 'ligacao', 'email', 'reuniao', 'visita', 'degustacao', 'outro'];
    public const ACTIVITY_RESULTS = ['realizado', 'sem_resposta', 'remarcado', 'nao_compareceu'];

    /** Padrão do "Sem resposta" (6 tentativas, 2 canais, 14 dias); o valor usado vem de Regras comerciais. */
    public const NO_ANSWER_MIN_ATTEMPTS = 6;
    public const NO_ANSWER_MIN_CHANNELS = 2;
    public const NO_ANSWER_WINDOW_DAYS = 14;

    /**
     * Dados obrigatórios para SAIR de cada etapa do caminho principal (rumo à próxima).
     * Chave = etapa de origem; valor = campo => nome para a pessoa.
     */
    public const STAGE_REQUIREMENTS = [
        'novo' => [
            'phone'         => 'telefone',
            'source'        => 'canal de origem',
            'event_type_id' => 'tipo de evento',
            'city'          => 'cidade',
            'guests'        => 'número de convidados',
        ],
        'em_contato' => [
            'event_date'  => 'data do evento',
            'temperature' => 'temperatura (quente, morno ou frio)',
        ],
        'reuniao' => [
            'meeting_date'   => 'data da reunião',
            'meeting_result' => 'resultado da reunião',
        ],
        'proposta_enviada' => [
            'estimated_value'    => 'valor da proposta',
            'expected_viability' => 'viabilidade prevista %',
            'proposal_sent_at'   => 'data de envio da proposta',
        ],
        'follow_up' => [
            'next_contact_date' => 'data do próximo contato',
        ],
        'negociacao' => [
            'final_value' => 'valor final',
        ],
        'aguardando_assinatura' => [
            'deposit_value' => 'valor do sinal',
            'deposit_date'  => 'data do pagamento do sinal',
        ],
    ];

    /**
     * Dias que o lead pode ficar na etapa sem atividade antes de contar como parado.
     * Recebimento conta em dias úteis; Reunião conta a partir da data da reunião.
     */
    public const STAGE_MAX_DAYS = [
        'novo'                  => 1,
        'em_contato'            => 3,
        'reuniao'               => 2,
        'proposta_enviada'      => 10,
        'follow_up'             => 10,
        'negociacao'            => 10,
        'aguardando_assinatura' => 5,
        'nutricao'              => 30,
        'licitacao'             => 10,
    ];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'instagram',
        'city',
        'served_city_id',
        'source',
        'temperature',
        'tags',
        'partner_id',
        'event_type_id',
        'event_date',
        'guests',
        'estimated_value',
        'expected_viability',
        'real_viability',
        'meeting_date',
        'meeting_result',
        'meeting_notes',
        'proposal_sent_at',
        'proposal_valid_until',
        'pre_reserve_released_at',
        'pre_reserve_released_by',
        'final_value',
        'discount_percent',
        'approval_status',
        'approval_note',
        'approval_request_note',
        'approved_by',
        'approved_at',
        'deposit_value',
        'deposit_date',
        'tasting',
        'tasting_date',
        'tasting_result',
        'tasting_participants',
        'stage',
        'position',
        'user_id',
        'lost_reason_id',
        'lost_note',
        'competitor',
        'competing_company_id',
        'client_id',
        'event_id',
        'next_contact_date',
        'next_action',
        'observation',
        'stage_changed_at',
        'first_contact_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'tasting' => 'boolean',
        'event_date' => 'date:Y-m-d',
        'meeting_date' => 'date:Y-m-d',
        'proposal_sent_at' => 'date:Y-m-d',
        'proposal_valid_until' => 'date:Y-m-d',
        'pre_reserve_released_at' => 'datetime',
        'deposit_date' => 'date:Y-m-d',
        'tasting_date' => 'date:Y-m-d',
        'next_contact_date' => 'date:Y-m-d',
        'stage_changed_at' => 'datetime',
        'first_contact_at' => 'datetime',
        'approved_at' => 'datetime',
        'estimated_value' => 'decimal:2',
        'final_value' => 'decimal:2',
        'deposit_value' => 'decimal:2',
        'expected_viability' => 'decimal:2',
        'real_viability' => 'decimal:2',
        'discount_percent' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function servedCity()
    {
        return $this->belongsTo(ServedCity::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function commission()
    {
        return $this->hasOne(LeadCommission::class);
    }

    public function lostReason()
    {
        return $this->belongsTo(LostReason::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function competingCompany()
    {
        return $this->belongsTo(CompetingCompany::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function comments()
    {
        return $this->hasMany(LeadComment::class)->latest();
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('happened_at')->orderByDesc('id');
    }

    public static function isMainStage(string $stage): bool
    {
        return in_array($stage, self::MAIN_PATH, true);
    }

    /** Grava cada mudança de etapa no histórico (gráficos do Comercial). */
    protected static function booted(): void
    {
        static::saved(function (Lead $lead) {
            if (!$lead->wasRecentlyCreated && !$lead->wasChanged('stage')) {
                return;
            }
            LeadStageChange::create([
                'lead_id' => $lead->id,
                'from_stage' => $lead->wasRecentlyCreated ? null : $lead->getOriginal('stage'),
                'to_stage' => $lead->stage,
                'user_id' => auth()->id(),
                'changed_at' => now(),
            ]);
        });
    }

    public function stageChanges()
    {
        return $this->hasMany(LeadStageChange::class);
    }

    public static function isClosedStage(string $stage): bool
    {
        return in_array($stage, ['ganho', 'perdido'], true);
    }

    /**
     * Campos que faltam para o lead ir para a etapa de destino.
     * Só cobra quando avança no caminho principal: volta, Nutrição, Licitações e Perdido não cobram.
     * $values = dados que o lead vai ter depois da gravação (atuais + alterados).
     */
    public static function missingFor(string $fromStage, string $toStage, array $values): array
    {
        if (!self::isMainStage($toStage) || $toStage === 'novo') {
            return [];
        }
        $toIndex = array_search($toStage, self::MAIN_PATH, true);
        $fromIndex = self::isMainStage($fromStage) ? array_search($fromStage, self::MAIN_PATH, true) : 0;
        if ($toIndex <= $fromIndex) {
            return [];
        }

        $missing = [];
        for ($i = $fromIndex; $i < $toIndex; $i++) {
            $stage = self::MAIN_PATH[$i];
            // Reunião e Proposta podem se inverter: os dados da reunião só são cobrados ao sair da própria Reunião.
            if ($stage === 'reuniao' && $fromStage !== 'reuniao') {
                continue;
            }
            foreach (self::STAGE_REQUIREMENTS[$stage] ?? [] as $field => $label) {
                $value = $values[$field] ?? null;
                if ($value === null || $value === '' || $value === []) {
                    $missing[$field] = $label;
                }
            }
        }

        return $missing;
    }

    /** Tentativas de contato sem resposta dentro da janela da regra e em quantos canais. */
    public function noAnswerAttempts(): array
    {
        $since = now()->subDays(self::noAnswerRule()['days'])->startOfDay();
        $attempts = $this->activities()
            ->whereIn('type', self::CONTACT_TYPES)
            ->where('result', 'sem_resposta')
            ->where('happened_at', '>=', $since)
            ->get(['type']);

        return [
            'count' => $attempts->count(),
            'channels' => $attempts->pluck('type')->unique()->count(),
        ];
    }

    /** Dias que o lead está parado na etapa atual (Recebimento em dias úteis; Reunião a partir da data da reunião). */
    public function daysInStage(): int
    {
        if (self::isClosedStage($this->stage)) {
            return 0;
        }
        $start = $this->stage_changed_at ?? $this->created_at ?? now();
        if ($this->stage === 'reuniao' && $this->meeting_date) {
            $start = Carbon::parse($this->meeting_date)->endOfDay();
        }
        $lastActivity = $this->relationLoaded('activities')
            ? $this->activities->max('happened_at')
            : null;
        if ($lastActivity && Carbon::parse($lastActivity)->gt($start) && $this->stage !== 'reuniao') {
            $start = Carbon::parse($lastActivity);
        }
        if ($this->stage === 'novo') {
            return (int) Carbon::parse($start)->diffInWeekdays(now());
        }

        return (int) Carbon::parse($start)->startOfDay()->diffInDays(now()->startOfDay());
    }

    private static ?array $stageMaxDaysCache = null;

    /** Prazo por etapa vindo de Configurações > Regras comerciais (padrão do código quando não configurado). */
    public static function stageMaxDays(): array
    {
        if (self::$stageMaxDaysCache === null) {
            try {
                self::$stageMaxDaysCache = CommercialRule::current()->stageMaxDays();
            } catch (\Throwable) {
                self::$stageMaxDaysCache = self::STAGE_MAX_DAYS;
            }
        }

        return self::$stageMaxDaysCache;
    }

    private static ?array $noAnswerRuleCache = null;

    /** Regra do "Sem resposta" vinda de Configurações > Regras comerciais. */
    public static function noAnswerRule(): array
    {
        if (self::$noAnswerRuleCache === null) {
            try {
                self::$noAnswerRuleCache = CommercialRule::current()->noAnswerRule();
            } catch (\Throwable) {
                self::$noAnswerRuleCache = ['attempts' => self::NO_ANSWER_MIN_ATTEMPTS, 'channels' => self::NO_ANSWER_MIN_CHANNELS, 'days' => self::NO_ANSWER_WINDOW_DAYS];
            }
        }

        return self::$noAnswerRuleCache;
    }

    public static function forgetRulesCache(): void
    {
        self::$stageMaxDaysCache = null;
        self::$noAnswerRuleCache = null;
    }

    public function isStalled(): bool
    {
        $max = self::stageMaxDays()[$this->stage] ?? null;

        return $max !== null && $this->daysInStage() > $max;
    }

    /** Até quando a proposta vale (campo informado ou envio + dias da regra). */
    public function proposalValidUntil(): ?Carbon
    {
        if ($this->proposal_valid_until) {
            return Carbon::parse($this->proposal_valid_until)->endOfDay();
        }
        if ($this->proposal_sent_at) {
            $days = CommercialRule::current()->proposal_validity_days ?? 7;

            return Carbon::parse($this->proposal_sent_at)->addDays($days)->endOfDay();
        }

        return null;
    }

    /**
     * Lead com proposta enviada segura a data do evento (pré-reserva).
     * A reserva NÃO cai sozinha quando a proposta vence: só sai quando alguém clica em "Liberar data"
     * ou quando o lead é ganho/perdido (gerência comercial, 05/11/2026).
     */
    public function holdsPreReserve(): bool
    {
        if (!$this->event_date || !in_array($this->stage, self::PRE_RESERVE_STAGES, true)) {
            return false;
        }

        return $this->proposalValidUntil() !== null && !$this->pre_reserve_released_at;
    }

    public function preReserveReleasedBy()
    {
        return $this->belongsTo(User::class, 'pre_reserve_released_by');
    }

    public function isOpen(): bool
    {
        return !self::isClosedStage($this->stage);
    }
}
