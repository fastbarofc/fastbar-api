<?php

namespace App\Http\Controllers;

use App\Http\Requests\BiddingRequest;
use App\Http\Resources\BiddingCommentResource;
use App\Http\Resources\BiddingResource;
use App\Models\Bidding;
use App\Models\BiddingComment;
use App\Models\AppSetting;
use App\Services\BiddingAiService;
use App\Services\FileService;
use Illuminate\Http\Request;

class BiddingController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 30);

        $query = Bidding::query()->with('noticeFile')->withCount('comments');

        // Pesquisa única da tela: órgão, número do edital, observação ou ID.
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('agency', 'like', "%$search%")
                    ->orWhere('notice_number', 'like', "%$search%")
                    ->orWhere('observation', 'like', "%$search%")
                    ->orWhere('summary', 'like', "%$search%");
                if (ctype_digit($search)) {
                    $q->orWhere('biddings.id', (int) $search);
                }
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortableField = $request->input('order_by', 'opening_date');
        if (!in_array($sortableField, ['id', 'agency', 'notice_number', 'opening_date', 'status'])) {
            $sortableField = 'opening_date';
        }
        $direction = $request->input('direction') == 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortableField, $direction);
        if ($sortableField === 'opening_date') {
            $query->orderBy('opening_time', $direction);
        }
        $query->orderBy('id', 'desc');

        $biddings = $query->paginate($perPage);

        // Contadores do topo da tela (todas as licitações, sem filtro).
        $totals = array_fill_keys(Bidding::STATUSES, 0);
        foreach (Bidding::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') as $status => $total) {
            $totals[$status] = (int) $total;
        }
        $totals['todas'] = array_sum($totals);

        return response()->json([
            'data' => BiddingResource::collection($biddings),
            'pagination' => [
                'page' => $biddings->currentPage(),
                'total_pages' => $biddings->lastPage(),
                'per_page' => $biddings->perPage(),
                'total' => $biddings->total(),
            ],
            'totals' => $totals,
        ], 200);
    }

    public function store(BiddingRequest $request)
    {
        $bidding = Bidding::create($request->validated());

        return response()->json(new BiddingResource($bidding->load('comments.user', 'noticeFile')), 201);
    }

    public function show(Bidding $bidding)
    {
        return response()->json(new BiddingResource($bidding->load('comments.user', 'noticeFile')), 200);
    }

    public function update(BiddingRequest $request, Bidding $bidding)
    {
        $bidding->update($request->validated());

        return response()->json(new BiddingResource($bidding->load('comments.user', 'noticeFile')), 200);
    }

    public function destroy(Bidding $bidding)
    {
        $bidding->delete();

        return response()->json([], 204);
    }

    /**
     * Anexa (ou troca) o edital. "data" é o arquivo em base64 (PDF ou Word, até 20 MB).
     */
    public function storeNotice(Request $request, Bidding $bidding)
    {
        $request->validate(
            [
                'data' => ['required', 'string', 'regex:/^data:application\/(pdf|msword|vnd\.openxmlformats-officedocument\.wordprocessingml\.document);base64,/'],
                'name' => 'nullable|string|max:255',
            ],
            ['data.required' => 'Envie o edital.', 'data.regex' => 'O edital deve ser um arquivo PDF ou Word.']
        );

        // base64 ocupa ~4/3 do tamanho original
        if (strlen($request->input('data')) > 20 * 1024 * 1024 * 4 / 3 + 200) {
            return response()->json(['message' => 'O edital pode ter no máximo 20 MB.'], 422);
        }

        $old = $bidding->noticeFile;
        $file = FileService::saveBase64File('biddings', $request->input('data'), $request->input('name'));
        $bidding->update(['notice_file_id' => $file->id, 'notice_file_name' => $request->input('name')]);
        if ($old) {
            FileService::delete($old);
        }

        return response()->json(new BiddingResource($bidding->fresh()->load('comments.user', 'noticeFile')), 200);
    }

    public function destroyNotice(Bidding $bidding)
    {
        $old = $bidding->noticeFile;
        $bidding->update(['notice_file_id' => null, 'notice_file_name' => null]);
        if ($old) {
            FileService::delete($old);
        }

        return response()->json(new BiddingResource($bidding->fresh()->load('comments.user', 'noticeFile')), 200);
    }

    /** IA: lista os lotes do edital anexado, para a equipe escolher de quais quer participar. */
    public function aiLots(Bidding $bidding, BiddingAiService $ai)
    {
        set_time_limit(300);

        try {
            return response()->json(['lots' => $ai->listLots($bidding)], 200);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'A IA está indisponível agora. Tente de novo em alguns minutos.'], 502);
        }
    }

    /** IA: escreve o resumo do edital no modelo, só com os lotes escolhidos. Não salva: a tela coloca no campo e a pessoa salva. */

    /**
     * Gera o PDF do resumo (usa o texto enviado, para incluir edições ainda não salvas).
     */
    public function summaryPdf(Request $request, Bidding $bidding)
    {
        $request->validate(['summary' => 'nullable|string|max:200000']);
        $text = $request->filled('summary') ? $request->input('summary') : $bidding->summary;
        if (trim((string) $text) === '') {
            return response()->json(['message' => 'A licitação ainda não tem resumo.'], 422);
        }

        $title = 'Resumo - ' . $bidding->agency . ($bidding->notice_number ? ' - Edital ' . $bidding->notice_number : '');
        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>' . e($title) . '</title>'
            . '<style>body{font-family:Calibri,Arial,sans-serif;font-size:12pt;color:#111;line-height:1.35;margin:0}'
            . 'p{margin:0 0 0.45em}ul{margin:0 0 0.6em 1.2em;padding:0}li{margin-bottom:0.15em}strong{font-weight:700}</style>'
            . '</head><body>' . BiddingAiService::summaryHtml($text) . '</body></html>';

        $root = base_path();
        $arch = FileService::getArchitecture();
        $pdf = new \mikehaertl\wkhtmlto\Pdf([
            'binary' => "{$root}/vendor/bin/wkhtmltopdf-{$arch}",
            'ignoreWarnings' => true,
            'encoding' => 'UTF-8',
            'margin-top' => 15, 'margin-bottom' => 15, 'margin-left' => 18, 'margin-right' => 18,
        ]);
        $pdf->addPage($html);
        $content = $pdf->toString();
        if ($content === false) {
            return response()->json(['message' => 'Não foi possível gerar o PDF: ' . $pdf->getError()], 500);
        }

        $fileName = preg_replace('/[^A-Za-z0-9_\-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $title)) . '.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function aiSummary(Request $request, Bidding $bidding, BiddingAiService $ai)
    {
        $request->validate(['lots' => 'nullable|array', 'lots.*' => 'string|max:255']);
        set_time_limit(300);

        try {
            return response()->json(['summary' => $ai->summarize($bidding, $request->input('lots', []))], 200);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'A IA está indisponível agora. Tente de novo em alguns minutos.'], 502);
        }
    }

    /** Configuração da IA: diz se a chave está informada (nunca devolve a chave) e o modelo de resumo. */
    public function aiSettings()
    {
        return response()->json([
            'configured' => BiddingAiService::isConfigured(),
            'template' => BiddingAiService::template(),
            'default_template' => BiddingAiService::DEFAULT_TEMPLATE,
        ], 200);
    }

    public function updateAiSettings(Request $request)
    {
        $request->validate([
            'api_key' => 'nullable|string|max:500',
            'template' => 'nullable|string',
        ]);

        if ($request->filled('api_key')) {
            AppSetting::putSecret(BiddingAiService::SETTING_KEY, trim($request->input('api_key')));
        }
        if ($request->has('template')) {
            AppSetting::put(BiddingAiService::SETTING_TEMPLATE, $request->input('template') ?: null);
        }

        return $this->aiSettings();
    }

    public function storeComment(Request $request, Bidding $bidding)
    {
        $request->validate(
            ['comment' => 'required|string'],
            ['comment.required' => 'Escreva o comentário.']
        );

        $comment = $bidding->comments()->create([
            'user_id' => $request->user()?->id,
            'comment' => $request->input('comment'),
        ]);

        return response()->json(new BiddingCommentResource($comment->load('user')), 201);
    }

    public function destroyComment(Bidding $bidding, BiddingComment $comment)
    {
        if ($comment->bidding_id !== $bidding->id) {
            abort(404);
        }

        $comment->delete();

        return response()->json([], 204);
    }
}
