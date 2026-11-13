<?php

namespace App\Services;

use App\Models\Ceremonial;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\Company;
use App\Models\CompetingCompany;
use App\Models\CostCenter;
use App\Models\Decorator;
use App\Models\Event;
use App\Models\EventCapture;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\FinancialOperation;
use App\Models\HowDidYouHear;
use App\Models\LostReason;
use App\Models\ParthenonBankAccount;
use App\Models\Partner;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Sector;
use App\Models\SectorOffice;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OwenIt\Auditing\Models\Audit;

/**
 * Monta o histórico geral do sistema a partir da tabela "audits" (laravel-auditing),
 * traduzindo modelos, campos e valores para português.
 */
class ActivityHistoryService
{
    /** Model => [nome da área, grupo do filtro]. */
    public const AREAS = [
        'App\Models\Event' => ['Evento', 'Eventos'],
        'App\Models\EventBatch' => ['Lote do evento', 'Eventos'],
        'App\Models\EventKit' => ['Menu de drinks', 'Eventos'],
        'App\Models\EventNote' => ['Observação do evento', 'Eventos'],
        'App\Models\EventGift' => ['Brinde do evento', 'Eventos'],
        'App\Models\EventStatusHistory' => ['Status do evento', 'Eventos'],
        'App\Models\ExtraSale' => ['Venda extra', 'Eventos'],
        'App\Models\Contract' => ['Contrato', 'Eventos'],
        'App\Models\EventDocument' => ['Documento do evento', 'Eventos'],
        'App\Models\Failure' => ['Falha do evento', 'Eventos'],
        'App\Models\EventPayment' => ['Pagamento de evento', 'Financeiro'],
        'App\Models\Payable' => ['Conta a pagar', 'Financeiro'],
        'App\Models\Receivable' => ['Conta a receber', 'Financeiro'],
        'App\Models\Installment' => ['Parcela', 'Financeiro'],
        'App\Models\FinancialOperation' => ['Operação financeira', 'Financeiro'],
        'App\Models\CostCenter' => ['Centro de custo', 'Financeiro'],
        'App\Models\ParthenonBankAccount' => ['Conta bancária', 'Financeiro'],
        'App\Models\Client' => ['Cliente', 'Cadastros'],
        'App\Models\Collaborator' => ['Colaborador', 'Cadastros'],
        'App\Models\CollaboratorIncident' => ['Ocorrência de colaborador', 'Cadastros'],
        'App\Models\EventPlace' => ['Local', 'Cadastros'],
        'App\Models\Partner' => ['Parceiro', 'Cadastros'],
        'App\Models\Ceremonial' => ['Cerimonial', 'Cadastros'],
        'App\Models\Decorator' => ['Decorador', 'Cadastros'],
        'App\Models\Product' => ['Produto', 'Cadastros'],
        'App\Models\ProductGroup' => ['Grupo de produto', 'Cadastros'],
        'App\Models\ProductSubgroup' => ['Subgrupo de produto', 'Cadastros'],
        'App\Models\Supplier' => ['Fornecedor', 'Cadastros'],
        'App\Models\Kit' => ['Kit', 'Cadastros'],
        'App\Models\Company' => ['Empresa', 'Cadastros'],
        'App\Models\Address' => ['Endereço', 'Cadastros'],
        'App\Models\File' => ['Arquivo', 'Cadastros'],
        'App\Models\PurchaseRequest' => ['Solicitação de compra', 'Compras'],
        'App\Models\PurchaseOrder' => ['Pedido de compra', 'Compras'],
        'App\Models\Stock' => ['Estoque', 'Estoque'],
        'App\Models\StockLevel' => ['Nível de estoque', 'Estoque'],
        'App\Models\StockMovement' => ['Movimentação de estoque', 'Estoque'],
        'App\Models\Requisition' => ['Requisição', 'Estoque'],
        'App\Models\Devolution' => ['Devolução', 'Estoque'],
        'App\Models\EventStatus' => ['Status de evento', 'Configurações'],
        'App\Models\EventType' => ['Tipo de evento', 'Configurações'],
        'App\Models\EventCapture' => ['Forma de captura', 'Configurações'],
        'App\Models\LostReason' => ['Motivo de perda', 'Configurações'],
        'App\Models\HowDidYouHear' => ['Como nos conheceu', 'Configurações'],
        'App\Models\CompetingCompany' => ['Concorrente', 'Configurações'],
        'App\Models\PaymentMethod' => ['Método de pagamento', 'Configurações'],
        'App\Models\Unit' => ['Unidade', 'Configurações'],
        'App\Models\Utensil' => ['Utensílio', 'Configurações'],
        'App\Models\Material' => ['Material', 'Configurações'],
        'App\Models\BreakItem' => ['Item de quebra', 'Configurações'],
        'App\Models\BlockedDate' => ['Data bloqueada', 'Configurações'],
        'App\Models\Qualification' => ['Qualificação', 'Configurações'],
        'App\Models\Sector' => ['Setor', 'Configurações'],
        'App\Models\SectorOffice' => ['Cargo', 'Configurações'],
        'App\Models\OfficeBudget' => ['Função de colaborador', 'Configurações'],
        'App\Models\OfficeEvent' => ['Função no evento', 'Configurações'],
        'App\Models\User' => ['Usuário', 'Acesso ao sistema'],
    ];

    /** Colunas com texto de contrato: no histórico aparece só o trecho que mudou (ContractChangeService). */
    private const CONTRACT_FIELDS = [
        'App\Models\Event' => Event::HEAVY_COLUMNS,
        'App\Models\EventType' => ['contract_text'],
        'App\Models\Contract' => ['body', 'properties', 'properties_diff'],
    ];

    /** Registros que pertencem a um evento (Model => tabela com a coluna event_id). */
    private const EVENT_CHILDREN = [
        'App\Models\EventBatch' => 'event_batches',
        'App\Models\EventKit' => 'event_kits',
        'App\Models\EventNote' => 'event_notes',
        'App\Models\EventGift' => 'event_gifts',
        'App\Models\EventStatusHistory' => 'event_status_histories',
        'App\Models\ExtraSale' => 'extra_sales',
        'App\Models\Contract' => 'contracts',
        'App\Models\EventPayment' => 'event_payments',
        'App\Models\Requisition' => 'requisitions',
        'App\Models\EventDocument' => 'event_documents',
    ];

    /** Orçamento, contrato, aditivo e fechamento: nome do tipo e o que cada mudança de situação quer dizer. */
    private const DOCUMENT_TYPES = [
        'orcamento' => 'Orçamento',
        'contrato' => 'Contrato',
        'aditivo' => 'Aditivo',
        'fechamento' => 'Fechamento',
    ];

    private const DOCUMENT_STATUS_ACTIONS = [
        'gerado' => 'Gerou de novo',
        'enviado' => 'Enviou para assinatura',
        'aprovado' => 'Aprovou',
        'recusado' => 'Recusou',
        'assinado' => 'Assinado',
        'cancelado' => 'Cancelou',
    ];

    public const ACTIONS = [
        'created' => 'Criou',
        'updated' => 'Alterou',
        'deleted' => 'Excluiu',
        'restored' => 'Restaurou',
        'login' => 'Entrou no sistema',
    ];

    /** Campos usados como nome do registro, em ordem de preferência. */
    private const NAME_FIELDS = [
        'name', 'fantasy_name', 'corporate_name', 'internal_name', 'trade_name',
        'description', 'bank_name', 'code', 'street', 'text',
    ];

    /** Campos que não aparecem no "o que mudou". */
    private const HIDDEN_FIELDS = [
        'id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token',
        'old_system_id', 'created_by', 'updated_by', 'slug',
        // Documentos do evento: dados internos (cópia do evento, PDF, assinatura).
        'snapshot', 'changes', 'proposal', 'pdf_path', 'signed_pdf_path', 'signature_provider_id', 'signers',
        'sent_at', 'decided_at',
    ];

    /** Chave estrangeira => [Model, coluna com o nome]. */
    private const FOREIGN_KEYS = [
        'client_id' => [Client::class, 'name'],
        'invoice_client_id' => [Client::class, 'name'],
        'collaborator_id' => [Collaborator::class, 'name'],
        'user_id' => [User::class, 'name'],
        'requester_id' => [User::class, 'name'],
        'registered_by_id' => [User::class, 'name'],
        'event_id' => [Event::class, 'name'],
        'event_type_id' => [EventType::class, 'name'],
        'event_place_id' => [EventPlace::class, 'name'],
        'event_status_id' => [EventStatus::class, 'name'],
        'event_capture_id' => [EventCapture::class, 'name'],
        'partner_id' => [Partner::class, 'name'],
        'decorator_id' => [Decorator::class, 'name'],
        'ceremonial_id' => [Ceremonial::class, 'name'],
        'competing_company_id' => [CompetingCompany::class, 'name'],
        'lost_reason_id' => [LostReason::class, 'name'],
        'how_did_you_hear_id' => [HowDidYouHear::class, 'name'],
        'payment_method_id' => [PaymentMethod::class, 'name'],
        'parthenon_bank_account_id' => [ParthenonBankAccount::class, 'bank_name'],
        'financial_operation_id' => [FinancialOperation::class, 'description'],
        'cost_center_id' => [CostCenter::class, 'description'],
        'company_id' => [Company::class, 'internal_name'],
        'product_id' => [Product::class, 'name'],
        'product_group_id' => [ProductGroup::class, 'description'],
        'stock_id' => [Stock::class, 'name'],
        'destination_stock_id' => [Stock::class, 'name'],
        'sector_id' => [Sector::class, 'name'],
        'sector_office_id' => [SectorOffice::class, 'name'],
        'unit_id' => [Unit::class, 'description'],
        'supplier_id' => [Supplier::class, 'fantasy_name'],
    ];

    private const FIELD_LABELS = [
        'name' => 'Nome',
        'nickname' => 'Apelido',
        'email' => 'E-mail',
        'phone' => 'Telefone',
        'phone_1' => 'Telefone 1',
        'phone_2' => 'Telefone 2',
        'employment_type' => 'Tipo de contratação',
        'biometric_consent_at' => 'Consentimento da biometria em',
        'biometric_consent_by' => 'Consentimento da biometria por',
        'description' => 'Descrição',
        'type' => 'Tipo',
        'status' => 'Situação',
        'active' => 'Ativo',
        'value' => 'Valor',
        'amount' => 'Valor',
        'price' => 'Preço',
        'quantity' => 'Quantidade',
        'date' => 'Data',
        'time' => 'Horário',
        'due_date' => 'Vencimento',
        'paid_at' => 'Pago em',
        'birth_date' => 'Data de nascimento',
        'opening_date' => 'Data de abertura',
        'expected_date' => 'Data prevista',
        'signed_at' => 'Assinado em',
        'closed_at' => 'Data de fechamento',
        'cpf' => 'CPF',
        'rg' => 'RG',
        'cnpj' => 'CNPJ',
        'ie' => 'Inscrição estadual',
        'im' => 'Inscrição municipal',
        'state_registration' => 'Inscrição estadual',
        'corporate_name' => 'Razão social',
        'fantasy_name' => 'Nome fantasia',
        'legal_name' => 'Razão social',
        'trade_name' => 'Nome fantasia',
        'internal_name' => 'Nome interno',
        'responsible' => 'Responsável',
        'observation' => 'Observação',
        'observations' => 'Observações',
        'notes' => 'Observações',
        'comment' => 'Comentário',
        'text' => 'Texto',
        'body' => 'Texto do contrato',
        'version' => 'Versão',
        'code' => 'Código',
        'account' => 'Conta',
        'nature' => 'Natureza',
        'bank_name' => 'Banco',
        'account_number' => 'Número da conta',
        'street' => 'Rua',
        'number' => 'Número',
        'district' => 'Bairro',
        'complement' => 'Complemento',
        'zip_code' => 'CEP',
        'reference' => 'Referência',
        'city_id' => 'Cidade',
        'state_id' => 'Estado',
        'is_employee' => 'É funcionário',
        'has_login' => 'Tem acesso ao sistema',
        'guests' => 'Convidados',
        'unit_value' => 'Valor unitário',
        'installment_number' => 'Número da parcela',
        'barcode' => 'Código de barras',
        'weight' => 'Peso',
        'manufacturer' => 'Fabricante',
        'current_quantity' => 'Quantidade atual',
        'min_quantity' => 'Quantidade mínima',
        'max_quantity' => 'Quantidade máxima',
        'weighted_average_price' => 'Preço médio',
        'total_value' => 'Valor total',
        'checklist' => 'Checklist',
        'optional' => 'Opcional',
        'ordination' => 'Ordem',
        'percentage' => 'Percentual',
        'lease' => 'Valor da locação',
        'duration' => 'Duração (horas)',
        'discount' => 'Desconto',
        'event_guests' => 'Quantidade de convidados',
        'buffet_value' => 'Valor do buffet',
        'is_draft' => 'Rascunho',
        'ip' => 'IP',
        'client_id' => 'Cliente',
        'invoice_client_id' => 'Cliente da nota fiscal',
        'collaborator_id' => 'Colaborador',
        'user_id' => 'Usuário',
        'requester_id' => 'Solicitante',
        'registered_by_id' => 'Registrado por',
        'event_id' => 'Evento',
        'event_type_id' => 'Tipo de evento',
        'event_place_id' => 'Local',
        'event_status_id' => 'Status do evento',
        'event_capture_id' => 'Forma de captura',
        'partner_id' => 'Parceiro',
        'decorator_id' => 'Decorador',
        'ceremonial_id' => 'Cerimonial',
        'competing_company_id' => 'Concorrente',
        'lost_reason_id' => 'Motivo da perda',
        'how_did_you_hear_id' => 'Como nos conheceu',
        'payment_method_id' => 'Método de pagamento',
        'parthenon_bank_account_id' => 'Conta bancária',
        'financial_operation_id' => 'Operação financeira',
        'cost_center_id' => 'Centro de custo',
        'company_id' => 'Empresa',
        'product_id' => 'Produto',
        'product_group_id' => 'Grupo de produto',
        'stock_id' => 'Estoque',
        'destination_stock_id' => 'Estoque de destino',
        'sector_id' => 'Setor',
        'sector_office_id' => 'Cargo',
        'unit_id' => 'Unidade',
        'supplier_id' => 'Fornecedor',
    ];

    /** Campos de dinheiro: aparecem como R$ 1.234,56. */
    private const MONEY_FIELDS = [
        'value', 'amount', 'price', 'total_value', 'buffet_value', 'lease', 'discount', 'unit_value',
        'guest_unit_value', 'excess_unit_value', 'unit_insurance', 'weighted_average_price',
    ];

    private const VALUE_LABELS = [
        'pending' => 'Pendente',
        'paid' => 'Pago',
        'overdue' => 'Vencido',
        'approved' => 'Aprovado',
        'rejected' => 'Recusado',
        'canceled' => 'Cancelado',
        'cancelled' => 'Cancelado',
        'completed' => 'Concluído',
        'gerado' => 'Gerado',
        'enviado' => 'Enviado para assinatura',
        'aprovado' => 'Aprovado',
        'recusado' => 'Recusado',
        'assinado' => 'Assinado',
        'cancelado' => 'Cancelado',
        'orcamento' => 'Orçamento',
        'contrato' => 'Contrato',
        'aditivo' => 'Aditivo',
        'fechamento' => 'Fechamento',
        'in' => 'Entrada',
        'out' => 'Saída',
    ];

    /** Lista paginada com os filtros da tela. */
    public function paginate(array $filters, int $perPage)
    {
        $query = Audit::query()->orderByDesc('created_at')->orderByDesc('id');

        $query->whereIn('auditable_type', array_keys(self::AREAS));

        // Alterações sem nenhum campo mudado não interessam.
        $query->where(function ($q) {
            $q->where('event', '!=', 'updated')
                ->orWhereNotIn('new_values', ['[]', '{}']);
        });

        $this->hideAddendumDrafts($query);

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['action'])) {
            $query->where('event', $filters['action']);
        }
        if (!empty($filters['group'])) {
            $types = collect(self::AREAS)->filter(fn($area) => $area[1] === $filters['group'])->keys();
            $query->whereIn('auditable_type', $types);
        }
        if (!empty($filters['event_id'])) {
            $eventId = (int) $filters['event_id'];
            $query->where(function ($q) use ($eventId) {
                // auditable_id é texto no banco: comparar como texto usa o índice (com número, varria tudo).
                $q->where(fn($q) => $q->where('auditable_type', Event::class)->where('auditable_id', (string) $eventId));
                foreach (self::EVENT_CHILDREN as $type => $table) {
                    if (!Schema::hasTable($table)) {
                        continue;
                    }
                    $q->orWhere(fn($q) => $q->where('auditable_type', $type)
                        ->whereIn('auditable_id', DB::table($table)->select('id')->where('event_id', $eventId)));
                }
                $q->orWhere(fn($q) => $q->where('auditable_type', 'App\Models\Installment')
                    ->whereIn('auditable_id', DB::table('payment_plan_installments')->select('payment_plan_installments.id')
                        ->join('payment_plans', 'payment_plans.id', '=', 'payment_plan_installments.payment_plan_id')
                        ->where('payment_plans.event_id', $eventId)));
            });
        }
        if (!empty($filters['area'])) {
            $query->where('auditable_type', $filters['area']);
        }

        // O total de linhas é a parte lenta (cresce com o histórico): fica guardado por 1 minuto para cada filtro.
        // As linhas da página são sempre buscadas na hora.
        $totalKey = 'activity-history:total:' . md5(json_encode(collect($filters)->except(['per_page', 'page'])->sortKeys()->all()));
        $total = (int) Cache::remember($totalKey, 60, fn () => $query->toBase()->getCountForPagination());
        $pageNumber = Paginator::resolveCurrentPage();
        $items = $total > 0 ? $query->forPage($pageNumber, $perPage)->get() : $query->getModel()->newCollection();
        $page = new LengthAwarePaginator($items, $total, $perPage, $pageNumber, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
        $page->setCollection($this->format($page->getCollection()));

        return $page;
    }

    /**
     * Esconde a cópia "sombra" que a janela do aditivo cria do evento (com o menu de drinks).
     * Ela é só um rascunho interno: abrir a janela gerava dezenas de "Criou Menu de drinks"
     * num evento que ninguém mexeu (pedido do André, 2026-11-03).
     * Tudo é descoberto pela própria tabela audits, então funciona mesmo depois de a sombra ser apagada.
     */
    private function hideAddendumDrafts($query): void
    {
        // Sem a coluna do aditivo-sombra no evento não existe rascunho para esconder: não varre o histórico à toa.
        if (!Schema::hasColumn('events', 'addendum_of_event_id')) {
            return;
        }

        $draftEventIds = Audit::query()
            ->where('auditable_type', Event::class)
            ->where('event', 'created')
            ->where('new_values', 'like', '%addendum_of_event_id%')
            ->get(['auditable_id', 'new_values'])
            ->filter(fn($audit) => !empty(($audit->new_values ?? [])['addendum_of_event_id']))
            ->pluck('auditable_id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (!$draftEventIds) {
            return;
        }

        $draftKitIds = Audit::query()
            ->where('auditable_type', 'App\Models\EventKit')
            ->where('event', 'created')
            // CASE garante que um texto que não seja JSON nunca chegue ao JSON_EXTRACT (daria erro no MySQL).
            ->whereIn(DB::raw("CASE WHEN JSON_VALID(new_values) THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(new_values, '$.event_id')) AS UNSIGNED) END"), $draftEventIds)
            ->pluck('auditable_id')
            ->map(fn($id) => (int) $id)
            ->all();

        // Itens da sombra que viraram itens do evento de verdade (aditivo assinado/aplicado):
        // esconde só o "Criou" da cópia; o que mudar neles depois aparece normalmente.
        $movedKitIds = $draftKitIds
            ? DB::table('event_kits')->whereIn('id', $draftKitIds)->whereNotIn('event_id', $draftEventIds)->pluck('id')->map(fn($id) => (int) $id)->all()
            : [];
        $hiddenKitIds = array_values(array_diff($draftKitIds, $movedKitIds));

        $query->whereNot(function ($q) use ($draftEventIds, $hiddenKitIds, $movedKitIds) {
            $q->where(fn($q) => $q->where('auditable_type', Event::class)->whereIn('auditable_id', $draftEventIds));
            if ($hiddenKitIds) {
                $q->orWhere(fn($q) => $q->where('auditable_type', 'App\Models\EventKit')->whereIn('auditable_id', $hiddenKitIds));
            }
            if ($movedKitIds) {
                $q->orWhere(fn($q) => $q->where('auditable_type', 'App\Models\EventKit')->where('event', 'created')->whereIn('auditable_id', $movedKitIds));
            }
        });
    }

    /** Opções dos filtros. */
    public function filterOptions(): array
    {
        $areas = collect(self::AREAS)
            ->map(fn($area, $type) => ['value' => $type, 'label' => $area[0], 'group' => $area[1]])
            ->sortBy('label')
            ->values();

        return [
            'groups' => array_values(array_unique(array_column(self::AREAS, 1))),
            'areas' => $areas,
            'actions' => collect(self::ACTIONS)->map(fn($label, $value) => compact('value', 'label'))->values(),
            'users' => User::withTrashed()->where('is_employee', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    /** Registra a entrada de um usuário no sistema. */
    public function logLogin(User $user): void
    {
        $request = request();

        Audit::create([
            'user_type' => User::class,
            'user_id' => $user->id,
            'event' => 'login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => [],
            'new_values' => [],
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 1023) : null,
        ]);
    }

    private function format(Collection $audits): Collection
    {
        $users = User::withTrashed()
            ->whereIn('id', $audits->pluck('user_id')->filter()->unique())
            ->pluck('name', 'id');

        $records = $this->loadRecords($audits);
        $names = $this->loadForeignNames($audits);

        return $audits->map(function (Audit $audit) use ($users, $records, $names) {
            $old = $this->clean($audit->old_values ?? []);
            $new = $this->clean($audit->new_values ?? []);
            [$contractChanges, $old, $new] = ContractChangeService::extractForDisplay(
                (string) $audit->event, $old, $new, self::CONTRACT_FIELDS[$audit->auditable_type] ?? []
            );
            $record = $records[$audit->auditable_type][$audit->auditable_id] ?? null;
            [$areaLabel, $group] = self::AREAS[$audit->auditable_type] ?? [class_basename($audit->auditable_type), null];
            $actionLabel = self::ACTIONS[$audit->event] ?? $audit->event;

            if ($audit->auditable_type === 'App\Models\EventDocument') {
                $type = $record?->type ?? $new['type'] ?? $old['type'] ?? null;
                $areaLabel = self::DOCUMENT_TYPES[$type] ?? $areaLabel;
                if ($audit->event === 'created') {
                    $actionLabel = 'Gerou';
                } elseif ($audit->event === 'updated' && isset($new['status'], self::DOCUMENT_STATUS_ACTIONS[$new['status']])) {
                    $actionLabel = self::DOCUMENT_STATUS_ACTIONS[$new['status']];
                } elseif ($audit->event === 'updated') {
                    $actionLabel = 'Editou';
                }
            }

            $changes = [];
            foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
                // Na alteração, só o que mudou de verdade (o sistema às vezes regrava "200" como "200.00",
                // vazio como nulo...): igual ao sistema antigo, só os campos alterados com Antes e Depois.
                if ($audit->event === 'updated' && $this->sameValue($old[$field] ?? null, $new[$field] ?? null)) {
                    continue;
                }
                $changes[] = [
                    'field' => $field,
                    'label' => self::FIELD_LABELS[$field] ?? ucfirst(str_replace('_', ' ', $field)),
                    'old' => $this->formatValue($field, $old[$field] ?? null, $names),
                    'new' => $this->formatValue($field, $new[$field] ?? null, $names),
                ];
            }

            $displayAction = $audit->event;
            if ($contractChanges) {
                $changes = array_merge($changes, $contractChanges);
                if ($audit->auditable_type === 'App\Models\Contract' && $audit->event === 'created') {
                    // Nova versão do contrato: mostra Antes e Depois do que mudou na versão anterior.
                    $changes = $contractChanges;
                    $displayAction = 'updated';
                    $actionLabel = 'Gerou nova versão';
                }
            }
            if ($audit->auditable_type === 'App\Models\EventDocument') {
                // Não repete a ficha do documento: mostra o que ele muda no evento (Antes → Depois),
                // que é a lista "O que mudou" que o próprio documento guarda.
                $docChanges = $this->documentChanges($audit);
                if ($audit->event === 'created') {
                    $changes = $docChanges ?: array_values(array_filter($changes, fn($c) => in_array($c['field'], ['total_value', 'guests', 'observation'], true)));
                    if ($docChanges) {
                        $displayAction = 'updated';
                    }
                } elseif ($audit->event === 'updated') {
                    $changes = array_merge($changes, $docChanges);
                }
            }

            return [
                'id' => $audit->id,
                'created_at' => $audit->created_at?->format('Y-m-d H:i:s'),
                'created_at_formatted' => $audit->created_at?->format('d/m/Y H:i:s'),
                'action' => $displayAction,
                'action_label' => $actionLabel,
                'area' => $audit->auditable_type,
                'area_label' => $areaLabel,
                'group' => $group,
                'record_id' => $audit->auditable_id,
                'record_label' => $this->recordLabel($audit, $record, $old, $new, $names),
                'event_id' => $audit->auditable_type === Event::class
                    ? (int) $audit->auditable_id
                    : ($record?->event_id ?? $new['event_id'] ?? $old['event_id'] ?? ($record instanceof \App\Models\Installment ? $record->paymentPlan?->event_id : null)),
                'user' => $audit->user_id ? ['id' => (int) $audit->user_id, 'name' => $users[$audit->user_id] ?? 'Usuário removido'] : null,
                'ip_address' => $audit->ip_address,
                'changes' => $audit->event === 'login' ? [] : $changes,
            ];
        });
    }

    /**
     * Linhas "O que mudou" guardadas no orçamento/contrato/aditivo ("Convidados: 180 → 200", "Entrou: Jantar / Massas")
     * viradas em Campo / Antes / Depois. Só entra quando a lista mudou nesse registro.
     */
    private function documentChanges(Audit $audit): array
    {
        $new = $audit->new_values ?? [];
        $old = $audit->old_values ?? [];
        if (!array_key_exists('changes', $new)) {
            return [];
        }
        $decode = fn($v) => is_string($v) ? (json_decode($v, true) ?: []) : (is_array($v) ? $v : []);
        $lines = $decode($new['changes']);
        if ($audit->event === 'updated' && $lines == $decode($old['changes'] ?? null)) {
            return [];
        }

        $rows = [];
        foreach ($lines as $line) {
            if (!is_string($line) || trim($line) === '') {
                continue;
            }
            $line = trim(strip_tags($line));
            if (preg_match('/^Entrou:\s*(.+)$/u', $line, $m)) {
                $rows[] = ['field' => 'item', 'label' => 'Menu de drinks (entrou)', 'old' => null, 'new' => $m[1]];
            } elseif (preg_match('/^Saiu:\s*(.+)$/u', $line, $m)) {
                $rows[] = ['field' => 'item', 'label' => 'Menu de drinks (saiu)', 'old' => $m[1], 'new' => null];
            } elseif (preg_match('/^([^:]+):\s*(.*?)\s*→\s*(.*)$/u', $line, $m)) {
                $rows[] = ['field' => 'doc', 'label' => $m[1], 'old' => $m[2] === '-' ? null : $m[2], 'new' => $m[3] === '-' ? null : $m[3]];
            } elseif (preg_match('/^([^:]+):\s*(.+)$/u', $line, $m)) {
                $rows[] = ['field' => 'doc', 'label' => $m[1], 'old' => null, 'new' => $m[2]];
            } else {
                $rows[] = ['field' => 'doc', 'label' => 'Observação', 'old' => null, 'new' => $line];
            }
        }

        return $rows;
    }

    /** Dois valores que, para quem lê, são o mesmo (200 = "200.00", "" = null, 1 = true...). */
    private function sameValue($old, $new): bool
    {
        $norm = function ($value) {
            if ($value === '' || $value === null || $value === [] ) {
                return null;
            }
            if (is_bool($value)) {
                return $value ? 1.0 : 0.0;
            }
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?/', $value)) {
                try {
                    return Carbon::parse($value)->format('Y-m-d H:i:s');
                } catch (\Throwable $e) {
                    return $value;
                }
            }
            if (is_string($value)) {
                return trim($value);
            }
            return $value;
        };

        return $norm($old) === $norm($new);
    }

    private function clean(array $values): array
    {
        return array_diff_key($values, array_flip(self::HIDDEN_FIELDS));
    }

    /** Carrega, de uma vez por tipo, os registros citados na página (inclusive os excluídos). */
    private function loadRecords(Collection $audits): array
    {
        $records = [];
        foreach ($audits->groupBy('auditable_type') as $type => $items) {
            if (!class_exists($type)) {
                continue;
            }
            $query = $type::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($type), true)) {
                $query->withTrashed();
            }
            $records[$type] = $query->whereIn('id', $items->pluck('auditable_id')->unique())->get()->keyBy('id')->all();
        }

        return $records;
    }

    /** Nomes das chaves estrangeiras que aparecem na página, para mostrar "Cliente: Maria" em vez de "Cliente: 12". */
    private function loadForeignNames(Collection $audits): array
    {
        $ids = [];
        foreach ($audits as $audit) {
            foreach ([$audit->old_values ?? [], $audit->new_values ?? []] as $values) {
                foreach ($values as $field => $value) {
                    if (isset(self::FOREIGN_KEYS[$field]) && is_scalar($value) && $value !== '') {
                        $ids[$field][] = $value;
                    }
                }
            }
        }

        $names = [];
        foreach ($ids as $field => $values) {
            [$model, $column] = self::FOREIGN_KEYS[$field];
            $query = $model::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $query->withTrashed();
            }
            $names[$field] = $query->whereIn('id', array_unique($values))->pluck($column, 'id')->all();
        }

        return $names;
    }

    private function recordLabel(Audit $audit, $record, array $old, array $new, array $names): string
    {
        // Na criação vale o nome da época; na exclusão, o último nome; nas alterações, o nome atual.
        $values = match ($audit->event) {
            'created' => array_merge($record ? $record->getAttributes() : [], $old, $new),
            'deleted' => array_merge($record ? $record->getAttributes() : [], $new, $old),
            default => array_merge($old, $new, $record ? $record->getAttributes() : []),
        };

        $label = null;
        foreach (self::NAME_FIELDS as $field) {
            if (!empty($values[$field]) && is_scalar($values[$field])) {
                $label = strip_tags((string) $values[$field]);
                break;
            }
        }

        if ($audit->auditable_type === 'App\Models\EventDocument') {
            $label = $record && method_exists($record, 'title')
                ? $record->title()
                : trim((self::DOCUMENT_TYPES[$values['type'] ?? ''] ?? 'Documento') . ' ' . ($values['number'] ?? ''));
        }

        if ($audit->auditable_type === 'App\Models\Installment' && isset($values['installment_number'])) {
            $label = 'Parcela ' . $values['installment_number'];
        }

        if ($audit->auditable_type === 'App\Models\StockMovement' && isset($values['product_id'])) {
            $label = $names['product_id'][$values['product_id']] ?? $label;
        }

        $eventId = $audit->auditable_type === Event::class ? null : ($values['event_id'] ?? null);
        if ($eventId && isset($names['event_id'][$eventId])) {
            $label = trim(($label ? $label . ' · ' : '') . 'Evento ' . $names['event_id'][$eventId]);
        } elseif ($eventId) {
            $eventName = Event::withTrashed()->whereKey($eventId)->value('name');
            if ($eventName) {
                $label = trim(($label ? $label . ' · ' : '') . 'Evento ' . $eventName);
            }
        }

        $label = $label ?: '#' . $audit->auditable_id;

        return mb_strimwidth($label, 0, 120, '…');
    }

    private function formatValue(string $field, $value, array $names)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (isset(self::FOREIGN_KEYS[$field]) && is_scalar($value)) {
            return $names[$field][$value] ?? (string) $value;
        }

        if (in_array($field, self::MONEY_FIELDS, true) && is_numeric($value)) {
            return 'R$ ' . number_format((float) $value, 2, ',', '.');
        }

        if (is_bool($value) || (in_array($field, ['active', 'is_employee', 'has_login', 'optional', 'is_draft', 'themed_table', 'marketable', 'own_production', 'display_checklist', 'belongs_parthenon', 'is_medicine', 'coordinator', 'checklist'], true) && in_array($value, [0, 1, '0', '1'], true))) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_string($value) && isset(self::VALUE_LABELS[$value]) && in_array($field, ['status', 'type'], true)) {
            return self::VALUE_LABELS[$value];
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $value)) {
            $date = Carbon::parse($value);
            return strlen($value) > 10 ? $date->format('d/m/Y H:i') : $date->format('d/m/Y');
        }

        if (is_string($value) && $value !== strip_tags($value)) {
            $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)));
        }

        return is_string($value) ? mb_strimwidth($value, 0, 500, '…') : $value;
    }
}
