<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function generatePdfReport(Request $request)
    {
        try {
            $query = Event::query();
            
            $this->applyFilters($query, $request);
            
            $events = $query
            ->with([
                'eventPlace',
                'client',
                'collaborator',
                'eventStatus',
                'extraSales',
                'receivables',
                'breakItems',
                'materials',
                'officeEvents',
            ])
            ->get();

            $events = $events->map(function ($event) {
                $event->paid_value = $event->receivables
                    ->filter(fn($r) => $r->status === 'paid' || !is_null($r->paid_at))
                    ->sum('amount');

                    $event->pending_value = $event->total_value - $event->paid_value - $event->total_breaks;

                return $event;
            });

            
            
            $totalDiscounts = $events->sum('discount');
            $totalCommissions = $events->sum(function ($event) {
                return $event->commission ?? 0;
            });
            $totalAdditional = $events->sum(function ($event) {
                return $event->extraSales->sum(function ($item) {
                    return $item->quantity * $item->unit_value;
                });
            });
            $totalBreaks = $events->sum(function ($event) {
                return $event->breakItems->sum(function ($item) {
                    return $item->pivot->quantity * $item->pivot->value;
                });
            });
            $totalPayments = $events->sum(function ($event) {
                return $event->receivables->where('status', 'paid')->sum('amount') ?? 0;
            });
            $totalPost = $events->sum('pending_value');
            $totalCosts = $totalCommissions + $totalBreaks + $totalDiscounts;

            
            
            $data = [
                'summary' => [
                    'total_events'     => $events->count(),
                    'total_buffets' => $events->sum('buffet_value'),
                    'total_leases' => $events->sum('lease'),
                    'total_insurances' => $events->sum('unit_insurance'),
                    'total_financial'  => $events->sum('total_value'),
                    'total_guests'     => $events->sum(function ($event) {
                        return $event->event_guests + $event->parthenon_courtesies + $event->partner_courtesies;
                    }),
                    'total_discounts'  => $totalDiscounts,
                    'total_additional' => $totalAdditional,
                    'total_breaks'     => $totalBreaks,
                    'total_payments'   => $totalPayments,
                    'total_post'       => $totalPost,
                    'total_commissions' => $totalCommissions,
                    'total_costs'      => $totalCosts,
                ],
                'events'  => $events,
                'filters' => $request->all(),
            ];
            
            $onlySummary = $request->filled('only_summary') && $request->input('only_summary') === true;
            
            $onlyDebtors = $request->filled('debtors') && $request->input('debtors') === true;
            if ($onlyDebtors) {
                $data['events'] = $events->filter(function ($event) {
                    $totalPaid = $event->receivables->where('type', 'payment')->sum('value') ?? 0;
                    return $event->total_value > $totalPaid;
                });
                $data['summary']['total_events'] = $data['events']->count();
            }
            
            $fileName = 'relatorio-eventos-' . now()->format('YmdHis');
            $viewName = $onlySummary ? 'pdfs.event-report-summary-pdf' : 'pdfs.event-report-pdf';
            $viewContent = view($viewName, $data);
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('eventStartDate') && $request->filled('eventEndDate')) {
            $query->whereBetween('date', [
                $request->input('eventStartDate'),
                $request->input('eventEndDate')
            ]);
        } else if ($request->filled('eventStartDate')) {
            $query->whereDate('date', '>=', $request->input('eventStartDate'));
        } else if ($request->filled('eventEndDate')) {
            $query->whereDate('date', '<=', $request->input('eventEndDate'));
        }
        
        if ($request->filled('registeredEventsStartDate') && $request->filled('registeredEventsEndDate')) {
            $query->whereBetween('closed_at', [
                $request->input('registeredEventsStartDate'),
                $request->input('registeredEventsEndDate')
            ]);
        } else if ($request->filled('registeredEventsStartDate')) {
            $query->whereDate('closed_at', '>=', $request->input('registeredEventsStartDate'));
        } else if ($request->filled('registeredEventsEndDate')) {
            $query->whereDate('closed_at', '<=', $request->input('registeredEventsEndDate'));
        }
        
        if ($request->filled('status_id')) {
            $query->where('event_status_id', $request->input('status_id'));
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->input('partner_id'));
        }
        
        if ($request->filled('type_id')) {
            $query->where('event_type_id', $request->input('type_id'));
        }
        
        if ($request->filled('local_id')) {
            $query->where('event_place_id', $request->input('local_id'));
        }
        
        if ($request->filled('responsible_id')) {
            $query->where('collaborator_id', $request->input('responsible_id'));
        }
        
        if ($request->filled('item_id')) {
            $query->whereHas('officeEvents', function ($q) use ($request) {
                $q->where('office_event_id', $request->input('item_id'));
            });
        }
        
        if ($request->filled('materials_id')) {
            $query->whereHas('materials', function ($q) use ($request) {
                $q->where('material_id', $request->input('materials_id'));
            });
        }
        
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        if ($request->filled('state_id') && is_numeric($request->input('state_id'))) {
            $query->whereHas('eventPlace.address', function($q) use ($request) {
                $q->where('state_id', $request->input('state_id'));
            });
        }
        
        if ($request->filled('city_id') && is_numeric($request->input('city_id'))) {
            $query->whereHas('eventPlace.address', function($q) use ($request) {
                $q->where('city_id', $request->input('city_id'));
            });
        }
        
        if ($request->filled('minGuests')) {
            $query->where('event_guests', '>=', $request->input('minGuests'));
        }
        
        if ($request->filled('maxGuests')) {
            $query->where('event_guests', '<=', $request->input('maxGuests'));
        }
        
    }
}