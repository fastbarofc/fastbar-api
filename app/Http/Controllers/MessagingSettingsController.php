<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessagingAccount;
use App\Services\MetaMessagingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Comercial > Comunicação > Configurar: dados da Meta e canais (números/contas).
 */
class MessagingSettingsController extends Controller
{
    public function show()
    {
        return response()->json([
            'configured' => MetaMessagingService::isConfigured(),
            'app_secret_set' => (bool) AppSetting::getSecret(MetaMessagingService::SETTING_APP_SECRET),
            'app_id' => AppSetting::get(MetaMessagingService::SETTING_APP_ID),
            'waba_id' => AppSetting::get(MetaMessagingService::SETTING_WABA_ID),
            'verify_token' => MetaMessagingService::verifyToken(),
            'webhook_url' => MetaMessagingService::webhookUrl(),
            'has_conversations' => Conversation::exists(),
        ], 200);
    }

    public function update(Request $request)
    {
        $request->validate([
            'access_token' => 'nullable|string|max:1000',
            'app_secret' => 'nullable|string|max:200',
            'app_id' => 'nullable|string|max:50',
            'waba_id' => 'nullable|string|max:50',
        ]);

        if ($request->filled('access_token')) {
            AppSetting::putSecret(MetaMessagingService::SETTING_TOKEN, trim($request->input('access_token')));
        }
        if ($request->filled('app_secret')) {
            AppSetting::putSecret(MetaMessagingService::SETTING_APP_SECRET, trim($request->input('app_secret')));
        }
        if ($request->has('app_id')) {
            AppSetting::put(MetaMessagingService::SETTING_APP_ID, trim((string) $request->input('app_id')) ?: null);
        }
        if ($request->has('waba_id')) {
            AppSetting::put(MetaMessagingService::SETTING_WABA_ID, trim((string) $request->input('waba_id')) ?: null);
        }

        return $this->show();
    }

    public function accounts()
    {
        return response()->json(['data' => MessagingAccount::with('user')->orderBy('channel')->orderBy('name')->get()], 200);
    }

    public function storeAccount(Request $request)
    {
        $data = $this->validateAccount($request);
        $account = MessagingAccount::withTrashed()->firstOrNew(['channel' => $data['channel'], 'external_id' => $data['external_id']]);
        if ($account->trashed()) {
            $account->restore();
        }
        $account->fill($data)->save();

        return response()->json($account->load('user'), 201);
    }

    public function updateAccount(Request $request, MessagingAccount $messagingAccount)
    {
        $messagingAccount->update($this->validateAccount($request, $messagingAccount));

        return response()->json($messagingAccount->load('user'), 200);
    }

    public function destroyAccount(MessagingAccount $messagingAccount)
    {
        $messagingAccount->delete();

        return response()->json([], 204);
    }

    /** Busca na Meta os números da conta do WhatsApp Business e cadastra os que faltam. */
    public function importPhoneNumbers(Request $request)
    {
        $wabaId = trim((string) ($request->input('waba_id') ?: AppSetting::get(MetaMessagingService::SETTING_WABA_ID)));
        if (!$wabaId) {
            return response()->json(['message' => 'Informe o ID da conta do WhatsApp Business (WABA).'], 422);
        }
        AppSetting::put(MetaMessagingService::SETTING_WABA_ID, $wabaId);

        try {
            $numbers = MetaMessagingService::listPhoneNumbers($wabaId);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $created = 0;
        foreach ($numbers as $number) {
            $account = MessagingAccount::withTrashed()->firstOrNew(['channel' => 'whatsapp', 'external_id' => (string) $number['id']]);
            if (!$account->exists) {
                $account->name = $number['verified_name'] ?? ('WhatsApp ' . ($number['display_phone_number'] ?? ''));
                $created++;
            }
            if ($account->trashed()) {
                $account->restore();
            }
            $account->display = $number['display_phone_number'] ?? $account->display;
            $account->save();
        }

        return response()->json(['imported' => $created, 'total' => count($numbers)], 200);
    }

    /** Conversas de exemplo para testar a tela antes de a Meta estar ligada. Só quando não há conversas de verdade. */
    public function seedDemo(Request $request)
    {
        if (Conversation::exists()) {
            return response()->json(['message' => 'Já existem conversas; os exemplos só podem ser carregados em uma tela vazia.'], 422);
        }

        $userId = $request->user()?->id;
        $wa = MessagingAccount::firstOrCreate(['channel' => 'whatsapp', 'external_id' => 'demo-wa-1'], ['name' => 'Comercial (exemplo)', 'display' => '+55 31 98604-3398', 'user_id' => $userId]);
        $ig = MessagingAccount::firstOrCreate(['channel' => 'instagram', 'external_id' => 'demo-ig-1'], ['name' => 'Instagram Fastbar (exemplo)', 'display' => '@fastbar', 'user_id' => $userId]);

        $samples = [
            [$wa, '5531999990001', 'Mariana Souza', null, [
                ['in', 'Oi! Vi o bar de vocês no Instagram. Vocês fazem casamento para 150 pessoas em maio?', -180],
                ['out', 'Olá, Mariana! Fazemos sim. Qual a data e a cidade do evento?', -170],
                ['in', 'Dia 10/05/2027, aqui na cidade mesmo. Pode me mandar o cardápio de drinks?', -20],
            ]],
            [$wa, '5531999990002', 'Carlos Pereira', null, [
                ['in', 'Boa tarde, queria um orçamento de formatura.', -400],
                ['in', 'Uns 200 convidados, em dezembro.', -395],
            ]],
            [$ig, 'demo-ig-user-1', 'Ana Clara', 'anaclara.festas', [
                ['in', 'Amei as fotos! Quanto fica para aniversário de 15 anos?', -60],
                ['out', 'Oi, Ana! Obrigado 😊 Me conta a data e o número de convidados que eu te passo os valores.', -50],
                ['in', 'Dia 22/08, uns 80 convidados.', -5],
            ]],
            [$wa, '5531999990003', null, null, [
                ['in', 'Olá, vocês fazem bar para evento corporativo?', -2000],
                ['out', 'Olá! Fazemos sim. Quantas pessoas?', -1990],
            ]],
        ];

        foreach ($samples as [$account, $contactId, $name, $username, $messages]) {
            $conversation = Conversation::create([
                'messaging_account_id' => $account->id,
                'channel' => $account->channel,
                'contact_external_id' => $contactId,
                'contact_name' => $name,
                'contact_phone' => $account->channel === 'whatsapp' ? $contactId : null,
                'contact_username' => $username,
                'user_id' => $userId,
            ]);
            foreach ($messages as [$direction, $body, $minutes]) {
                $at = now()->addMinutes($minutes);
                Message::create([
                    'conversation_id' => $conversation->id,
                    'direction' => $direction,
                    'type' => 'text',
                    'body' => $body,
                    'status' => $direction === 'in' ? 'received' : 'read',
                    'user_id' => $direction === 'out' ? $userId : null,
                    'sent_at' => $at,
                ]);
                MetaMessagingService::touchConversation($conversation, $direction, $body, $at);
            }
        }

        return response()->json(['message' => 'Exemplos carregados.'], 201);
    }

    private function validateAccount(Request $request, ?MessagingAccount $account = null): array
    {
        return $request->validate(
            [
                'channel' => ['required', Rule::in(MessagingAccount::CHANNELS)],
                'name' => 'required|string|max:255',
                'external_id' => [
                    'required', 'string', 'max:100',
                    Rule::unique('messaging_accounts')->where('channel', $request->input('channel'))->ignore($account?->id)->whereNull('deleted_at'),
                ],
                'display' => 'nullable|string|max:255',
                'user_id' => 'nullable|exists:users,id',
                'active' => 'nullable|boolean',
            ],
            [
                'name.required' => 'Dê um nome ao canal (ex.: Comercial Juliana).',
                'external_id.required' => 'Informe o ID do número (Phone number ID) ou da conta do Instagram.',
                'external_id.unique' => 'Esse ID já está cadastrado.',
            ]
        );
    }
}
