<?php

namespace App\Services;

use App\Models\AppSetting;

/**
 * Regras do Planejamento (Configurações > Planejamento > Regras do planejamento, André 2026-11-05).
 * As que têm "toggle" podem ser ligadas/desligadas; as outras são só informativas (valem sempre).
 * Liga/desliga fica em app_settings com a chave "planning_rule.<key>" ("1" ou "0").
 */
class PlanningRules
{
    public const RULES = [
        // Status do evento
        ['key' => 'concluido_exige_materiais', 'group' => 'Status do evento', 'toggle' => true, 'default' => true,
            'label' => 'Para passar para Concluído, a aba Materiais precisa estar preenchida',
            'detail' => 'Todos os materiais do evento precisam ter resposta na aba Materiais.'],
        ['key' => 'planejamento_exige_contrato', 'group' => 'Status do evento', 'toggle' => false,
            'label' => 'Planejamento e Concluído exigem o contrato assinado',
            'detail' => 'Faz parte da lista de campos obrigatórios por status do evento.'],
        ['key' => 'concluido_sem_convocacao', 'group' => 'Status do evento', 'toggle' => false,
            'label' => 'A convocação não precisa estar feita para o evento ficar Concluído', 'detail' => null],
        ['key' => 'trava_fechado_planejamento', 'group' => 'Status do evento', 'toggle' => false,
            'label' => 'Fechado e Planejamento: Informações Básicas e Itens do Buffet só mudam por aditivo',
            'detail' => 'Continuam liberados: Vendas Extras, Materiais, Colaboradores, Histórico, Representantes, Lotes, Impressos, e nos itens Mesa temática, Opcional, Checklist e Observação.'],
        ['key' => 'trava_concluido', 'group' => 'Status do evento', 'toggle' => false,
            'label' => 'Concluído trava o evento inteiro', 'detail' => null],
        // Aba Planejamento do evento
        ['key' => 'aditivo_30_dias', 'group' => 'Aba Planejamento do evento', 'toggle' => false,
            'label' => 'Aditivo a 30 dias ou menos do evento: o Planejamento lança e avisa os setores e terceirizados',
            'detail' => 'Com mais de 30 dias, quem faz é o consultor.'],
        ['key' => 'divergencia_avisa_consultor', 'group' => 'Aba Planejamento do evento', 'toggle' => false,
            'label' => 'Divergência registrada avisa o responsável do evento e o gerente comercial', 'detail' => 'Gerente comercial = usuário com o perfil Gerente Comercial ou colaborador com o cargo Gerente Comercial.'],
        ['key' => 'custo_aprovado_conta_pagar', 'group' => 'Aba Planejamento do evento', 'toggle' => false,
            'label' => 'Custo aprovado vira conta a pagar no Financeiro', 'detail' => 'Desaprovar apaga a conta, se ainda não foi paga.'],
        // Mão de Obra
        ['key' => 'mao_de_obra_status', 'group' => 'Mão de Obra', 'toggle' => false,
            'label' => 'Só entram eventos com status Planejamento ou Concluído', 'detail' => null],
        ['key' => 'mao_de_obra_concluido_so_leitura', 'group' => 'Mão de Obra', 'toggle' => true, 'default' => false,
            'label' => 'Evento Concluído: Quantificação, Convocação, Relatório Financeiro e Planejamento Financeiro ficam só para consulta',
            'detail' => 'Desligada, dá para convocar e acertar valores depois do Concluído.'],
        ['key' => 'convocacao_um_setor', 'group' => 'Mão de Obra', 'toggle' => true, 'default' => true,
            'label' => 'A mesma pessoa não pode ser convocada em dois setores do mesmo evento', 'detail' => null],
        ['key' => 'relatorio_linhas_quantificacao', 'group' => 'Mão de Obra', 'toggle' => false,
            'label' => 'Relatório Financeiro sai com as linhas da Quantificação; setor com zero não sai',
            'detail' => '"+ Linha" acrescenta linhas em branco por setor.'],
        ['key' => 'diaria_tabela_valores', 'group' => 'Mão de Obra', 'toggle' => false,
            'label' => 'Diária = parte Valor base da Tabela de valores, na coluna da cidade do local do evento',
            'detail' => 'Cozinha usa COZINHA, Limpeza usa LIMPEZA, os outros setores usam GARÇOM. Cidade fora da tabela usa a coluna Local. Cidade que só aparece nas colunas de 8 horas usa 8 horas.'],
        ['key' => 'kpi_freelancer', 'group' => 'Mão de Obra', 'toggle' => false,
            'label' => 'KPI Freelancer = total da mão de obra ÷ receita do evento',
            'detail' => 'Receita = valor total do evento; sem ele, convidados × valor unitário.'],
        // Relatório Coordenação
        ['key' => 'coordenacao_prazo', 'group' => 'Relatório Coordenação', 'toggle' => false,
            'label' => 'Prazo do Relatório Pós Evento de Coordenação: segunda-feira 10h depois do evento', 'detail' => null],
    ];

    public static function enabled(string $key): bool
    {
        $rule = collect(self::RULES)->firstWhere('key', $key);
        $default = $rule['default'] ?? true;
        $value = AppSetting::get('planning_rule.' . $key);
        return $value === null ? $default : $value === '1';
    }

    public static function all(): array
    {
        return collect(self::RULES)->map(fn ($r) => $r + ['enabled' => $r['toggle'] ? self::enabled($r['key']) : true])->all();
    }

    public static function set(string $key, bool $on): void
    {
        AppSetting::put('planning_rule.' . $key, $on ? '1' : '0');
    }
}
