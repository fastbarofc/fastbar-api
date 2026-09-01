<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

/**
 * Monta os dados da proposta comercial (Fast Bar).
 *
 * A proposta nao e persistida: o que sai daqui e apenas o preenchimento
 * inicial do formulario. O usuario ajusta o que faltar e o payload que ele
 * devolver e o que vai para o PDF.
 */
class EventProposalService
{
    public const DEFAULT_VALIDITY_DAYS = 30;

    public static function defaults(Event $event): array
    {
        $event->loadMissing(['client', 'clients', 'eventPlace', 'eventBatches']);

        $guests = self::totalGuests($event);
        $duration = self::duration($event);

        return [
            'proposal_number' => (string) $event->id,
            'validity_days' => self::DEFAULT_VALIDITY_DAYS,
            'client_name' => self::clientName($event),
            'event_name' => $event->name ?? '',
            'location' => $event->eventPlace?->description ?? $event->eventPlace?->name ?? '',
            'date' => $event->date ? Carbon::parse($event->date)->format('d/m/y') : '',
            'duration' => $duration ? str_pad((string) $duration, 2, '0', STR_PAD_LEFT) . 'hs' : '',
            'guests' => $guests ? (string) $guests : '',

            'budget_details' => self::budgetDetails($duration, $guests),
            'observations' => [
                'Hora adicional: +10% do valor do evento.',
                'Consulte a disponibilidade de outros rótulos de destilados.',
            ],

            'drinks_bar_items' => [
                'FAST CONCEPT – ATÉ 10 DRINKS',
                'Drinks com Smirnoff, Gilbey’s e Red Label.',
                'Bar 360º ou Lateral (Balcões inclusos)',
            ],
            'drinks_bar_price' => 28,
            'optional_drinks_note' => 'Bebidas opcionais (Aperol, Rum, Saquê, Cachaça, Espumante) incluso de acordo com o cardápio escolhido.',

            'spirits_bar_items' => ['SMIRNOFF', 'GILBEY’S', 'RED LABEL', 'MONSTER'],
            'spirits_bar_price' => 24,

            'additional_items' => [
                'ABSOLUT +R$ 4,00',
                'GORDON’S +R$ 2,00',
                'TANQUERAY +R$ 4,00',
                'OLD PAR +R$ 5,00',
                'REDBULL +R$ 6,00',
                'Bar de Gin tônica, Red Label e água de Coco (sem energético) – R$ 18,00',
            ],
        ];
    }

    /**
     * Campos da capa que o PDF nao pode sair sem. O front usa essa lista para
     * destacar o que o evento nao tinha e precisa ser digitado na hora.
     */
    public static function requiredFields(): array
    {
        return ['proposal_number', 'client_name', 'event_name', 'location', 'date', 'duration', 'guests'];
    }

    private static function clientName(Event $event): string
    {
        if ($event->client?->name) {
            return $event->client->name;
        }

        return $event->clients->pluck('name')->filter()->implode(' e ');
    }

    private static function totalGuests(Event $event): int
    {
        $total = (int) $event->event_guests + (int) $event->parthenon_courtesies + (int) $event->partner_courtesies;

        foreach ($event->eventBatches as $eventBatch) {
            $total += (int) $eventBatch->guests;
        }

        return $total;
    }

    private static function duration(Event $event): ?int
    {
        $duration = (int) $event->duration + (int) $event->duration_after;

        return $duration > 0 ? $duration : null;
    }

    private static function budgetDetails(?int $duration, int $guests): array
    {
        $hours = $duration ? str_pad((string) $duration, 2, '0', STR_PAD_LEFT) : '__';

        return [
            "Orçamento para evento com duração de {$hours} horas.",
            $guests ? "Preço praticado para o mínimo de {$guests} pessoas." : 'Preço praticado para o mínimo de ___ pessoas.',
            "Copos de acrílico por {$hours} horas de evento.",
            'Cardápio personalizável.',
            'Gelo Incluso.',
            'Equipe e material necessário inclusos.',
            'O serviço engloba todos os itens do cardápio durante o tempo de duração do evento.',
        ];
    }
}
