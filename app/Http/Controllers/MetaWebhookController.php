<?php

namespace App\Http\Controllers;

use App\Services\MetaMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endereço que a Meta chama para avisar mensagens novas de WhatsApp e Instagram.
 * Rota pública (sem login): a segurança é o código de verificação e a assinatura do aplicativo.
 */
class MetaWebhookController extends Controller
{
    /** Verificação feita pela Meta ao cadastrar o endereço. */
    public function verify(Request $request)
    {
        if ($request->query('hub_mode') === 'subscribe' && $request->query('hub_verify_token') === MetaMessagingService::verifyToken()) {
            return response($request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['message' => 'Código de verificação inválido.'], 403);
    }

    public function receive(Request $request)
    {
        if (!MetaMessagingService::signatureIsValid($request->getContent(), $request->header('X-Hub-Signature-256'))) {
            return response()->json(['message' => 'Assinatura inválida.'], 403);
        }

        try {
            MetaMessagingService::handleWebhook($request->json()->all());
        } catch (\Throwable $e) {
            // Sempre responde 200 para a Meta não repetir o aviso sem parar.
            Log::error('Meta webhook: ' . $e->getMessage(), ['payload' => $request->getContent()]);
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
