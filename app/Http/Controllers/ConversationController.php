<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessagingAccount;
use App\Services\MetaMessagingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Comercial > Comunicação: lista de conversas e troca de mensagens.
 */
class ConversationController extends Controller
{
    private const RELATIONS = ['account', 'user', 'lead', 'client'];
    private const SEE_ALL = 'comercial-comunicacao-ver-todos';
    private const REPLY = 'comercial-comunicacao-responder';

    private ?array $slugs = null;

    /**
     * Filtros: account_id, channel, filter (sem_resposta | nao_lidas | todas | internas), user_id, search, order (recent | waiting).
     * Conversas abandonadas há mais de 30 dias ficam fora da fila (a não ser com search ou filter=todas).
     */
    public function index(Request $request)
    {
        $query = $this->visible($request->user())->with(self::RELATIONS);
        $filter = $request->input('filter', 'sem_resposta');
        $search = trim((string) $request->input('search'));

        if ($request->filled('account_id')) {
            $query->where('messaging_account_id', $request->input('account_id'));
        }
        if ($channel = $request->input('channel')) {
            $query->where('channel', $channel);
        }
        if ($request->filled('user_id')) {
            $request->input('user_id') === 'none'
                ? $query->whereNull('user_id')
                : $query->where('user_id', $request->input('user_id'));
        }

        $query->where('internal', $filter === 'internas');

        switch ($filter) {
            case 'sem_resposta':
                $query->whereNotNull('waiting_since');
                break;
            case 'nao_lidas':
                $query->where('unread_count', '>', 0);
                break;
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('contact_name', 'like', "%$search%")
                    ->orWhere('contact_phone', 'like', "%$search%")
                    ->orWhere('contact_username', 'like', "%$search%")
                    ->orWhere('last_message_preview', 'like', "%$search%")
                    ->orWhereHas('messages', fn ($m) => $m->where('body', 'like', "%$search%"));
                if (ctype_digit($search)) {
                    $q->orWhere('conversations.id', (int) $search)->orWhere('lead_id', (int) $search);
                }
            });
        } elseif ($filter !== 'todas') {
            $query->where(fn ($q) => $q->where('last_message_at', '>=', now()->subDays(30))->orWhereNull('last_message_at'));
        }

        $request->input('order') === 'waiting'
            ? $query->orderByRaw('waiting_since is null')->orderBy('waiting_since')
            : $query->orderByDesc('last_message_at');

        $conversations = $query->limit(300)->get();

        $abandoned = $this->visible($request->user())->where('internal', false)->where('last_message_at', '<', now()->subDays(30))->count();

        return response()->json([
            'data' => ConversationResource::collection($conversations),
            'abandoned_count' => $abandoned,
        ], 200);
    }

    /** Canais (números e contas) com contadores de não respondidas, para o painel da esquerda. */
    public function summary(Request $request)
    {
        $user = $request->user();
        $accounts = MessagingAccount::with('user')->orderBy('channel')->orderBy('name')->get();
        $waiting = $this->visible($user)->whereNotNull('waiting_since')->where('internal', false)
            ->selectRaw('messaging_account_id, count(*) as total')->groupBy('messaging_account_id')->pluck('total', 'messaging_account_id');
        $unread = $this->visible($user)->where('unread_count', '>', 0)->where('internal', false)
            ->selectRaw('messaging_account_id, count(*) as total')->groupBy('messaging_account_id')->pluck('total', 'messaging_account_id');

        return response()->json([
            'accounts' => $accounts->map(fn ($a) => [
                'id' => $a->id,
                'channel' => $a->channel,
                'name' => $a->name,
                'display' => $a->display,
                'user_id' => $a->user_id,
                'user_name' => $a->user?->name,
                'active' => $a->active,
                'waiting' => (int) ($waiting[$a->id] ?? 0),
                'unread' => (int) ($unread[$a->id] ?? 0),
            ]),
            'total_waiting' => (int) $waiting->sum(),
            'total_unread' => (int) $unread->sum(),
            'internal_count' => $this->visible($user)->where('internal', true)->count(),
            'views' => [
                'sem_nome' => $this->visible($user)->where('internal', false)->whereNull('contact_name')->count(),
                'sem_lead' => $this->visible($user)->where('internal', false)->whereNull('lead_id')->count(),
                'varios_numeros' => $this->visible($user)->where('internal', false)->whereNotNull('client_id')
                    ->selectRaw('client_id')->groupBy('client_id')->havingRaw('count(*) > 1')->get()->count(),
            ],
            'configured' => MetaMessagingService::isConfigured(),
            'see_all' => $this->can($user, self::SEE_ALL),
            // Pode responder em algum número (é consultor vinculado a um número). Cada conversa diz o seu em can_reply.
            'can_reply' => $this->can($user, self::REPLY) && MessagingAccount::where('user_id', $user?->id ?? 0)->exists(),
        ], 200);
    }

    /**
     * Abre a conversa com as últimas mensagens. Só marca como lida para o consultor vinculado ao número
     * (quem responde). Administrador e gerência só acompanham: abrem sem marcar como lida.
     */
    public function show(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $this->guardVisible($user, $conversation);
        $conversation->load(self::RELATIONS);
        $messages = $conversation->messages()->with('user')->orderByDesc('sent_at')->orderByDesc('id')->limit(200)->get()->reverse()->values();
        $conversation->setRelation('messages', $messages);

        $canReply = $this->canReply($user, $conversation);
        if ($canReply && $conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        return response()->json((new ConversationResource($conversation))->resolve($request) + ['can_reply' => $canReply], 200);
    }

    /** Atribuir consultor, ligar a um lead/cliente, fechar ou renomear o contato. */
    public function update(Request $request, Conversation $conversation)
    {
        $this->guardVisible($request->user(), $conversation);
        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'lead_id' => 'nullable|exists:leads,id',
            'client_id' => 'nullable|exists:clients,id',
            'contact_name' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['open', 'closed'])],
            'internal' => 'nullable|boolean',
        ]);

        $conversation->update(array_filter($data, fn ($v) => $v !== null) + ($request->has('user_id') ? ['user_id' => $data['user_id']] : []));

        return response()->json(new ConversationResource($conversation->fresh(self::RELATIONS)), 200);
    }

    /** Responde pelo sistema. */
    public function send(Request $request, Conversation $conversation)
    {
        $this->guardVisible($request->user(), $conversation);
        if (!$this->canReply($request->user(), $conversation)) {
            return response()->json(['message' => 'Só o consultor vinculado a este número pode responder. Você pode acompanhar a conversa.'], 403);
        }
        $request->validate(['body' => 'required|string|max:4000'], ['body.required' => 'Escreva a mensagem.']);
        $text = trim($request->input('body'));

        try {
            $externalId = MetaMessagingService::sendText($conversation, $text);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'out',
            'type' => 'text',
            'body' => $text,
            'external_id' => $externalId,
            'status' => 'sent',
            'user_id' => $request->user()?->id,
            'sent_at' => now(),
        ]);

        $changes = [];
        if (!$conversation->user_id && $request->user()) {
            $changes['user_id'] = $request->user()->id;
        }
        if ($changes) {
            $conversation->update($changes);
        }
        MetaMessagingService::touchConversation($conversation, 'out', $text, now());

        return response()->json(new MessageResource($message->load('user')), 201);
    }

    // ----------------------------------------------------------------------------------------

    /** Responde só o consultor vinculado ao número (ou conta) da conversa, com a permissão de responder. */
    private function canReply($user, Conversation $conversation): bool
    {
        if (!$user || !$this->can($user, self::REPLY)) {
            return false;
        }
        $conversation->loadMissing('account');

        return $conversation->account && (int) $conversation->account->user_id === (int) $user->id;
    }

    /**
     * Conversas que o usuário enxerga: todas (administrador e gerência) ou só as dele — as que chegam
     * no número vinculado a ele e as atribuídas a ele.
     */
    private function visible($user)
    {
        $query = Conversation::query();
        if ($this->can($user, self::SEE_ALL)) {
            return $query;
        }
        $userId = $user?->id ?? 0;

        return $query->where(function ($q) use ($userId) {
            $q->where('conversations.user_id', $userId)
                ->orWhereHas('account', fn ($a) => $a->where('user_id', $userId));
        });
    }

    private function guardVisible($user, Conversation $conversation): void
    {
        abort_unless($this->visible($user)->whereKey($conversation->id)->exists(), 403, 'Esta conversa é de outro consultor.');
    }

    private function can($user, string $slug): bool
    {
        if (!$user) {
            return false;
        }
        $this->slugs ??= method_exists($user, 'effectivePermissions')
            ? $user->effectivePermissions()->pluck('slug')->all()
            : $user->getAllPermissions()->pluck('slug')->all();

        return in_array($slug, $this->slugs, true);
    }
}
