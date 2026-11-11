<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Appointment;
use App\Models\AppointmentRecording;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Resumo das reuniões com clientes:
 * 1) a OpenAI transforma o áudio captado em texto;
 * 2) o Claude escreve o resumo;
 * 3) o áudio é apagado (fica só o resumo no cadastro do cliente).
 */
class MeetingSummaryService
{
    public const OPENAI_KEY = 'openai_api_key';
    // Mesma chave do Claude usada em Licitações
    public const ANTHROPIC_KEY = 'anthropic_api_key';

    private const TRANSCRIBE_MODELS = ['gpt-4o-transcribe', 'whisper-1'];
    private const SUMMARY_MODEL = 'claude-opus-5-5';
    // Limite de arquivo da OpenAI para transcrição
    private const MAX_AUDIO_BYTES = 25 * 1024 * 1024;

    public static function openAiKey(): ?string
    {
        return env('OPENAI_API_KEY') ?: AppSetting::getSecret(self::OPENAI_KEY);
    }

    public static function anthropicKey(): ?string
    {
        return env('ANTHROPIC_API_KEY') ?: AppSetting::getSecret(self::ANTHROPIC_KEY);
    }

    public function summarize(Appointment $appointment): void
    {
        $recordings = $appointment->recordings()
            ->where('status', AppointmentRecording::STATUS_DONE)
            ->orderBy('id')
            ->get();

        if ($recordings->isEmpty()) {
            throw new RuntimeException('Não há áudio desta reunião para resumir.');
        }

        $appointment->update(['summary_status' => 'processing', 'summary_error' => null]);

        $transcript = $recordings->map(fn ($recording) => $this->transcribe($recording))->implode("\n\n");
        if (trim($transcript) === '') {
            throw new RuntimeException('Não deu para entender a fala da reunião (áudio vazio ou muito baixo).');
        }

        $summary = $this->writeSummary($appointment, $transcript);

        $appointment->update([
            'summary' => $summary,
            'summary_status' => 'done',
            'summary_error' => null,
        ]);

        // Resumo pronto: o áudio não fica guardado
        foreach ($recordings as $recording) {
            Storage::disk('local')->delete($recording->path);
            $recording->delete();
        }
    }

    private function transcribe(AppointmentRecording $recording): string
    {
        $key = self::openAiKey();
        if (!$key) {
            throw new RuntimeException('Falta a chave da OpenAI. Informe em Configurações > APIs.');
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($recording->path)) {
            throw new RuntimeException('O áudio da reunião não foi encontrado.');
        }
        if ($disk->size($recording->path) > self::MAX_AUDIO_BYTES) {
            throw new RuntimeException('A reunião ficou longa demais para a IA (mais de 25 MB de áudio). Escreva o resumo à mão.');
        }

        $lastError = null;
        foreach (self::TRANSCRIBE_MODELS as $model) {
            $response = Http::withToken($key)
                ->timeout(600)
                ->attach('file', $disk->get($recording->path), basename($recording->path))
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => $model,
                    'language' => 'pt',
                    'response_format' => 'text',
                ]);

            if ($response->successful()) {
                return trim($response->body());
            }
            if ($response->status() === 401) {
                throw new RuntimeException('A chave da OpenAI não foi aceita. Confira em Configurações > APIs.');
            }
            $lastError = $response->json('error.message') ?? $response->body();
        }

        throw new RuntimeException('A OpenAI não conseguiu transcrever a reunião: ' . mb_substr((string) $lastError, 0, 300));
    }

    private function writeSummary(Appointment $appointment, string $transcript): string
    {
        $key = self::anthropicKey();
        if (!$key) {
            throw new RuntimeException('Falta a chave do Claude. Informe em Configurações > APIs.');
        }

        $people = \App\Models\AppointmentParticipant::describe($appointment->participants()->get());
        if ($people) {
            $client = collect($people)->map(fn ($p) => mb_strtolower($p['label']) . ' ' . $p['name'])->join(', ', ' e ');
        } else {
            $client = $appointment->client ? 'cliente ' . $appointment->client->name : 'um cliente';
        }
        $when = $appointment->date?->format('d/m/Y') . ($appointment->start_time ? ' às ' . substr($appointment->start_time, 0, 5) : '');

        $system = <<<TXT
Você resume reuniões do Fastbar com clientes, parceiros, fornecedores e colaboradores. Participantes desta reunião: {$client}.
Escreva em português do Brasil, de forma organizada e curta, para a equipe ler depois no cadastro de cada participante.
Use títulos em negrito (**Título**) e marcadores (- ) e inclua só o que apareceu na conversa:
**Assunto da reunião**
**Evento** (tipo, data, local, número de convidados, se falado)
**O que foi pedido** (por quem e para quem, quando fizer diferença: cardápio, produtos, serviços, bebidas, estrutura, decoração, horários, restrições)
**Valores e condições** (preços, forma de pagamento, descontos, prazos)
**Decisões tomadas**
**Próximos passos** (quem faz o quê e até quando)
**Pontos em aberto**
Omita os títulos sem conteúdo. Não invente nada que não esteja na transcrição. A transcrição pode ter erros de reconhecimento de fala: corrija nomes e termos óbvios pelo contexto.
TXT;

        $response = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
        ])->timeout(600)->post('https://api.anthropic.com/v1/messages', [
            'model' => self::SUMMARY_MODEL,
            'max_tokens' => 4000,
            'system' => $system,
            'messages' => [[
                'role' => 'user',
                'content' => "Reunião \"{$appointment->title}\" com {$client} em {$when}.\n\nTranscrição:\n<transcricao>\n{$transcript}\n</transcricao>\n\nEscreva o resumo.",
            ]],
        ]);

        if ($response->status() === 401) {
            throw new RuntimeException('A chave do Claude não foi aceita. Confira em Configurações > APIs.');
        }
        if (!$response->successful()) {
            throw new RuntimeException('O Claude não conseguiu resumir a reunião agora. Tente de novo em alguns minutos.');
        }

        $text = collect($response->json('content') ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        if (trim($text) === '') {
            throw new RuntimeException('A IA não devolveu o resumo. Tente de novo.');
        }

        return trim($text);
    }
}
