<?php

namespace App\Http\Controllers;

use App\Services\PipedriveImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Funil > Importar: traz os negócios exportados do Pipedrive (Excel ou CSV) para o funil.
 * Só quem vê todos os leads (gerência) importa. Fluxo: prévia (reconhece colunas, conta por etapa) → importar → opcionalmente apagar importados.
 */
class LeadImportController extends Controller
{
    private const SEE_ALL_PERMISSION = 'comercial-funil-ver-todos';

    public function __construct(private PipedriveImportService $service)
    {
    }

    /** Lê o arquivo e devolve colunas reconhecidas, abas e a prévia (etapas, consultores, motivos de perda). */
    public function preview(Request $request)
    {
        $this->guard($request);
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'people' => ['nullable', 'file', 'max:20480'],
            'notes' => ['nullable', 'file', 'max:20480'],
            'sheet' => ['nullable', 'string'],
            'mapping' => ['nullable', 'string'],
            'stage_map' => ['nullable', 'string'],
            'owner_map' => ['nullable', 'string'],
        ]);

        $file = $this->fileOrFail($request);
        try {
            $data = $this->service->read($file->getRealPath(), $file->getClientOriginalName(), $request->input('sheet'));
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => ['Não consegui ler o arquivo: ' . $e->getMessage()]]);
        }

        $mapping = $this->json($request, 'mapping') ?: $this->service->detect($data['headers']);
        $stageMap = $this->json($request, 'stage_map');
        $ownerMap = $this->json($request, 'owner_map');
        if (!isset($mapping['pipedrive_id'])) {
            throw ValidationException::withMessages(['file' => ['Não achei a coluna com o ID do negócio no Pipedrive. Exporte com a coluna "ID" marcada.']]);
        }
        [$contacts, $notes] = $this->extras($request);

        return response()->json([
            'sheets' => $data['sheets'],
            'sheet' => $data['sheet'],
            'headers' => $data['headers'],
            'mapping' => $mapping,
            'preview' => $this->service->preview($data['rows'], $mapping, $stageMap, $ownerMap, $contacts, $notes),
        ]);
    }

    /** Grava os negócios do arquivo: cria os novos e atualiza os que já vieram antes (mesmo ID do Pipedrive). */
    public function store(Request $request)
    {
        $this->guard($request);
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'people' => ['nullable', 'file', 'max:20480'],
            'notes' => ['nullable', 'file', 'max:20480'],
            'sheet' => ['nullable', 'string'],
            'mapping' => ['required', 'string'],
            'stage_map' => ['nullable', 'string'],
            'owner_map' => ['nullable', 'string'],
        ]);

        $file = $this->fileOrFail($request);
        try {
            $data = $this->service->read($file->getRealPath(), $file->getClientOriginalName(), $request->input('sheet'));
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['file' => ['Não consegui ler o arquivo: ' . $e->getMessage()]]);
        }
        $mapping = $this->json($request, 'mapping');
        if (!isset($mapping['pipedrive_id'])) {
            throw ValidationException::withMessages(['mapping' => ['Escolha a coluna com o ID do negócio no Pipedrive.']]);
        }

        [$contacts, $notes] = $this->extras($request);
        $result = $this->service->import(
            $data['rows'],
            $mapping,
            $this->json($request, 'stage_map'),
            $this->json($request, 'owner_map'),
            $request->user()?->id,
            $contacts,
            $notes,
        );

        return response()->json($result);
    }

    /** Apaga tudo o que veio por importação (para limpar depois de um teste). */
    public function destroy(Request $request)
    {
        $this->guard($request);

        return response()->json(['deleted' => $this->service->destroyImported()]);
    }

    /** Arquivos opcionais: "Pessoas" (telefone/e-mail) e "Anotações" (viram registros de contato). */
    private function extras(Request $request): array
    {
        $contacts = [];
        $notes = [];
        try {
            if ($f = $request->file('people')) {
                $contacts = $this->service->readContacts($f->getRealPath(), $f->getClientOriginalName());
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['people' => ['Arquivo de pessoas: ' . $e->getMessage()]]);
        }
        try {
            if ($f = $request->file('notes')) {
                $notes = $this->service->readNotes($f->getRealPath(), $f->getClientOriginalName());
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['notes' => ['Arquivo de anotações: ' . $e->getMessage()]]);
        }

        return [$contacts, $notes];
    }

    /** Aceita só Excel (.xlsx/.xlsm) ou CSV; o tipo vem pela extensão porque o Excel muitas vezes chega como "zip". */
    private function fileOrFail(Request $request): \Illuminate\Http\UploadedFile
    {
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xlsm', 'csv', 'txt'], true)) {
            throw ValidationException::withMessages(['file' => ['Escolha um arquivo Excel (.xlsx) ou CSV exportado do Pipedrive.']]);
        }

        return $file;
    }

    private function guard(Request $request): void
    {
        $user = $request->user();
        $slugs = $user && method_exists($user, 'effectivePermissions')
            ? $user->effectivePermissions()->pluck('slug')->all()
            : ($user ? $user->getAllPermissions()->pluck('slug')->all() : []);
        if (!in_array(self::SEE_ALL_PERMISSION, $slugs, true)) {
            abort(response()->json(['message' => 'Só a gerência comercial pode importar leads.'], 403));
        }
    }

    private function json(Request $request, string $key): array
    {
        $raw = $request->input($key);
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
