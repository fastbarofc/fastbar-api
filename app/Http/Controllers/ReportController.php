<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\CompetingCompany;
use App\Models\Event;
use App\Models\EventCapture;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\HowDidYouHear;
use App\Models\LostReason;
use App\Models\ParthenonBankAccount;
use App\Models\Partner;
use App\Models\PaymentMethod;
use App\Models\State;
use App\Models\User;
use App\Services\FileService;
use App\Services\Reports\EventsReport;
use App\Services\Reports\GroupedReport;
use App\Services\Reports\LossesReport;
use App\Services\Reports\PaymentsReport;
use App\Services\Reports\Report;
use App\Services\Reports\RevenueReport;
use App\Services\Reports\SalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public const REPORTS = [
        'events' => EventsReport::class,
        'revenue' => RevenueReport::class,
        'payments' => PaymentsReport::class,
        'sales' => SalesReport::class,
        'losses' => LossesReport::class,
    ];

    private const EXPORT_PERMISSION = 'relatorios-exportar';

    private const EXPORT_LIMIT = 10000;

    public function show(ReportRequest $request, string $report)
    {
        $report = $this->resolve($request, $report);
        $rows = $report->paginate($request->integer('per_page', 25), $request->integer('page', 1));

        return response()->json([
            'data' => $rows->items(),
            'summary' => $report->summary(),
            'pagination' => [
                'page' => $rows->currentPage(),
                'total_pages' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function export(ReportRequest $request, string $report)
    {
        $this->authorizeReport($request->user(), self::EXPORT_PERMISSION);
        $report = $this->resolve($request, $report);

        if ($report->count() > self::EXPORT_LIMIT) {
            return response()->json([
                'message' => 'O relatório passa de ' . number_format(self::EXPORT_LIMIT, 0, ',', '.') . ' linhas. Refine os filtros para exportar.',
            ], 422);
        }

        return response()->json([
            'data' => $report->all(),
            'summary' => $report->summary(),
        ]);
    }

    public function filterOptions()
    {
        $statusGroups = [
            'negotiating' => EventStatus::NEGOTIATING,
            'confirmed' => EventStatus::CONFIRMED,
            'lost' => EventStatus::LOST,
        ];

        $placeAddresses = Address::where('addressable_type', EventPlace::class);

        return response()->json([
            'statuses' => EventStatus::orderBy('id')->get(['id', 'name'])->map(fn (EventStatus $status) => [
                'id' => $status->id,
                'name' => $status->name,
                'group' => collect($statusGroups)->search(fn (array $names) => in_array($status->name, $names, true)) ?: null,
            ]),
            'event_types' => EventType::orderBy('name')->get(['id', 'name']),
            'event_places' => EventPlace::with('address.city', 'address.state')->orderBy('name')->get()->map(fn (EventPlace $place) => [
                'id' => $place->id,
                'name' => $place->name,
                'city' => $place->address?->city?->name,
                'state' => $place->address?->state?->letter,
            ]),
            'states' => State::whereIn('id', (clone $placeAddresses)->select('state_id'))->orderBy('name')->get(['id', 'name', 'letter']),
            'cities' => City::with('state')->whereIn('id', (clone $placeAddresses)->select('city_id'))->orderBy('name')->get()->map(fn (City $city) => [
                'id' => $city->id,
                'name' => $city->name,
                'state_id' => $city->state_id,
                'state' => $city->state?->letter,
            ]),
            'collaborators' => Collaborator::whereIn('id', Event::select('collaborator_id'))->orderBy('name')->get(['id', 'name']),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'event_captures' => EventCapture::orderBy('name')->get(['id', 'name']),
            'how_did_you_hears' => HowDidYouHear::orderBy('name')->get(['id', 'name']),
            'lost_reasons' => LostReason::orderBy('name')->get(['id', 'name']),
            'competitors' => CompetingCompany::orderBy('name')->get(['id', 'name']),
            'payment_methods' => PaymentMethod::orderBy('name')->get(['id', 'name']),
            'bank_accounts' => ParthenonBankAccount::orderBy('bank_name')->get()->map(fn (ParthenonBankAccount $account) => [
                'id' => $account->id,
                'name' => trim("{$account->bank_name} - {$account->account_number}", ' -'),
            ]),
        ]);
    }

    public function clients(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'string'],
        ]);

        $query = Client::query()->select(['id', 'name'])->orderBy('name')->limit(30);

        if ($request->filled('ids')) {
            $query->whereIn('id', array_map('intval', explode(',', $request->input('ids'))));
        } else {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        return response()->json($query->get());
    }

    private function resolve(ReportRequest $request, string $key): Report
    {
        $class = self::REPORTS[$key];
        $this->authorizeReport($request->user(), $class::PERMISSION);

        $report = new $class($request->filters());

        if ($report instanceof GroupedReport) {
            $request->validate(
                ['group_by' => ['nullable', Rule::in($report->groupKeys())]],
                ['group_by.in' => 'Este relatório não pode ser agrupado por esse campo.']
            );
        }

        return $report;
    }

    private function authorizeReport(User $user, string $permission): void
    {
        abort_unless(
            $user->isAdmin() || $user->getPermissionsViaRoles()->contains('slug', $permission),
            403,
            'Você não tem permissão para acessar este relatório.'
        );
    }

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