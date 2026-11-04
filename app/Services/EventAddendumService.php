<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAddendum;
use App\Models\EventHistory;
use App\Models\EventStatus;
use App\Models\Kit;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * O aditivo aplica as alterações no próprio evento e guarda o documento já
 * montado, para o PDF sair igual ao que foi assinado mesmo que o evento mude
 * depois.
 */
class EventAddendumService
{
    private const VALUE_LABELS = [
        'guest_unit_value' => 'Valor Unitário por Convidado',
        'event_guests' => 'Quantidade de Convidados',
        'addendum_value' => 'Valor de Aditivos',
        'buffet_value' => 'Valor do Buffet',
        'sub_total' => 'Subtotal',
        'total_value' => 'Valor Total',
        'menu_modality' => 'Modalidade do Cardápio',
    ];

    private const MONEY_FIELDS = ['guest_unit_value', 'addendum_value', 'buffet_value', 'sub_total', 'total_value'];

    public function __construct(
        private EventDrinkMenuService $drinkMenu,
        private EventDrinkMenuHistoryService $drinkMenuHistory,
    ) {
    }

    public static function canCreate(Event $event): bool
    {
        return in_array($event->eventStatus?->name, EventStatus::ALLOWS_ADDENDUM, true);
    }

    public static function fileName(EventAddendum $addendum): string
    {
        return Str::slug("{$addendum->code} aditivo {$addendum->event?->client?->name} fastbar");
    }

    public function create(Event $event, array $data): EventAddendum
    {
        return DB::transaction(function () use ($event, $data) {
            $event = Event::query()->lockForUpdate()->with('eventBatches')->findOrFail($event->id);
            $types = array_values(array_intersect(array_keys(EventAddendum::TYPES), $data['types'] ?? []));

            $before = $this->values($event);
            $totalsBefore = $event->calculatedTotals();
            $items = [];
            $menu = null;
            $menuChanges = [];
            $unchanged = [];

            if (in_array('unit_price', $types, true)) {
                if (abs((float) $data['unit_price'] - $before['guest_unit_value']) < 0.005) {
                    $unchanged['unit_price'] = 'O novo valor por convidado é igual ao atual (R$ ' . self::money($before['guest_unit_value']) . ').';
                }

                $event->guest_unit_value = $data['unit_price'];
                $items[] = $this->item('Alteração do valor por convidado de R$ ' . self::money($before['guest_unit_value']) . ' para R$ ' . self::money($data['unit_price']) . ';');
            }

            if (in_array('guests', $types, true)) {
                $added = (int) $data['guests_added'];
                $event->event_guests = $before['event_guests'] + $added;
                $items[] = $this->item("Acréscimo de {$added} " . ($added === 1 ? 'convidado' : 'convidados') . ", passando de {$before['event_guests']} para {$event->event_guests} convidados;");
            }

            if (in_array('menu', $types, true)) {
                $menuBefore = $this->drinkMenuHistory->snapshot($event->id);
                $menu = $this->changeMenu($event, $data);
                $menuChanges = $this->drinkMenuHistory->changes($menuBefore, $this->drinkMenuHistory->snapshot($event->id));

                if (!$menuChanges && $menu['modality'] === trim((string) $before['menu_modality'])) {
                    $unchanged['menu'] = 'O cardápio não mudou: tire ou adicione drinks, ou altere a modalidade.';
                }

                $items[] = $this->item("Fica definido o cardápio a ser executado no evento: {$menu['modality']};", $menu['drinks']);
            }

            if (in_array('value', $types, true)) {
                $event->addendum_value = (float) $event->addendum_value + (float) $data['value_added'];
                $description = trim($data['value_description'] ?? '');
                $items[] = $this->item('Acréscimo de R$ ' . self::money($data['value_added']) . ' ao valor do contrato' . ($description !== '' ? ", referente a {$description}" : '') . ';');
            }

            if (in_array('service', $types, true)) {
                foreach ($data['services'] as $service) {
                    $value = (float) ($service['value'] ?? 0);
                    $event->addendum_value = (float) $event->addendum_value + $value;
                    $items[] = $this->item('Inclusão do serviço adicional: ' . trim($service['description']) . ($value > 0 ? ' (R$ ' . self::money($value) . ')' : '') . ';');
                }
            }

            foreach (preg_split('/\R/', $data['notes'] ?? '') as $line) {
                $line = rtrim(trim($line), ';.');
                if ($line !== '') {
                    $items[] = $this->item("{$line};");
                }
            }

            if ($unchanged) {
                throw ValidationException::withMessages($unchanged);
            }

            $this->applyTotals($event, $totalsBefore);
            $event->save();

            $after = $this->values($event);
            $paymentTerms = trim($data['payment_terms'] ?? '') ?: null;
            $details = EventContractService::eventDetails($event);

            $addendum = EventAddendum::create([
                'event_id' => $event->id,
                'number' => (int) EventAddendum::where('event_id', $event->id)->max('number') + 1,
                'types' => $types,
                'previous_total' => $before['total_value'],
                'new_total' => $after['total_value'],
                'user_id' => Auth::id(),
                'document' => [
                    'issued_at' => Carbon::now()->toDateString(),
                    'contract_code' => (string) $event->id,
                    'contractant' => EventContractService::contractant($event->client, EventContractService::representativeIds($event)),
                    'event' => $details,
                    'items' => $items,
                    'menu' => $menu,
                    'value_clause' => $this->valueClause($before['total_value'], $after['total_value']),
                    'payment_terms' => $paymentTerms,
                ],
            ]);

            EventHistory::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'event_addendum_id' => $addendum->id,
                'action' => 'updated',
                'changes' => [
                    ...$this->valueChanges($before, $after),
                    ...$menuChanges,
                ],
            ]);

            return $addendum;
        });
    }

    public static function money($value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }

    private function changeMenu(Event $event, array $data): array
    {
        $this->drinkMenu->removeDrinks($event->id, $data['menu_remove'] ?? []);

        foreach ($data['menu_add'] ?? [] as $selection) {
            $kit = Kit::findOrFail($selection['kit_id']);

            if (empty($selection['product_id'])) {
                $this->drinkMenu->addFolder($event->id, $kit);
                continue;
            }

            $this->drinkMenu->addDrink($event->id, $kit, Product::findOrFail($selection['product_id']));
        }

        $drinks = collect($this->drinkMenu->drinks($event->id))->pluck('name')->unique()->values()->all();

        if (!$drinks) {
            throw ValidationException::withMessages([
                'menu_add' => 'O cardápio do aditivo precisa ter pelo menos um drink.',
            ]);
        }

        $modality = trim($data['menu_modality']);
        $event->contract_print_data = array_merge($event->contract_print_data ?? [], ['menu_modality' => $modality]);

        return ['modality' => $modality, 'drinks' => $drinks];
    }

    /**
     * Soma ao que está gravado a diferença que o aditivo causa na conta da aba
     * Valores, para não refazer o total de eventos antigos que não batem com ela.
     */
    private function applyTotals(Event $event, array $totalsBefore): void
    {
        foreach ($event->calculatedTotals() as $field => $value) {
            $difference = $value - $totalsBefore[$field];

            if (abs($difference) >= 0.005) {
                $base = $event->getOriginal($field) ?? $totalsBefore[$field];
                $event->{$field} = round((float) $base + $difference, 2);
            }
        }
    }

    private function values(Event $event): array
    {
        return [
            'guest_unit_value' => (float) $event->guest_unit_value,
            'event_guests' => (int) $event->event_guests,
            'addendum_value' => (float) $event->addendum_value,
            'buffet_value' => (float) $event->buffet_value,
            'sub_total' => (float) $event->sub_total,
            'total_value' => (float) $event->total_value,
            'menu_modality' => $event->contract_print_data['menu_modality'] ?? null,
        ];
    }

    private function valueChanges(array $before, array $after): array
    {
        $changes = [];

        foreach (self::VALUE_LABELS as $field => $label) {
            if ($before[$field] === $after[$field]) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'old' => $this->display($field, $before[$field]),
                'new' => $this->display($field, $after[$field]),
            ];
        }

        return $changes;
    }

    private function display(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '(vazio)';
        }

        return in_array($field, self::MONEY_FIELDS, true) ? 'R$ ' . self::money($value) : (string) $value;
    }

    private function valueClause(float $before, float $after): string
    {
        $difference = round($after - $before, 2);

        if (abs($difference) < 0.005) {
            return 'Pela alteração ora pactuada, não haverá alteração no valor do contrato.';
        }

        return 'Pela alteração ora pactuada, o valor total do contrato passa de R$ ' . self::money($before)
            . ' para R$ ' . self::money($after) . ', '
            . ($difference > 0 ? 'um acréscimo' : 'uma redução') . ' de R$ ' . self::money(abs($difference)) . '.';
    }

    private function item(string $text, array $children = []): array
    {
        return ['text' => $text, 'children' => $children];
    }
}
