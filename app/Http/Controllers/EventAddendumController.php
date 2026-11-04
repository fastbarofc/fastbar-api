<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventAddendumRequest;
use App\Http\Resources\EventAddendumResource;
use App\Models\Event;
use App\Models\EventAddendum;
use App\Models\EventStatus;
use App\Services\EventAddendumService;
use App\Services\EventContractService;
use App\Services\FileService;

class EventAddendumController extends Controller
{
    public function __construct(private EventAddendumService $addendums)
    {
    }

    public function index(Event $event)
    {
        $event->load(['eventStatus', 'addendums.user.collaborator']);

        return response()->json([
            'data' => EventAddendumResource::collection($event->addendums->sortByDesc('number')->values()),
            'context' => [
                'can_create' => EventAddendumService::canCreate($event),
                'statuses' => EventStatus::ALLOWS_ADDENDUM,
                'guest_unit_value' => (float) $event->guest_unit_value,
                'event_guests' => (int) $event->event_guests,
                'total_value' => (float) $event->total_value,
                'menu_modality' => $event->contract_print_data['menu_modality'] ?? null,
            ],
        ]);
    }

    public function store(StoreEventAddendumRequest $request, Event $event)
    {
        $event->load('eventStatus');

        if (!EventAddendumService::canCreate($event)) {
            return response()->json([
                'message' => 'O aditivo só pode ser feito com o evento em ' . implode(', ', EventStatus::ALLOWS_ADDENDUM) . '.',
            ], 422);
        }

        $addendum = $this->addendums->create($event, $request->validated());
        $addendum->load('user.collaborator');

        return response()->json(new EventAddendumResource($addendum), 201);
    }

    public function pdf(Event $event, EventAddendum $addendum)
    {
        $document = $addendum->document;
        $code = $addendum->code;
        $brand = EventContractService::brandAssets();

        $viewContent = view('/pdfs/event-addendum-pdf', compact('document', 'code', 'brand'));
        $header = view('/pdfs/partials/fastbar-header', compact('brand'))->render();
        $footer = view('/pdfs/partials/fastbar-footer', compact('brand'))->render();

        try {
            // Mesmas margens do contrato, que a marca d'água do blade depende delas.
            $pdfUrl = FileService::generatePdf($viewContent, EventAddendumService::fileName($addendum), $footer, $header, [
                'margin-top' => '20mm',
                'margin-bottom' => '24mm',
                'margin-left' => '21mm',
                'margin-right' => '21mm',
                'header-spacing' => 3,
                'footer-spacing' => 3,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }

        return response()->json(['pdf_url' => $pdfUrl]);
    }
}
