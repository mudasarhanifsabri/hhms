<?php

namespace App\Http\Controllers;

use App\Models\WhatsappWebhookEvent;
use App\Support\AppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $verifyToken = AppSettings::get('whatsapp_verify_token', env('WHATSAPP_VERIFY_TOKEN'));

        if (
            $request->query('hub_mode') === 'subscribe'
            || $request->query('hub.mode') === 'subscribe'
        ) {
            $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
            $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

            if (filled($verifyToken) && hash_equals((string) $verifyToken, (string) $token)) {
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }
        }

        return response('Invalid verification token.', 403);
    }

    public function receive(Request $request)
    {
        $payload = $request->all();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                $metadata = $value['metadata'] ?? [];

                foreach ($value['statuses'] ?? [] as $status) {
                    WhatsappWebhookEvent::create([
                        'event_type' => 'status',
                        'message_id' => $status['id'] ?? null,
                        'from_number' => $status['recipient_id'] ?? null,
                        'to_number' => $metadata['display_phone_number'] ?? null,
                        'status' => $status['status'] ?? null,
                        'status_at' => isset($status['timestamp'])
                            ? Carbon::createFromTimestamp((int) $status['timestamp'])
                            : null,
                        'payload' => $status,
                    ]);
                }

                foreach ($value['messages'] ?? [] as $message) {
                    WhatsappWebhookEvent::create([
                        'event_type' => 'message',
                        'message_id' => $message['id'] ?? null,
                        'from_number' => $message['from'] ?? null,
                        'to_number' => $metadata['display_phone_number'] ?? null,
                        'status' => $message['type'] ?? null,
                        'status_at' => isset($message['timestamp'])
                            ? Carbon::createFromTimestamp((int) $message['timestamp'])
                            : null,
                        'payload' => $message,
                    ]);
                }
            }
        }

        Log::info('WhatsApp webhook received', ['payload' => $payload]);

        return response('EVENT_RECEIVED', 200);
    }
}
