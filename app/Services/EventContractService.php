<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventKit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Monta o contrato de prestação de serviços de coquetelaria (Fast Bar).
 *
 * O texto fixo do contrato mora no blade; daqui saem apenas as partes que
 * mudam de evento para evento. Os itens do cardápio são os drinks cadastrados
 * no evento. Modalidade e pontos de bar não existem no cadastro: são digitados
 * na hora de imprimir e ficam gravados em `contract_print_data`, para voltarem
 * preenchidos na próxima impressão.
 */
class EventContractService
{
    public const DEFAULT_BAR_POINTS_TEXT = 'Balcões necessários para a montagem de 1 (um) ponto de bar do tipo "lateral".';

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
            'bar_points_text' => $saved['bar_points_text'] ?? self::DEFAULT_BAR_POINTS_TEXT,
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

    /** Sem representante o preâmbulo de um contratante PJ fica sem quem assina. */
    public static function missingRepresentative(?Client $client): bool
    {
        return self::isCompany($client)
            && (empty($client->representative_name) || empty($client->representative_cpf));
    }

    public static function build(Event $event, array $printable): array
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

        return [
            'code' => $event->id,
            'issued_at' => Carbon::now(),
            'contractant' => self::contractant($event->client),
            'event' => self::eventSummary($event),
            'price' => self::price($event),
            'payment' => self::payment($event),
            'cups' => self::cups($event),
            'menu' => [
                'modality' => $printable['menu_modality'] ?? '',
                'items' => self::drinks($event),
            ],
            'bar_points_text' => $printable['bar_points_text'] ?? self::DEFAULT_BAR_POINTS_TEXT,
        ];
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
            'address' => self::formatAddress($client?->address),
            'representative' => $isCompany ? [
                'name' => $client?->representative_name,
                'cpf' => maskCpf($client?->representative_cpf),
                'address' => self::formatAddress($client?->representativeAddress),
            ] : null,
        ];
    }

    private static function eventSummary(Event $event): array
    {
        return [
            'name' => $event->name ?: 'Não informado',
            'place' => self::place($event),
            'date' => $event->date ? Carbon::parse($event->date)->format('d/m/Y') : 'Não informada',
            'time' => $event->time ? Carbon::parse($event->time)->format('H:i') . 'h' : 'A definir',
            'duration' => (int) $event->duration,
            'guests' => self::totalGuests($event),
        ];
    }

    private static function place(Event $event): string
    {
        $place = $event->eventPlace;

        if (!$place) {
            return 'Não informado';
        }

        if ($place->description && $place->description !== $place->name) {
            return $place->name . ' (' . $place->description . ')';
        }

        return $place->name ?: 'Não informado';
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

    private static function price(Event $event): array
    {
        return [
            'unit' => (float) $event->guest_unit_value,
            'total' => (float) $event->total_value,
            'excess_unit' => (float) ($event->excess_unit_value ?: $event->guest_unit_value),
        ];
    }

    private static function payment(Event $event): array
    {
        $plan = $event->paymentPlan;

        return [
            'method' => $plan?->paymentMethod?->name ?: 'transferência bancária',
            'installments' => self::installments($event),
        ];
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

    private static function formatAddress(?Address $address): string
    {
        if (!$address) {
            return 'Não informado';
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

        return $parts ? implode(', ', $parts) : 'Não informado';
    }
}
