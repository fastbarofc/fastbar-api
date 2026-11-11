<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Etapa do planejamento do evento (do fluxograma do setor Planejamento).
 * As tarefas básicas ficam em planning_tasks (Configurações > Planejamento); STEPS é só a carga inicial.
 * Aqui fica o que foi feito em cada evento, as tarefas extras do evento (custom) e as tiradas dele (removed).
 */
class EventPlanningStep extends Model
{
    protected $fillable = ['event_id', 'step_key', 'planning_stage_id', 'label', 'who', 'custom', 'removed', 'done', 'done_by', 'done_at', 'note'];

    protected $casts = [
        'done' => 'boolean',
        'custom' => 'boolean',
        'removed' => 'boolean',
        'done_at' => 'datetime',
    ];

    /** Grupos e etapas na ordem do fluxograma. who = responsável sugerido. */
    public const STEPS = [
        ['key' => 'receber', 'group' => 'Recebimento e conferência', 'label' => 'Receber e arquivar o evento fechado (pasta pelo ID)', 'who' => 'Tamires'],
        ['key' => 'lista_30_dias', 'group' => 'Recebimento e conferência', 'label' => 'Entrar na lista de conferência do mês (30 dias antes)', 'who' => 'Tamires'],
        ['key' => 'cardapio_convidados', 'group' => 'Recebimento e conferência', 'label' => 'Cardápio e convidados definidos pelo cliente', 'who' => 'Comercial'],
        ['key' => 'conferir_contrato', 'group' => 'Recebimento e conferência', 'label' => 'Conferir contrato x sistema, item por item, com os aditivos', 'who' => 'Tamires, Bruna'],
        ['key' => 'divergencias', 'group' => 'Recebimento e conferência', 'label' => 'Divergências resolvidas (registrar a diferença, avisar o consultor e conferir de novo)', 'who' => 'Tamires, Bruna'],
        ['key' => 'escanear', 'group' => 'Recebimento e conferência', 'label' => 'Escanear e arquivar (grupo Escaneados e Drop)', 'who' => 'Tamires'],
        ['key' => 'status_planejamento', 'group' => 'Recebimento e conferência', 'label' => 'Passar o status para Planejamento (setores liberados)', 'who' => 'Bruna'],

        ['key' => 'alinhar_cliente', 'group' => 'Alinhamento e contratações', 'label' => 'Alinhar com o cliente: data, hora, convidados, perguntas e metragens', 'who' => 'Bruna'],
        ['key' => 'material', 'group' => 'Alinhamento e contratações', 'label' => 'Preencher Material, pontos e metragens no sistema', 'who' => 'Bruna'],
        ['key' => 'status_concluido', 'group' => 'Alinhamento e contratações', 'label' => 'Passar o status para Concluído (fim do alinhamento; exige a aba Materiais preenchida)', 'who' => 'Bruna'],
        ['key' => 'aditivos_30_dias', 'group' => 'Alinhamento e contratações', 'label' => 'Aditivos a 30 dias ou menos: Planejamento lança e avisa os setores e os terceirizados', 'who' => 'Bruna'],
        ['key' => 'terceirizados', 'group' => 'Alinhamento e contratações', 'label' => 'Contratar terceirizados (confirma valor e faz a OC)', 'who' => 'Bruna'],
        ['key' => 'previsao_mao_obra', 'group' => 'Alinhamento e contratações', 'label' => 'Previsão de custo da mão de obra', 'who' => 'Bruna, Amanda'],
        ['key' => 'convocar', 'group' => 'Alinhamento e contratações', 'label' => 'Convocar a mão de obra (listas fechadas, nome e CPF)', 'who' => 'Amanda'],

        ['key' => 'lanche', 'group' => 'Preparação da equipe', 'label' => 'Planilha de lanche (viagem longa ou madrugada)', 'who' => 'Maira'],
        ['key' => 'hotel', 'group' => 'Preparação da equipe', 'label' => 'Hotel perto do evento, se a equipe for pernoitar', 'who' => 'Bruna'],
        ['key' => 'malote', 'group' => 'Preparação da equipe', 'label' => 'Montar o malote (relatórios, pulseiras, cardápios, kit)', 'who' => 'Tamires'],
        ['key' => 'pasta_coordenador', 'group' => 'Preparação da equipe', 'label' => 'Pasta do coordenador no dia anterior (equipe, cardápio, contrato, checklists)', 'who' => 'Tamires'],

        ['key' => 'portaria', 'group' => 'Pós-evento', 'label' => 'Lançar a portaria (convidados presentes)', 'who' => 'Tamires'],
        ['key' => 'excedentes', 'group' => 'Pós-evento', 'label' => 'Conferir excedentes (se teve, gerar aditivo)', 'who' => 'Tamires'],
        ['key' => 'oc_complementar', 'group' => 'Pós-evento', 'label' => 'OC complementar do terceirizado (vieram mais convidados do que a OC previa)', 'who' => 'Bruna'],
        ['key' => 'arquivar', 'group' => 'Pós-evento', 'label' => 'Arquivar o evento (pasta vai para Eventos Passados)', 'who' => 'Tamires'],
        ['key' => 'relatorios', 'group' => 'Pós-evento', 'label' => 'Receber os relatórios dos coordenadores (até 10h de segunda)', 'who' => 'Coordenadores'],
        ['key' => 'ocs_coordenacao', 'group' => 'Pós-evento', 'label' => 'OCs de coordenação (só para quem entregou o relatório no prazo)', 'who' => 'Bruna'],
        ['key' => 'reuniao', 'group' => 'Pós-evento', 'label' => 'Reunião de pós-evento (presentes, relatos e problemas)', 'who' => 'Amanda, Bruna'],
    ];

    public static function keys(): array
    {
        return array_column(self::STEPS, 'key');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function doneBy()
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
