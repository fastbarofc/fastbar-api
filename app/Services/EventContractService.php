<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\Event;
use App\Models\EventKit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Monta o contrato de prestação de serviços de coquetelaria (Fast Bar).
 *
 * O texto fixo do contrato mora no blade; daqui saem apenas as partes que
 * mudam de evento para evento. As cláusulas vêm do modelo de contrato, com
 * o que foi ajustado no próprio evento por cima, e os `{{campos}}` delas são
 * trocados pelos dados do evento. Os itens do cardápio são os drinks
 * cadastrados no evento. A modalidade não existe no cadastro: é digitada na
 * hora de imprimir e fica gravada em `contract_print_data`, para voltar
 * preenchida na próxima impressão.
 */
class EventContractService
{
    public const DEFAULT_GLASS_CUP_HOURS = 4;

    /**
     * Status a partir dos quais o contrato pode ser impresso. "Contrato" é a
     * fase em que o cliente assina, e o evento só sai dela para Planejamento
     * com o contrato assinado: bloquear a impressão aqui travaria o fluxo.
     */
    public const PRINTABLE_STATUSES = ['Contrato', 'Fechado', 'Planejamento', 'Concluído'];

    /**
     * Logo, marca d'água e filete extraídos da arte do contrato Fast Bar.
     *
     * Vão em base64 porque o wkhtmltopdf 0.12.6+ bloqueia file:// por padrão
     * e a flag --enable-local-file-access não existe na 0.12.4; data URI
     * funciona nas duas versões, inclusive dentro do cabeçalho e do rodapé.
     */
    public static function brandAssets(): array
    {
        return [
            'logo' => self::brandAsset('fastbar-logo.png'),
            'watermark' => self::brandAsset('fastbar-watermark-tile.png'),
            'divider' => self::brandAsset('fastbar-divider.png'),
        ];
    }

    private static function brandAsset(string $file): string
    {
        return 'data:image/png;base64,' . base64_encode(file_get_contents(public_path("img/logos/fastbar/{$file}")));
    }

    /** Campos que o usuário preenche na impressão, já com o que foi salvo antes. */
    public static function printableDefaults(Event $event): array
    {
        $saved = $event->contract_print_data ?? [];

        return [
            'menu_modality' => $saved['menu_modality'] ?? '',
        ];
    }

    /** Drinks de todas as pastas do evento, na ordem em que aparecem na árvore. */
    public static function drinks(Event $event): array
    {
        return $event->eventKits()
            ->whereNull('event_kit_id')
            ->with(['products', 'eventKits'])
            ->get()
            ->sortBy('ordination')
            ->flatMap(fn (EventKit $kit) => self::kitDrinks($kit))
            ->unique()
            ->values()
            ->all();
    }

    private static function kitDrinks(EventKit $kit): Collection
    {
        return $kit->products
            ->sortBy('pivot.ordination')
            ->pluck('name')
            ->merge($kit->eventKits->sortBy('ordination')->flatMap(fn (EventKit $child) => self::kitDrinks($child)));
    }

    public static function isPrintable(Event $event): bool
    {
        return in_array($event->eventStatus?->name, self::PRINTABLE_STATUSES, true);
    }

    public static function isCompany(?Client $client): bool
    {
        return $client?->type === Client::TYPE_LEGAL || !empty($client?->cnpj);
    }

    /**
     * Rótulos do que falta no cadastro para o contrato sair completo: os dados
     * do contratante do preâmbulo fixo e os campos que o texto das cláusulas
     * deste evento usa. Campo removido do texto deixa de ser exigido.
     */
    public static function missingFields(Event $event): array
    {
        self::loadRelations($event);

        $client = $event->client;
        $isCompany = self::isCompany($client);

        $contractant = array_keys(array_filter([
            'Cliente' => !$client,
            'Nome do cliente' => $client && !$client->name,
            ($isCompany ? 'CNPJ do cliente' : 'CPF do cliente') => $client && !($isCompany ? $client->cnpj : $client->cpf),
            'Endereço do cliente' => $client && !self::formatAddress($client->address),
            'Nome do representante legal' => $isCompany && !$client->representative_name,
            'CPF do representante legal' => $isCompany && !$client->representative_cpf,
            'Endereço do representante legal' => $isCompany && !self::formatAddress($client->representativeAddress),
        ]));

        $text = implode(' ', ContractTemplate::current()->sectionsWith($event->contract_sections));
        $values = self::variables($event);

        $clauses = collect(ContractTemplate::VARIABLES)
            ->filter(fn (string $label, string $key) => $values[$key] === null && str_contains($text, '{{' . $key . '}}'))
            ->values()
            ->all();

        return array_merge($contractant, $clauses);
    }

    public static function build(Event $event, array $printable): array
    {
        self::loadRelations($event);

        $replacements = collect(self::variables($event))
            ->mapWithKeys(fn (?string $value, string $key) => ['{{' . $key . '}}' => str_replace("\n", '<br>', e($value ?? ''))])
            ->all();

        return [
            'code' => $event->id,
            'issued_at' => Carbon::now(),
            'contractant' => self::contractant($event->client),
            'menu' => [
                'modality' => $printable['menu_modality'] ?? '',
                'items' => self::drinks($event),
            ],
            'sections' => array_map(
                fn (string $html) => strtr($html, $replacements),
                ContractTemplate::current()->sectionsWith($event->contract_sections)
            ),
        ];
    }

    private static function loadRelations(Event $event): void
    {
        $event->loadMissing([
            'client.address.state',
            'client.address.city.state',
            'client.representativeAddress.state',
            'client.representativeAddress.city.state',
            'eventPlace',
            'eventBatches',
            'paymentPlan.paymentMethod',
            'paymentPlan.installments',
        ]);
    }

    /** Valor de cada campo das cláusulas; null é o que não está preenchido no cadastro. */
    private static function variables(Event $event): array
    {
        $plan = $event->paymentPlan;
        $duration = (int) $event->duration;
        $guests = self::totalGuests($event);
        $cups = self::cups($event);

        return [
            'event_name' => $event->name ?: null,
            'event_place' => self::place($event),
            'event_date' => $event->date ? Carbon::parse($event->date)->format('d/m/Y') : null,
            'event_time' => $event->time ? Carbon::parse($event->time)->format('H:i') . 'h' : null,
            'event_duration' => $duration > 0 ? (string) $duration : null,
            'event_guests' => $guests > 0 ? (string) $guests : null,
            'bar_counter_type' => Event::BAR_COUNTER_TYPES[$event->bar_counter_type] ?? null,
            'unit_price' => self::money($event->guest_unit_value),
            'total_price' => self::money($event->total_value),
            'excess_unit_price' => self::money($event->excess_unit_value ?: $event->guest_unit_value),
            'payment_method' => $plan?->paymentMethod?->name ?: null,
            'installments' => $plan ? self::installmentsText(self::installments($event), (float) $event->total_value) : null,
            'guests_per_bartender' => $event->guests_per_bartender ? (string) $event->guests_per_bartender : null,
            'cup_type' => Event::CUP_TYPES[$event->cup_type] ?? null,
            'glass_cup_hours' => (string) $cups['glass_hours'],
            'acrylic_cup_hours' => $duration > 0 ? (string) $cups['acrylic_hours'] : null,
        ];
    }

    private static function installmentsText(array $installments, float $total): ?string
    {
        if (!$installments) {
            return $total > 0 ? 'Pagamento à vista no valor de R$ ' . self::money($total) . '.' : null;
        }

        return collect($installments)
            ->map(fn (array $row) => $row['label']
                . ($row['share'] ? ' – ' . $row['share'] : '')
                . ($row['due_date'] ? ' até ' . $row['due_date'] : '')
                . ' (R$ ' . self::money($row['amount']) . ')')
            ->implode("\n");
    }

    private static function money($value): ?string
    {
        return (float) $value > 0 ? number_format((float) $value, 2, ',', '.') : null;
    }

    private static function contractant(?Client $client): array
    {
        $isCompany = self::isCompany($client);

        return [
            'is_company' => $isCompany,
            'name' => $client?->name ?: 'Não informado',
            'document_label' => $isCompany ? 'CNPJ' : 'CPF',
            'document' => $isCompany
                ? (maskCnpj($client?->cnpj) ?: 'Não informado')
                : (maskCpf($client?->cpf) ?: 'Não informado'),
            'rg' => $client?->rg ?: null,
            'email' => $client?->email ?: null,
            'address' => self::formatAddress($client?->address) ?? 'Não informado',
            'representative' => $isCompany ? [
                'name' => $client?->representative_name,
                'cpf' => maskCpf($client?->representative_cpf),
                'address' => self::formatAddress($client?->representativeAddress) ?? 'Não informado',
            ] : null,
        ];
    }

    private static function place(Event $event): ?string
    {
        $place = $event->eventPlace;

        if (!$place?->name) {
            return null;
        }

        if ($place->description && $place->description !== $place->name) {
            return $place->name . ' (' . $place->description . ')';
        }

        return $place->name;
    }

    private static function totalGuests(Event $event): int
    {
        $total = (int) $event->event_guests
            + (int) $event->parthenon_courtesies
            + (int) $event->partner_courtesies;

        foreach ($event->eventBatches as $batch) {
            $total += (int) $batch->guests;
        }

        return $total;
    }

    /**
     * A entrada não vira parcela do plano (vira recebível), então entra aqui
     * na mão para o contrato listar tudo que o cliente vai pagar.
     */
    private static function installments(Event $event): array
    {
        $plan = $event->paymentPlan;

        if (!$plan) {
            return [];
        }

        $total = (float) ($plan->total_amount ?: $event->total_value);
        $rows = [];

        if ((float) $plan->down_payment > 0) {
            $rows[] = self::installmentRow('Entrada', (float) $plan->down_payment, $plan->down_payment_due_date, $total);
        }

        foreach ($plan->installments->sortBy('installment_number') as $installment) {
            $rows[] = self::installmentRow(
                $installment->installment_number . 'ª parcela',
                (float) $installment->amount,
                $installment->due_date,
                $total
            );
        }

        return $rows;
    }

    private static function installmentRow(string $label, float $amount, $dueDate, float $total): array
    {
        return [
            'label' => $label,
            'share' => $total > 0 ? round($amount / $total * 100) . '%' : null,
            'due_date' => $dueDate ? Carbon::parse($dueDate)->format('d/m/Y') : null,
            'amount' => $amount,
        ];
    }

    private static function cups(Event $event): array
    {
        $duration = (int) $event->duration;
        $glass = $event->glass_cup_hours === null
            ? self::DEFAULT_GLASS_CUP_HOURS
            : (int) $event->glass_cup_hours;

        if ($duration > 0) {
            $glass = min($glass, $duration);
        }

        return [
            'glass_hours' => $glass,
            'acrylic_hours' => max($duration - $glass, 0),
        ];
    }

    private static function formatAddress(?Address $address): ?string
    {
        if (!$address) {
            return null;
        }

        $street = trim(($address->street ?? '') . ($address->number ? ', n° ' . $address->number : ''));
        $city = array_filter([$address->city?->name, $address->state?->letter ?? $address->city?->state?->letter]);

        $parts = array_filter([
            $street ?: null,
            $address->complement ?: null,
            $address->district ? 'Bairro ' . $address->district : null,
            $city ? implode('/', $city) : null,
            $address->zip_code ? 'CEP ' . $address->zip_code : null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
