<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventInvoice;
use App\Services\FocusNfeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Eventos > Nota fiscal (André, 2026-11-11): emite a NFS-e do evento pela Focus NFe.
 * O tomador é o cliente do campo "Nota Fiscal" do evento (ou o cliente do evento, se vazio).
 * A prefeitura processa a nota em segundos ou minutos: a tela consulta de novo até sair o número.
 */
class EventInvoiceController extends Controller
{
    /** Financeiro > Notas fiscais: relação de todas as notas emitidas, com filtros e totais. */
    public function list(Request $request)
    {
        $base = EventInvoice::query();
        if ($request->filled('date_from')) {
            $base->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $base->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('environment')) {
            $base->where('environment', $request->input('environment'));
        }
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('event', fn ($e) => $e->where('name', 'like', "%{$search}%")->orWhere('id', ctype_digit($search) ? (int) $search : 0))
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('cpf', 'like', "%{$search}%")->orWhere('cnpj', 'like', "%{$search}%"));
            });
        }

        $summary = [];
        foreach (array_keys(EventInvoice::STATUS_LABELS) as $status) {
            $q = (clone $base)->where('status', $status);
            $summary[$status] = ['count' => (clone $q)->count(), 'amount' => (float) (clone $q)->sum('amount')];
        }

        $query = (clone $base)->with(['company', 'client', 'createdBy', 'event:id,name,date']);
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }
        $page = $query->orderByDesc('id')->paginate((int) $request->input('per_page', 30));

        return response()->json([
            'data' => $page->getCollection()->map(fn ($i) => $i->toApi() + [
                'event' => $i->event ? ['id' => $i->event->id, 'name' => $i->event->name, 'date' => optional($i->event->date)->format('Y-m-d')] : null,
            ])->values(),
            'pagination' => [
                'page' => $page->currentPage(),
                'total_pages' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
            'summary' => $summary,
        ]);
    }

    /** Notas do evento e o que já vem preenchido para emitir uma nova. */
    public function index(Event $event)
    {
        $event->loadMissing(['client.address.city.state', 'invoiceClient.address.city.state']);
        $client = $event->invoiceClient ?: $event->client;

        $companies = Company::with('city.state')->orderBy('id')->get();
        $company = $companies->first(fn ($c) => $c->cnpj) ?: $companies->first();

        $invoices = EventInvoice::with(['company', 'client', 'createdBy'])
            ->where('event_id', $event->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => optional($event->date)->format('Y-m-d'),
            ],
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'document' => $client->cnpj ?: $client->cpf,
                'email' => $client->email,
                'from_invoice_field' => (bool) $event->invoiceClient,
            ] : null,
            'companies' => $companies->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->trade_name ?: $c->legal_name ?: $c->internal_name,
                'environment' => $c->nf_environment ?: 'homologacao',
                'missing' => $this->companyMissing($c),
            ])->values(),
            'default_company_id' => $company?->id,
            'default_amount' => (float) ($event->total_value ?: $event->sub_total ?: 0),
            'default_description' => $this->defaultDescription($event, $company),
            'client_missing' => $this->clientMissing($client),
            'tokens' => [
                'homologacao' => FocusNfeService::for('homologacao')->configured(),
                'producao' => FocusNfeService::for('producao')->configured(),
            ],
            'invoices' => $invoices->map->toApi()->values(),
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|min:5|max:2000',
        ], [
            'company_id.required' => 'Escolha a empresa que emite a nota.',
            'amount.required' => 'Informe o valor da nota.',
            'amount.min' => 'O valor da nota precisa ser maior que zero.',
            'description.required' => 'Escreva a descrição do serviço.',
        ]);

        $event->loadMissing(['client.address.city.state', 'invoiceClient.address.city.state']);
        $client = $event->invoiceClient ?: $event->client;
        $company = Company::with('city.state')->findOrFail($data['company_id']);

        $problems = array_merge($this->companyMissing($company), $this->clientMissing($client));
        if ($problems) {
            return response()->json(['message' => 'Falta preencher: ' . implode(', ', $problems) . '.'], 422);
        }

        $environment = $company->nf_environment === 'producao' ? 'producao' : 'homologacao';
        $focus = FocusNfeService::for($environment);
        if (!$focus->configured()) {
            $label = $environment === 'producao' ? 'de produção' : 'de homologação (teste)';
            return response()->json(['message' => "O token {$label} da Focus NFe ainda não foi colado em Configurações > APIs."], 422);
        }

        $ref = 'fastbar-ev' . $event->id . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(4));
        $invoice = EventInvoice::create([
            'event_id' => $event->id,
            'company_id' => $company->id,
            'client_id' => $client->id,
            'ref' => $ref,
            'environment' => $environment,
            'status' => 'processando_autorizacao',
            'amount' => $data['amount'],
            'description' => trim($data['description']),
            'created_by' => $request->user()?->id,
        ]);

        try {
            $response = $focus->emit($ref, $this->payload($company, $client, (float) $data['amount'], trim($data['description'])));
        } catch (\Throwable $e) {
            report($e);
            $invoice->update(['status' => 'erro_autorizacao', 'message' => 'Não foi possível falar com a Focus NFe agora. Confira a internet e tente de novo.']);
            return response()->json(['message' => $invoice->message, 'invoice' => $invoice->fresh(['company', 'client', 'createdBy'])->toApi()], 422);
        }

        $this->applyResponse($invoice, $response->json() ?? [], $focus, $response->status());

        if ($invoice->status === 'processando_autorizacao') {
            // A prefeitura costuma responder rápido: consulta uma vez antes de devolver.
            sleep(2);
            $this->refreshFromFocus($invoice, $focus);
        }

        $invoice->refresh()->load(['company', 'client', 'createdBy']);
        $status = $invoice->status === 'erro_autorizacao' ? 422 : 201;
        return response()->json(['message' => $invoice->message, 'invoice' => $invoice->toApi()], $status);
    }

    /** Consulta de novo na Focus (para notas que ainda estão processando). */
    public function refresh(EventInvoice $invoice)
    {
        $this->refreshFromFocus($invoice, FocusNfeService::for($invoice->environment));
        return response()->json(['invoice' => $invoice->fresh(['company', 'client', 'createdBy'])->toApi()]);
    }

    public function cancel(Request $request, EventInvoice $invoice)
    {
        $data = $request->validate(
            ['reason' => 'required|string|min:15|max:255'],
            ['reason.required' => 'Escreva o motivo do cancelamento.', 'reason.min' => 'O motivo precisa ter pelo menos 15 letras (regra da prefeitura).']
        );
        if ($invoice->status !== 'autorizado') {
            return response()->json(['message' => 'Só dá para cancelar uma nota já emitida.'], 422);
        }

        $focus = FocusNfeService::for($invoice->environment);
        try {
            $response = $focus->cancel($invoice->ref, $data['reason']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Não foi possível falar com a Focus NFe agora. Tente de novo.'], 422);
        }

        $body = $response->json() ?? [];
        if (($body['status'] ?? null) === 'cancelado') {
            $invoice->update(['status' => 'cancelado', 'cancelled_at' => now(), 'message' => 'Cancelada: ' . $data['reason'], 'last_response' => $body]);
        } else {
            $invoice->update(['last_response' => $body]);
            return response()->json(['message' => $this->errorMessage($body) ?: 'A prefeitura não aceitou o cancelamento.'], 422);
        }

        return response()->json(['invoice' => $invoice->fresh(['company', 'client', 'createdBy'])->toApi()]);
    }

    private function refreshFromFocus(EventInvoice $invoice, FocusNfeService $focus): void
    {
        if (!$focus->configured()) {
            return;
        }
        try {
            $response = $focus->consult($invoice->ref);
        } catch (\Throwable $e) {
            report($e);
            return;
        }
        $this->applyResponse($invoice, $response->json() ?? [], $focus, $response->status());
    }

    private function applyResponse(EventInvoice $invoice, array $body, FocusNfeService $focus, int $httpStatus): void
    {
        $status = $body['status'] ?? null;
        if (!$status) {
            // Erro de validação da própria Focus (ex.: token inválido, campo faltando).
            $status = $httpStatus >= 400 ? 'erro_autorizacao' : $invoice->status;
        }

        $update = ['status' => $status, 'last_response' => $body];
        if ($status === 'autorizado') {
            $update += [
                'number' => $body['numero'] ?? $invoice->number,
                'verification_code' => $body['codigo_verificacao'] ?? $invoice->verification_code,
                'pdf_url' => $focus->absolute($body['url_danfse'] ?? $body['url'] ?? null),
                'xml_url' => $focus->absolute($body['caminho_xml_nota_fiscal'] ?? null),
                'issued_at' => isset($body['data_emissao']) ? \Carbon\Carbon::parse($body['data_emissao']) : now(),
                'message' => null,
            ];
        } elseif ($status === 'erro_autorizacao') {
            $update['message'] = $this->errorMessage($body) ?: ($httpStatus === 401 || $httpStatus === 403 ? 'A Focus NFe recusou o token. Confira em Configurações > APIs.' : 'A prefeitura recusou a nota.');
        } elseif ($status === 'processando_autorizacao') {
            $update['message'] = 'A prefeitura está processando a nota. Clique em "Atualizar" em alguns segundos.';
        } elseif ($status === 'cancelado') {
            $update['cancelled_at'] = $invoice->cancelled_at ?: now();
        }
        $invoice->update($update);
    }

    private function errorMessage(array $body): ?string
    {
        if (!empty($body['erros']) && is_array($body['erros'])) {
            return collect($body['erros'])->map(fn ($e) => trim(($e['codigo'] ?? '') . ' ' . ($e['mensagem'] ?? '')))->filter()->implode(' | ');
        }
        return $body['mensagem'] ?? null;
    }

    /** Monta a NFS-e no formato da Focus NFe. */
    private function payload(Company $company, Client $client, float $amount, string $description): array
    {
        $address = $client->address;
        $taker = [
            'razao_social' => Str::limit($client->name, 115, ''),
        ];
        if ($client->cnpj) {
            $taker['cnpj'] = preg_replace('/\D/', '', $client->cnpj);
        } else {
            $taker['cpf'] = preg_replace('/\D/', '', (string) $client->cpf);
        }
        if ($client->email) {
            $taker['email'] = $client->email;
        }
        if ($client->phone_1) {
            $taker['telefone'] = preg_replace('/\D/', '', $client->phone_1);
        }
        if ($address && $address->city) {
            $taker['endereco'] = array_filter([
                'logradouro' => $address->street,
                'numero' => $address->number ?: 'S/N',
                'complemento' => $address->complement,
                'bairro' => $address->district,
                'codigo_municipio' => (string) $address->city->iso,
                'uf' => $address->city->state?->letter,
                'cep' => preg_replace('/\D/', '', (string) $address->zip_code),
            ], fn ($v) => $v !== null && $v !== '');
        }

        $service = [
            'aliquota' => (float) ($company->nf_iss_rate ?? 0),
            'discriminacao' => $description,
            'iss_retido' => false,
            'item_lista_servico' => preg_replace('/\D/', '', (string) $company->nf_service_code),
            'valor_servicos' => round($amount, 2),
        ];
        if ($company->nf_municipal_tax_code) {
            $service['codigo_tributario_municipio'] = $company->nf_municipal_tax_code;
        }

        return [
            'data_emissao' => now()->toIso8601String(),
            'natureza_operacao' => 1,
            'optante_simples_nacional' => in_array($company->tax_regime, ['simples_nacional', 'mei'], true),
            'prestador' => [
                'cnpj' => preg_replace('/\D/', '', (string) $company->cnpj),
                'inscricao_municipal' => preg_replace('/\D/', '', (string) $company->municipal_registration),
                'codigo_municipio' => (string) $company->city?->iso,
            ],
            'tomador' => $taker,
            'servico' => $service,
        ];
    }

    private function companyMissing(?Company $company): array
    {
        if (!$company) {
            return ['empresa emissora (Cadastros > Empresas)'];
        }
        $missing = [];
        if (!$company->cnpj) $missing[] = 'CNPJ da empresa';
        if (!$company->municipal_registration) $missing[] = 'inscrição municipal da empresa';
        if (!$company->city_id) $missing[] = 'cidade da empresa';
        if (!$company->nf_service_code) $missing[] = 'código do serviço da empresa';
        if ($company->nf_iss_rate === null) $missing[] = 'alíquota do ISS da empresa';
        return $missing;
    }

    private function clientMissing(?Client $client): array
    {
        if (!$client) {
            return ['cliente da nota fiscal no evento'];
        }
        $missing = [];
        if (!$client->cnpj && !$client->cpf) $missing[] = 'CPF ou CNPJ do cliente';
        return $missing;
    }

    private function defaultDescription(Event $event, ?Company $company): string
    {
        $base = $company?->nf_default_description ?: 'Serviço de bar para evento';
        $date = optional($event->date)->format('d/m/Y');
        return trim($base . ' - ' . $event->name . ($date ? ' em ' . $date : '') . ' (evento nº ' . $event->id . ').');
    }
}
