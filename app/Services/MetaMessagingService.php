<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\MessagingAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Conversa com a plataforma da Meta (WhatsApp Business Platform e Instagram Messaging):
 * recebe os avisos (webhooks), baixa mídias e envia respostas.
 */
class MetaMessagingService
{
    public const SETTING_TOKEN = 'meta_access_token';
    public const SETTING_APP_SECRET = 'meta_app_secret';
    public const SETTING_APP_ID = 'meta_app_id';
    public const SETTING_VERIFY_TOKEN = 'meta_verify_token';
    public const SETTING_WABA_ID = 'meta_waba_id';

    private const GRAPH = 'https://graph.facebook.com/v21.0';

    public static function isConfigured(): bool
    {
        return (bool) AppSetting::getSecret(self::SETTING_TOKEN);
    }

    /** Código que a Meta confere ao cadastrar o endereço de notificação. Gerado uma vez. */
    public static function verifyToken(): string
    {
        $token = AppSetting::get(self::SETTING_VERIFY_TOKEN);
        if (!$token) {
            $token = 'fastbar-' . Str::lower(Str::random(24));
            AppSetting::put(self::SETTING_VERIFY_TOKEN, $token);
        }
        return $token;
    }

    public static function webhookUrl(): string
    {
        return rtrim(config('app.url'), '/') . '/api/webhooks/meta';
    }

    /** Confere a assinatura X-Hub-Signature-256 quando o segredo do aplicativo está cadastrado. */
    public static function signatureIsValid(string $payload, ?string $header): bool
    {
        $secret = AppSetting::getSecret(self::SETTING_APP_SECRET);
        if (!$secret) {
            return true; // sem segredo cadastrado não dá para conferir; aceita
        }
        if (!$header || !str_starts_with($header, 'sha256=')) {
            return false;
        }
        return hash_equals('sha256=' . hash_hmac('sha256', $payload, $secret), $header);
    }

    /**
     * Processa um aviso da Meta. Devolve quantas mensagens novas foram gravadas.
     */
    public static function handleWebhook(array $payload): int
    {
        $object = $payload['object'] ?? '';
        $count = 0;

        foreach ($payload['entry'] ?? [] as $entry) {
            if ($object === 'whatsapp_business_account') {
                foreach ($entry['changes'] ?? [] as $change) {
                    $count += self::handleWhatsAppChange($change['field'] ?? '', $change['value'] ?? []);
                }
            } elseif ($object === 'instagram') {
                $accountId = (string) ($entry['id'] ?? '');
                foreach ($entry['messaging'] ?? [] as $event) {
                    $count += self::handleInstagramEvent($accountId, $event);
                }
            }
        }

        return $count;
    }

    private static function handleWhatsAppChange(string $field, array $value): int
    {
        $phoneNumberId = (string) ($value['metadata']['phone_number_id'] ?? '');
        if (!$phoneNumberId) {
            return 0;
        }
        $account = MessagingAccount::firstOrCreate(
            ['channel' => 'whatsapp', 'external_id' => $phoneNumberId],
            ['name' => 'WhatsApp ' . ($value['metadata']['display_phone_number'] ?? $phoneNumberId), 'display' => $value['metadata']['display_phone_number'] ?? null]
        );

        $names = [];
        foreach ($value['contacts'] ?? [] as $contact) {
            $names[(string) ($contact['wa_id'] ?? '')] = $contact['profile']['name'] ?? null;
        }

        $count = 0;

        // Mensagens recebidas dos clientes.
        foreach ($value['messages'] ?? [] as $msg) {
            $from = (string) ($msg['from'] ?? '');
            if (!$from) {
                continue;
            }
            $conversation = self::conversationFor($account, $from, $names[$from] ?? null, $from);
            if (self::storeMessage($conversation, 'in', $msg, $account)) {
                $count++;
            }
        }

        // Mensagens enviadas pelo celular (coexistência com o app WhatsApp Business).
        if ($field === 'smb_message_echoes') {
            foreach ($value['message_echoes'] ?? [] as $msg) {
                $to = (string) ($msg['to'] ?? '');
                if (!$to) {
                    continue;
                }
                $conversation = self::conversationFor($account, $to, $names[$to] ?? null, $to);
                if (self::storeMessage($conversation, 'out', $msg, $account)) {
                    $count++;
                }
            }
        }

        // Situação das mensagens enviadas (entregue, lida, falhou).
        foreach ($value['statuses'] ?? [] as $status) {
            $message = Message::where('external_id', $status['id'] ?? '')->first();
            if ($message && in_array($status['status'] ?? '', ['sent', 'delivered', 'read', 'failed'], true)) {
                $message->update([
                    'status' => $status['status'],
                    'error' => $status['status'] === 'failed' ? json_encode($status['errors'] ?? []) : null,
                ]);
            }
        }

        return $count;
    }

    private static function handleInstagramEvent(string $accountId, array $event): int
    {
        $message = $event['message'] ?? null;
        if (!$message || !$accountId) {
            return 0;
        }
        $account = MessagingAccount::firstOrCreate(
            ['channel' => 'instagram', 'external_id' => $accountId],
            ['name' => 'Instagram ' . $accountId]
        );

        $isEcho = !empty($message['is_echo']);
        $contactId = (string) ($isEcho ? ($event['recipient']['id'] ?? '') : ($event['sender']['id'] ?? ''));
        if (!$contactId) {
            return 0;
        }

        $conversation = self::conversationFor($account, $contactId, null, null);
        if (!$conversation->contact_username) {
            self::fillInstagramProfile($conversation);
        }

        $data = [
            'id' => $message['mid'] ?? null,
            'timestamp' => isset($event['timestamp']) ? (int) ($event['timestamp'] / 1000) : time(),
            'type' => 'text',
            'text' => ['body' => $message['text'] ?? ''],
        ];
        $attachment = $message['attachments'][0] ?? null;
        if ($attachment) {
            $type = $attachment['type'] ?? 'other';
            $data['type'] = in_array($type, ['image', 'video', 'audio', 'file'], true) ? ($type === 'file' ? 'document' : $type) : 'other';
            $data['media_url'] = $attachment['payload']['url'] ?? null;
        }

        return self::storeMessage($conversation, $isEcho ? 'out' : 'in', $data, $account) ? 1 : 0;
    }

    private static function conversationFor(MessagingAccount $account, string $contactId, ?string $name, ?string $phone): Conversation
    {
        $conversation = Conversation::firstOrCreate(
            ['messaging_account_id' => $account->id, 'contact_external_id' => $contactId],
            [
                'channel' => $account->channel,
                'contact_name' => $name,
                'contact_phone' => $phone,
                'user_id' => $account->user_id,
                'internal' => $phone ? self::isTeamNumber($phone) : false,
            ]
        );
        if ($name && !$conversation->contact_name) {
            $conversation->update(['contact_name' => $name]);
        }
        if ($conversation->wasRecentlyCreated) {
            self::linkClientAndLead($conversation);
        }
        return $conversation;
    }

    /** Número de um dos canais da equipe = conversa interna. */
    private static function isTeamNumber(string $phone): bool
    {
        $digits = preg_replace('/\D/', '', $phone);
        return MessagingAccount::where('channel', 'whatsapp')
            ->get()
            ->contains(fn ($a) => $a->display && str_ends_with($digits, substr(preg_replace('/\D/', '', $a->display), -8)));
    }

    /** Liga a conversa a um cliente já cadastrado (pelo telefone) e cria o lead no funil. */
    private static function linkClientAndLead(Conversation $conversation): void
    {
        if ($conversation->internal) {
            return;
        }
        $client = null;
        if ($conversation->contact_phone) {
            $tail = substr(preg_replace('/\D/', '', $conversation->contact_phone), -8);
            $client = Client::where('phone_1', 'like', "%$tail")->orWhere('phone_2', 'like', "%$tail")->first();
        }

        $lead = null;
        if ($client) {
            $lead = Lead::where('client_id', $client->id)->whereNotIn('stage', ['ganho', 'perdido'])->first();
        }
        if (!$lead && $conversation->contact_phone) {
            $tail = substr(preg_replace('/\D/', '', $conversation->contact_phone), -8);
            // O telefone do lead pode estar com máscara "(31) 99999-0001"; compara só os dígitos.
            $lead = Lead::whereNotIn('stage', ['ganho', 'perdido'])
                ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') like ?", ["%$tail"])
                ->first();
        }
        if (!$lead && $conversation->contact_username) {
            $lead = Lead::whereNotIn('stage', ['ganho', 'perdido'])->where('instagram', $conversation->contact_username)->first();
        }
        $assigned = [];
        if (!$lead) {
            Lead::where('stage', 'novo')->increment('position');
            // Sem consultor na conta da Meta: entra no rodízio de Regras comerciais (lead sem cidade e sem tipo).
            $assigned = $conversation->user_id ? ['user_id' => $conversation->user_id] : app(LeadRulesService::class)->distribute(['source' => $conversation->channel]);
            $lead = Lead::create([
                'name' => $conversation->contact_name ?: ($conversation->contact_username ? '@' . $conversation->contact_username : ($conversation->contact_phone ?: 'Contato ' . $conversation->channel)),
                'phone' => $conversation->contact_phone,
                'instagram' => $conversation->contact_username,
                'source' => $conversation->channel,
                'stage' => 'novo',
                'position' => 0,
                'user_id' => $assigned['user_id'] ?? null,
                'client_id' => $client?->id,
                'stage_changed_at' => now(),
            ]);
        }

        if (!empty($assigned['auto_assigned'])) {
            app(LeadRulesService::class)->notifyAssigned($lead->load('user'), null);
            $conversation->update(['user_id' => $lead->user_id]);
        }
        $conversation->update(['client_id' => $client?->id, 'lead_id' => $lead->id]);
    }

    private static function storeMessage(Conversation $conversation, string $direction, array $msg, MessagingAccount $account): bool
    {
        $externalId = $msg['id'] ?? null;
        if ($externalId && Message::where('external_id', $externalId)->exists()) {
            return false; // a Meta repete avisos; grava uma vez só
        }

        $type = $msg['type'] ?? 'text';
        $body = match ($type) {
            'text' => $msg['text']['body'] ?? '',
            'button' => $msg['button']['text'] ?? '',
            'interactive' => $msg['interactive']['button_reply']['title'] ?? ($msg['interactive']['list_reply']['title'] ?? ''),
            'reaction' => $msg['reaction']['emoji'] ?? '',
            'location' => 'Localização: ' . ($msg['location']['latitude'] ?? '') . ', ' . ($msg['location']['longitude'] ?? ''),
            'contacts' => 'Contato compartilhado',
            default => $msg[$type]['caption'] ?? '',
        };
        $storedType = in_array($type, ['image', 'audio', 'video', 'document', 'sticker'], true) ? $type : ($type === 'text' ? 'text' : 'other');

        $mediaUrl = $msg['media_url'] ?? null;
        $mediaMime = $msg[$type]['mime_type'] ?? null;
        $mediaName = $msg[$type]['filename'] ?? null;
        if (!$mediaUrl && isset($msg[$type]['id']) && $storedType !== 'text') {
            $mediaUrl = self::downloadMedia($msg[$type]['id'], $mediaMime);
        }

        $sentAt = isset($msg['timestamp']) ? Carbon::createFromTimestamp((int) $msg['timestamp']) : now();

        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => $direction,
            'type' => $storedType,
            'body' => $body,
            'media_url' => $mediaUrl,
            'media_mime' => $mediaMime,
            'media_name' => $mediaName,
            'external_id' => $externalId,
            'status' => $direction === 'in' ? 'received' : 'sent',
            'sent_at' => $sentAt,
        ]);

        self::touchConversation($conversation, $direction, $body ?: self::typeLabel($storedType), $sentAt);

        return true;
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'image' => '📷 Foto',
            'audio' => '🎤 Áudio',
            'video' => '🎬 Vídeo',
            'document' => '📄 Documento',
            'sticker' => 'Figurinha',
            default => 'Mensagem',
        };
    }

    public static function touchConversation(Conversation $conversation, string $direction, string $preview, Carbon $at): void
    {
        $changes = [
            'last_message_at' => $at,
            'last_direction' => $direction,
            'last_message_preview' => Str::limit($preview, 150),
            'status' => 'open',
        ];
        if ($direction === 'in') {
            $changes['unread_count'] = $conversation->unread_count + 1;
            if (!$conversation->waiting_since) {
                $changes['waiting_since'] = $at;
            }
        } else {
            $changes['waiting_since'] = null;
        }
        $conversation->update($changes);
    }

    /** Baixa uma mídia do WhatsApp (precisa do token) e guarda em storage/app/public/messaging. */
    private static function downloadMedia(string $mediaId, ?string $mime): ?string
    {
        $token = AppSetting::getSecret(self::SETTING_TOKEN);
        if (!$token) {
            return null;
        }
        try {
            $info = Http::withToken($token)->timeout(15)->get(self::GRAPH . "/$mediaId")->json();
            if (empty($info['url'])) {
                return null;
            }
            $file = Http::withToken($token)->timeout(30)->get($info['url']);
            if (!$file->ok()) {
                return null;
            }
            $ext = self::extensionFor($mime ?: ($info['mime_type'] ?? ''));
            $path = '/messaging/' . date('Y/m') . '/' . $mediaId . ($ext ? ".$ext" : '');
            Storage::disk('public')->put($path, $file->body());
            return asset('storage' . $path);
        } catch (\Throwable $e) {
            Log::warning('Meta media download failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function extensionFor(string $mime): string
    {
        $mime = explode(';', $mime)[0];
        return match ($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
            'audio/ogg' => 'ogg', 'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/aac' => 'aac', 'audio/amr' => 'amr',
            'video/mp4' => 'mp4', 'video/3gpp' => '3gp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            default => '',
        };
    }

    private static function fillInstagramProfile(Conversation $conversation): void
    {
        $token = AppSetting::getSecret(self::SETTING_TOKEN);
        if (!$token) {
            return;
        }
        try {
            $profile = Http::withToken($token)->timeout(10)
                ->get(self::GRAPH . '/' . $conversation->contact_external_id, ['fields' => 'name,username'])
                ->json();
            $conversation->update([
                'contact_name' => $conversation->contact_name ?: ($profile['name'] ?? null),
                'contact_username' => $profile['username'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Meta profile lookup failed: ' . $e->getMessage());
        }
    }

    /**
     * Envia um texto pela Meta. Devolve o id da mensagem na Meta ou lança exceção com mensagem simples.
     */
    public static function sendText(Conversation $conversation, string $text): string
    {
        $token = AppSetting::getSecret(self::SETTING_TOKEN);
        if (!$token) {
            throw new \RuntimeException('A Comunicação ainda não foi configurada: cole o token da Meta em Configurar.');
        }
        $account = $conversation->account;

        if ($conversation->channel === 'whatsapp') {
            $response = Http::withToken($token)->timeout(20)->post(self::GRAPH . "/{$account->external_id}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $conversation->contact_external_id,
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $text],
            ]);
            $id = $response->json('messages.0.id');
        } else {
            $response = Http::withToken($token)->timeout(20)->post(self::GRAPH . "/{$account->external_id}/messages", [
                'recipient' => ['id' => $conversation->contact_external_id],
                'message' => ['text' => $text],
            ]);
            $id = $response->json('message_id');
        }

        if (!$response->ok() || !$id) {
            $error = $response->json('error.message') ?: 'A Meta não aceitou a mensagem.';
            $code = $response->json('error.code');
            if ($code === 131047 || str_contains($error, '24')) {
                $error = 'Passaram mais de 24 horas desde a última mensagem do cliente. Pelo WhatsApp só é possível enviar um modelo aprovado pela Meta.';
            }
            throw new \RuntimeException($error);
        }

        return (string) $id;
    }

    /** Lista os números da conta do WhatsApp Business (WABA) para cadastrar como canais. */
    public static function listPhoneNumbers(string $wabaId): array
    {
        $token = AppSetting::getSecret(self::SETTING_TOKEN);
        if (!$token) {
            throw new \RuntimeException('Cole o token da Meta antes de buscar os números.');
        }
        $response = Http::withToken($token)->timeout(20)->get(self::GRAPH . "/$wabaId/phone_numbers", [
            'fields' => 'id,display_phone_number,verified_name,quality_rating',
        ]);
        if (!$response->ok()) {
            throw new \RuntimeException($response->json('error.message') ?: 'A Meta não devolveu os números.');
        }
        return $response->json('data') ?? [];
    }
}
