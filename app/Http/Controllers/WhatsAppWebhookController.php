<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\BotService;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private BotService $bot) {}

    public function handle(Request $request)
    {
        \Log::info('Webhook recibido', $request->all());

        $event = $request->input('event');
        $data = $request->input('data');

        if ($event == 'messages.upsert') {
            $fromMe = $data['key']['fromMe'] ?? false;

            $telefono = $data['key']['remoteJidAlt']
                ?? $data['key']['remoteJid'];

            $mensaje = $data['message']['conversation']
                ?? $data['message']['extendedTextMessage']['text']
                ?? null;

            if (!$fromMe && $mensaje && !str_contains($telefono, '@g.us')) {
                $this->bot->handle($telefono, $mensaje);
            }
        }

        return response()->json(['ok' => true]);
    }
}
