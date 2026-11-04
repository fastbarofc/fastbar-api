<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    use HasFactory;

    /** Trechos editáveis do contrato, na ordem em que aparecem no PDF: o fecho da qualificação das partes e as cláusulas antes e depois do cardápio. */
    public const SECTIONS = ['parties_closing', 'clauses_before_menu', 'clauses_after_menu'];

    /** Campos que o texto guarda como `{{chave}}` e que são trocados pelos dados do evento na emissão. */
    public const VARIABLES = [
        'event_name' => 'Nome do evento',
        'event_place' => 'Local do evento',
        'event_date' => 'Data do evento',
        'event_time' => 'Horário de início',
        'event_duration' => 'Duração (horas)',
        'event_guests' => 'Número de convidados',
        'bar_counter_type' => 'Tipo do balcão',
        'unit_price' => 'Valor por pessoa',
        'total_price' => 'Valor total',
        'excess_unit_price' => 'Valor por convidado excedente',
        'payment_method' => 'Forma de pagamento',
        'installments' => 'Entrada e parcelas',
        'guests_per_bartender' => 'Convidados por barman',
        'cup_type' => 'Tipo de copo',
        'glass_cup_hours' => 'Horas com copo de vidro',
        'acrylic_cup_hours' => 'Horas com copo de acrílico',
    ];

    protected $fillable = ['sections'];

    protected $casts = [
        'sections' => 'array',
    ];

    public static function current(): self
    {
        return self::query()->firstOrNew();
    }

    /**
     * Trecho em branco cai no nível de baixo (evento → modelo → texto
     * original): é assim que o usuário volta ao texto padrão.
     */
    public function sectionsWith(?array $overrides = []): array
    {
        return collect(self::SECTIONS)->mapWithKeys(fn (string $key) => [
            $key => self::filled($overrides[$key] ?? null)
                ?? self::filled($this->sections[$key] ?? null)
                ?? self::defaultSection($key),
        ])->all();
    }

    public static function defaultSection(string $key): string
    {
        return file_get_contents(resource_path("contract-sections/{$key}.html"));
    }

    private static function filled(?string $html): ?string
    {
        // O TinyMCE deixa `<p>&nbsp;</p>` quando o texto é apagado, e o trim não remove o nbsp.
        $text = preg_replace('/[\s\x{00A0}]+/u', '', html_entity_decode(strip_tags($html ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' ? null : $html;
    }
}
