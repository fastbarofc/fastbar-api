<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REPLACEMENTS = [
        'clauses_before_menu' => [
            'Número de convidados: {{event_guests}}</p>' => 'Número de convidados: {{event_guests}}<br>Tipo de balcão: {{bar_counter_type}}</p>',
        ],
        'clauses_after_menu' => [
            '<p>22.1 - A equipe de bartenders, necessários para a prestação dos serviços ora contratados.</p>'
                => '<p>22.1 - A equipe de bartenders, necessários para a prestação dos serviços ora contratados, mais especificamente 1 (um) barman a cada {{guests_per_bartender}} convidados.</p>',
            '<p>22.2 – Balcões necessários para a montagem de 1 (um) ponto de bar do tipo "lateral".</p>'
                => '<p>22.2 – Copos de {{cup_type}} necessários para a realização de {{glass_cup_hours}} horas iniciais de evento e copos de acrílico para as {{acrylic_cup_hours}} horas finais de evento.</p>',
            '<p>22.3 – Copos de Vidro necessários para a realização de {{glass_cup_hours}} horas iniciais de evento e copos de acrílico para as {{acrylic_cup_hours}} horas finais de evento.</p>'
                => '<p>22.3 – Balcões necessários para a montagem de 1 (um) ponto de bar do tipo "{{bar_counter_type}}".</p>',
        ],
    ];

    public function up(): void
    {
        $this->rewrite(self::REPLACEMENTS);
    }

    public function down(): void
    {
        $this->rewrite(array_map('array_flip', self::REPLACEMENTS));
    }

    private function rewrite(array $replacements): void
    {
        DB::table('contract_templates')->whereNotNull('sections')->get()->each(function ($template) use ($replacements) {
            $sections = json_decode($template->sections, true);

            foreach ($replacements as $key => $pairs) {
                // Trecho já editado à mão fica como está: trocar só parte dos itens da 22 duplicaria ou perderia um deles.
                if (isset($sections[$key]) && collect(array_keys($pairs))->every(fn ($old) => str_contains($sections[$key], $old))) {
                    $sections[$key] = strtr($sections[$key], $pairs);
                }
            }

            DB::table('contract_templates')->where('id', $template->id)->update(['sections' => json_encode($sections)]);
        });
    }
};
