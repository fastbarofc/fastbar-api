<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório de Eventos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; }
        .header { text-align: right; margin-bottom: 20px; }
        .filters { margin-bottom: 20px; }
        .filters h3 { font-size: 14px; margin-bottom: 5px; }
        .summary { margin-bottom: 30px; }
        .summary h3 { font-size: 14px; margin-bottom: 10px; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table th, .summary-table td { padding: 5px; border: 1px solid #ddd; }
        .summary-table th { background-color: #f2f2f2; }
        
        .events-header {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            padding: 5px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .event-box {
          border: 1px solid #ccc;
          margin-bottom: 15px;
          padding: 10px;
          display: flex;
          flex-direction: column;
        }
        .event-header {
            background-color: #f9f9f9;
            display: flex;
            justify-content: space-between;
            padding: 5px;
            border-bottom: 1px solid #ccc;
        }
        .event-data {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 10px;
            padding: 5px;
        }
        .event-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px;
            margin-bottom: 5px;
        }
        .event-item {
            padding: 5px;
            background-color: #f9f9f9;
            border-radius: 4px;
            word-wrap: break-word;
        }
        .event-financial {
            background-color: #f5f5f5;
            border-top: 1px solid #eee;
            padding: 10px;
        }
        .financial-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
        }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; }

        .total-azul {
            color: #007bff;
            font-weight: bold;
        }

        .total-vermelho {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RELATÓRIO DE EVENTOS EMITIDO EM {{ now()->format('d/m/Y') }} ÀS {{ now()->format('H:i') }}hs</h1>
    </div>
    
    <div class="filters">
        <h3>FILTROS</h3>
        <table class="summary-table">
            <tr>
            <td><strong>Tipo:</strong> 
                {{ isset($filters['type_id']) ? $events->firstWhere('event_type_id', $filters['type_id'])?->eventType?->name : 'Todos' }}
            </td>
            <td><strong>Local:</strong> 
                {{ isset($filters['local_id']) ? $events->firstWhere('event_place_id', $filters['local_id'])?->eventPlace?->name : 'Todos' }}
            </td>
            </tr>
            <tr>
                <td><strong>Responsável:</strong> 
                {{ isset($filters['responsible_id']) ? $events->firstWhere('collaborator_id', $filters['responsible_id'])?->collaborator?->name : 'Todos' }}
            </td>
                <td><strong>Parceiro:</strong> {{ isset($filters['partner_id']) ? $events->firstWhere('partner_id', $filters['partner_id'])?->partner?->name : 'Todos' }}</td>
            </tr>
            <tr>
                <td><strong>Data inicial:</strong> {{ isset($filters['eventStartDate']) ? date('d/m/Y', strtotime($filters['eventStartDate'])) : '' }}</td>
                <td><strong>Data final:</strong> {{ isset($filters['eventEndDate']) ? date('d/m/Y', strtotime($filters['eventEndDate'])) : '' }}</td>
            </tr>
            <tr>
                <td><strong>Data fechamento inicial:</strong> {{ isset($filters['registeredEventsStartDate']) ? date('d/m/Y', strtotime($filters['registeredEventsStartDate'])) : '' }}</td>
                <td><strong>Data fechamento final:</strong> {{ isset($filters['registeredEventsEndDate']) ? date('d/m/Y', strtotime($filters['registeredEventsEndDate'])) : '' }}</td>
            </tr>
        </table>
    </div>
    
    <div class="summary">
        <h3>RESUMO FINANCEIRO</h3>
        <table class="summary-table">
            <tr>
                <td><strong>Número de eventos:</strong> {{ $summary['total_events'] }}</td>
                <td><strong>Total dos buffets:</strong> <span class="total-azul">R$ {{ number_format($summary['total_buffets'], 2, ',', '.') }}</span></td>
            </tr>
            <tr>
                <td><strong>Total das locações:</strong> <span class="total-azul">R$ {{ number_format($summary['total_leases'], 2, ',', '.') }}</span></td>
                <td><strong>Total dos seguros:</strong> <span class="total-azul">R$ {{ number_format($summary['total_insurances'], 2, ',', '.') }}</span></td>
            </tr>
            <tr>
                <td><strong>Total dos eventos:</strong> <span class="total-azul">R$ {{ number_format($summary['total_financial'], 2, ',', '.') }}</span></td>
                <td><strong>Total de descontos:</strong> <span class="total-vermelho">R$ {{ number_format($summary['total_discounts'], 2, ',', '.') }}</span></td>
            </tr>
            <tr>
                <td><strong>Total de acréscimos:</strong> <span class="total-azul">R$ {{ number_format($summary['total_additional'], 2, ',', '.') }}</span></td>
                <td><strong>Total de comissões:</strong> <span class="total-vermelho">R$ {{ number_format($summary['total_commissions'], 2, ',', '.') }}</span></td>
            </tr>
            <tr>
                <td><strong>Total de quebras:</strong> <span class="total-vermelho">R$ {{ number_format($summary['total_breaks'], 2, ',', '.') }}</span></td>
                <td><strong>Total de custos:</strong> <span class="total-vermelho">R$ {{ number_format($summary['total_costs'], 2, ',', '.') }}</span></td>
            </tr>
            <tr>
                <td><strong>Total de pós:</strong> <span class="total-azul">R$ {{ number_format($summary['total_post'], 2, ',', '.') }}</span></td>
                <td><strong>Total de pagamentos:</strong> <span class="total-azul">R$ {{ number_format($summary['total_payments'], 2, ',', '.') }}</span></td>
            </tr>
        </table>
    </div>
    
    <div class="events-header">EVENTOS</div>
    
    @foreach ($events as $event)
    <div class="event-box">
        <div class="event-header">
            <div><strong>Evento:</strong> ({{ $event->id }}) {{ $event->name }}</div>
            <div><strong>Data:</strong> {{ $event->date ? date('d/m/Y', strtotime($event->date)) : '-' }}</div>
        </div>
        
        <div class="event-data">
            <div class="event-grid">
            <div class="event-item">
                <strong>Local:</strong> 
                {{ $event->eventPlace->name ?? '-' }}
                @if($event->eventPlace?->address?->city?->name)
                    ({{ $event->eventPlace->address->city->name }})
                @endif
            </div>
                <div class="event-item"><strong>Cliente:</strong> ({{ $event->client->id }}) {{ $event->client->name ?? '-' }}</div>
                <div class="event-item"><strong>Responsável:</strong> {{ $event->collaborator->name ?? '-' }}</div>
                <div class="event-item"><strong>Status:</strong> {{ $event->eventStatus->name ?? '-' }}</div>
            </div>
            
            <div class="event-grid">
                <div class="event-item"><strong>Convidados:</strong> {{ $event->event_guests }}</div>
                <div class="event-item"><strong>Valor Unit.:</strong> R$ {{ number_format($event->guest_unit_value ?? 0, 2, ',', '.') }}</div>
                <div class="event-item"><strong>Acréscimo:</strong> R$ {{ number_format($event->extraSales->sum(fn($item) => $item->quantity * $item->unit_value), 2, ',', '.') }}</div>
                <div class="event-item"><strong>Pago:</strong> R$ {{ number_format($event->paid_value ?? 0, 2, ',', '.') }}</div>
            </div>
            
            <div class="event-grid">
                <div class="event-item"><strong>Cortesias:</strong> {{ ($event->parthenon_courtesies ?? 0) + ($event->partner_courtesies ?? 0) }}</div>
                <div class="event-item"><strong>Seguro Unit.:</strong> R$ {{ number_format($event->unit_insurance ?? 0, 2, ',', '.') }}</div>
                <div class="event-item"><strong>Comissões:</strong> R$ {{ number_format($event->commissions_value ?? 0, 2, ',', '.') }}</div>
                <div class="event-item"><strong>Buffet:</strong> R$ {{ number_format($event->buffet_value ?? 0, 2, ',', '.') }}</div>
            </div>
            
            <div class="event-grid">
                <div class="event-item"><strong>Total Conv.:</strong> {{ $event->total_guests ?? $event->event_guests }}</div>
                <div class="event-item"><strong>Desc. Unit.:</strong> R$ {{ number_format($event->unit_discount ?? 0, 2, ',', '.') }}</div>
                <div class="event-item"><strong>Quebras:</strong> R$ {{ number_format($event->breakItems->sum(fn($item) => $item->pivot->quantity * $item->pivot->value), 2, ',', '.') }}</div>
                <div class="event-item"><strong>Locação:</strong> R$ {{ number_format($event->lease ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>
        
        <div class="event-financial">
            <div class="financial-grid">
                <div class="event-item"><strong>Conv. Pres.:</strong> {{ $event->present_guests ?? 0 }}</div>
                <div class="event-item"><strong>Desconto:</strong> R$ {{ number_format($event->discount ?? 0, 2, ',', '.') }}</div>
                <div class="event-item"><strong>Custos:</strong> 
                    R$ {{ 
                        number_format(
                            ($event->commissions_value ?? 0) + 
                            ($event->breakItems->sum(fn($item) => $item->pivot->quantity * $item->pivot->value)) + 
                            ($event->discount ?? 0), 2, ',', '.')
                    }}
                </div>
                <div class="event-item"><strong>Total Pós:</strong> R$ {{ number_format($event->pending_value ?? 0, 2, ',', '.') }}</div>
                <div class="event-item"><strong>A Pagar:</strong> R$ {{ number_format(($event->total_value ?? 0) - ($event->paid_value ?? 0), 2, ',', '.') }}</div>
                <div class="event-item"><strong>Total</strong> R$ {{ number_format($event->total_value ?? 0, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>
    @endforeach
    
    <div class="footer">
        <p>Relatório gerado em {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>