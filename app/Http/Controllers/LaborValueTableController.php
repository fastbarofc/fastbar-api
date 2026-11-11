<?php

namespace App\Http\Controllers;

use App\Models\LaborValueTable;
use Illuminate\Http\Request;

/**
 * Planejamento > Tabela de valores (André, 2026-11-04): a planilha de valores dos freelancers na tela.
 * Toda alteração precisa ser salva e cada Salvar vira uma versão com data; dá para pesquisar e abrir as antigas.
 */
class LaborValueTableController extends Controller
{
    public function current()
    {
        $table = LaborValueTable::with('user:id,name')->orderByDesc('id')->first();
        return response()->json(['data' => $table ? $this->present($table) : null]);
    }

    public function index(Request $request)
    {
        $query = LaborValueTable::with('user:id,name')->orderByDesc('id');
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        $currentId = LaborValueTable::max('id');
        $search = mb_strtolower(trim((string) $request->input('search', '')));
        $rows = $query->get()->filter(function (LaborValueTable $t) use ($search) {
            if ($search === '') {
                return true;
            }
            if ((string) $t->id === $search) {
                return true;
            }
            $hay = mb_strtolower(($t->note ?? '') . ' ' . ($t->user?->name ?? '') . ' ' . json_encode($t->changes ?? [], JSON_UNESCAPED_UNICODE));
            return str_contains($hay, $search);
        })->values()->map(fn (LaborValueTable $t) => [
            'id' => $t->id,
            'created_at' => $t->created_at?->toIso8601String(),
            'user_name' => $t->user?->name,
            'note' => $t->note,
            'changes' => $t->changes ?? [],
            'current' => $t->id === $currentId,
        ]);
        return response()->json(['data' => $rows]);
    }

    public function show(LaborValueTable $laborValueTable)
    {
        $laborValueTable->load('user:id,name');
        return response()->json(['data' => $this->present($laborValueTable)]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
            'data.columns' => 'required|array|min:1',
            'data.sections' => 'present|array',
            'note' => 'nullable|string|max:500',
        ]);
        // validate() só devolve as chaves validadas; a tabela inteira vem de input().
        $payload = ['data' => $request->input('data'), 'note' => $request->input('note')];
        $current = LaborValueTable::current();
        $changes = LaborValueTable::diff($current?->data, $payload['data']);
        if ($current && empty($changes)) {
            return response()->json(['message' => 'Nada mudou desde a última versão salva.'], 422);
        }
        $table = LaborValueTable::create([
            'data' => $payload['data'],
            'changes' => $changes,
            'note' => $payload['note'] ?? null,
            'user_id' => $request->user()?->id,
        ]);
        $table->load('user:id,name');
        return response()->json(['data' => $this->present($table)], 201);
    }

    /** Volta a usar uma tabela antiga: grava uma versão nova igual a ela (a história não se perde). */
    public function restore(Request $request, LaborValueTable $laborValueTable)
    {
        $current = LaborValueTable::current();
        $changes = LaborValueTable::diff($current?->data, $laborValueTable->data);
        if (empty($changes)) {
            return response()->json(['message' => 'Esta já é a tabela em uso.'], 422);
        }
        $table = LaborValueTable::create([
            'data' => $laborValueTable->data,
            'changes' => $changes,
            'note' => 'Voltou para a tabela de ' . $laborValueTable->created_at?->format('d/m/Y H:i'),
            'user_id' => $request->user()?->id,
        ]);
        $table->load('user:id,name');
        return response()->json(['data' => $this->present($table)], 201);
    }

    /** PDF da tabela (paisagem), para imprimir ou guardar. */
    public function pdf(Request $request, LaborValueTable $laborValueTable)
    {
        $data = $laborValueTable->data ?? [];
        $columns = $data['columns'] ?? [];
        $six = count(array_filter($columns, fn ($c) => ($c['hours'] ?? 6) != 8));
        $viewContent = view('pdfs.labor-value-table-pdf', [
            'data' => $data,
            'columns' => $columns,
            'sixCount' => $six,
            'eightCount' => count($columns) - $six,
            'version' => $this->present($laborValueTable),
            'date' => now()->format('d/m/Y H:i'),
        ]);
        try {
            $name = 'tabela-de-valores-' . $laborValueTable->id . '-' . time();
            $pdfUrl = \App\Services\FileService::generatePdf($viewContent, $name, null, null, ['orientation' => 'Landscape', 'page-size' => 'A4', 'margin-top' => 8, 'margin-bottom' => 8, 'margin-left' => 8, 'margin-right' => 8]);
            // ?file=1: devolve o próprio PDF (o botão Imprimir abre a impressão em cima dele, mesmo layout).
            if ($request->boolean('file')) {
                return response()->file(storage_path('app/public/pdfs/' . $name . '.pdf'), ['Content-Type' => 'application/pdf']);
            }
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    private function present(LaborValueTable $t): array
    {
        return [
            'id' => $t->id,
            'data' => $t->data,
            'changes' => $t->changes ?? [],
            'note' => $t->note,
            'user_name' => $t->user?->name,
            'created_at' => $t->created_at?->toIso8601String(),
            'current' => $t->id === LaborValueTable::max('id'),
        ];
    }
}
