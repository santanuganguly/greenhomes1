<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;
use App\Models\Message;

class WebhookController extends Controller
{
    /**
     * Verify the Meta Webhook setup.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = \App\Models\Setting::where('key', 'meta_verify_token')->first()?->value ?: env('META_VERIFY_TOKEN');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Webhook verified successfully.');
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming Meta Webhook events.
     */
    public function handle(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        
        Log::info('Incoming Meta Webhook', ['payload' => $payload]);

        app()->terminating(function () use ($payload) {
            \App\Jobs\ProcessWhatsAppWebhookJob::dispatchSync($payload);
        });

        // Immediately respond 200 OK to Meta to prevent retries
        return response('EVENT_RECEIVED', 200);
    }
}
