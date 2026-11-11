<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Formulários do Google Forms que o André mandou em 2026-11-04 (Relatório Coordenação). */
    public function up(): void
    {
        $types = [
            ['Coordenação Geral', 'https://docs.google.com/forms/d/e/1FAIpQLScPrIuO2hw3EIrPjzRfwqC4Upa_9XqPm7m-1f-dpO0QD5kTSA/viewform'],
            ['Fiscal de Evento', 'https://docs.google.com/forms/d/e/1FAIpQLSczWMnWjn_78n7_rJHHILmIzfj7PaiEr9QBJ9W1JqwbCqEJRA/viewform'],
            ['Pós-evento dos Maîtres', 'https://docs.google.com/forms/d/e/1FAIpQLSfJ90O6t98J2fMnsEKd70E8gXTWC1wF0QmOWEg6iLN3EzSawQ/viewform'],
            ['Mesa', 'https://docs.google.com/forms/d/e/1FAIpQLSdC3Nfi0LPa7gC83-Fviz5Nr07bi1k3EnhIDKBoDijEvIyImw/viewform'],
            ['Cozinha', 'https://docs.google.com/forms/d/e/1FAIpQLScSVZgp070uFDMVhvEvohEp_qT6q5snkKjWqcITVEUVi32qhg/viewform'],
            ['Fiscal de Caminhão', 'https://docs.google.com/forms/d/e/1FAIpQLScoaX2-PtYlubMLjXvfJoxrutzI7oSwvnlwFHJ9v6NMr96UQQ/viewform'],
            ['Fábrica', 'https://docs.google.com/forms/d/e/1FAIpQLSfJ3yiuE1j1sL_cJtzAbkm8oQl0kyOMCJ6DGcJuYXlzYMX-zA/viewform'],
            ['Copa', 'https://docs.google.com/forms/d/e/1FAIpQLSfzJhXTjwLlOody2UJvH7bjCx5F12aht2gvfxWJtiUOqEsYzQ/viewform'],
            ['Degustação', 'https://docs.google.com/forms/d/1tC97RpLqYEkDK7Y_VGImJAcOq-8YeZByxcqJXK3O9AU/edit'],
            ['Pizzaria', 'https://docs.google.com/forms/d/e/1FAIpQLSc-kcayi8TcyDQSieOZWlrgSvLpqtC7RIs-bHj5lxPOyUqKUA/viewform'],
            ['Gestor de Experiência', 'https://docs.google.com/forms/d/e/1FAIpQLScyKBB7rBumVS2Ih1Bp3SmqoBujYG4nvn9HYP5W0YPLG77Rnw/viewform'],
            ['Adega', 'https://docs.google.com/forms/d/e/1FAIpQLSfX-skb0fcPwT9vcuwNDmhnx5MFhiXwTlLO3KkvimpNDY4afQ/viewform'],
            ['Nutricionista', 'https://docs.google.com/forms/d/e/1FAIpQLScSuqt5CJv9_MsCl-eGNJYJTmq4zZFxHA9Flrc_seSQ7UjNJQ/viewform'],
        ];
        $now = now();
        foreach ($types as $i => [$name, $url]) {
            if (DB::table('coordination_report_types')->where('name', $name)->exists()) {
                continue;
            }
            DB::table('coordination_report_types')->insert([
                'name' => $name,
                'form_url' => $url,
                'sort_order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
    }
};
