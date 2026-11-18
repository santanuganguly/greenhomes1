<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\CampaignRecipient;
use App\Models\CampaignLog;

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

        $verifyToken = env('META_VERIFY_TOKEN');

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
        $payload = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256');
        $appSecret = env('META_APP_SECRET');

        if ($appSecret && $signature) {
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Webhook signature validation failed.');
                return response('Forbidden', 403);
            }
        }

        $payloadData = json_decode($payload, true);
        
        Log::info('Incoming Meta Webhook RAW: ' . $payload);

        // Process entry array
        if (isset($payloadData['entry'])) {
            $botEngine = app(\App\Services\BotEngine::class);

            foreach ($payloadData['entry'] as $entry) {
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        if ($change['value'] && isset($change['value']['messages'])) {
                            
                            $contacts = $change['value']['contacts'] ?? [];
                            $messages = $change['value']['messages'];

                            foreach ($messages as $msg) {
                                $botEngine->processMessage($msg, $contacts);
                            }
                        }

                        if ($change['value'] && isset($change['value']['statuses'])) {
                            foreach ($change['value']['statuses'] as $statusUpdate) {
                                if (!empty($statusUpdate['errors'])) {
                                    Log::error('Meta Delivery Status Error for ' . ($statusUpdate['recipient_id'] ?? 'unknown') . ': ' . json_encode($statusUpdate['errors']));
                                }
                                $this->handleStatusUpdate($statusUpdate);
                            }
                        }
                    }
                }
            }
        }

        // Immediately respond 200 OK to Meta to prevent retries
        return response('EVENT_RECEIVED', 200);
    }

    protected function handleStatusUpdate(array $statusUpdate): void
    {
        $metaMessageId = $statusUpdate['id'] ?? null;
        $status = $statusUpdate['status'] ?? null; // sent, delivered, read, failed

        if (!$metaMessageId || !$status) return;

        // Forward status updates to Project Control
        try {
            \Illuminate\Support\Facades\Http::timeout(3)->post('https://projectcontrol.thegreen.homes/api/whatsapp/status-callback', [
                'meta_message_id' => $metaMessageId,
                'status' => $status,
                'recipient_id' => $statusUpdate['recipient_id'] ?? null,
                'errors' => $statusUpdate['errors'] ?? null,
                'timestamp' => $statusUpdate['timestamp'] ?? time(),
            ]);
        } catch (\Throwable $t) {
            Log::warning('Failed to forward WhatsApp status to Project Control: ' . $t->getMessage());
        }

        $recipient = CampaignRecipient::where('meta_message_id', $metaMessageId)->first();
        if (!$recipient) return;

        $campaign = $recipient->campaign;

        // Ensure idempotency for statuses to prevent double incrementing
        if ($recipient->status === $status) return;
        if ($recipient->status === 'read') return; // Read is terminal success
        if ($recipient->status === 'delivered' && $status === 'sent') return; // Don't downgrade

        $recipient->status = $status;
        
        switch ($status) {
            case 'sent':
                $recipient->sent_at = now();
                // sent_count was already incremented during dispatch, but you could sync here if you prefer.
                break;
            case 'delivered':
                $recipient->delivered_at = now();
                $campaign->increment('delivered_count');
                break;
            case 'read':
                $recipient->read_at = now();
                $campaign->increment('read_count');
                break;
            case 'failed':
                $recipient->failed_at = now();
                $recipient->error_code = $statusUpdate['errors'][0]['code'] ?? 'UNKNOWN';
                $recipient->error_message = $statusUpdate['errors'][0]['message'] ?? 'Webhook Failure';
                $campaign->increment('failed_count');
                break;
        }

        $recipient->save();

        CampaignLog::create([
            'campaign_id' => $campaign->id,
            'campaign_recipient_id' => $recipient->id,
            'event' => 'message_status_updated',
            'metadata' => $statusUpdate
        ]);
        
        // If all recipients are in a terminal state, mark campaign completed
        if ($campaign->recipients()->whereIn('status', ['pending', 'queued', 'sending', 'sent', 'delivered'])->count() === 0) {
            if ($campaign->failed_count > 0) {
                $campaign->status = 'completed_with_errors';
            } else {
                $campaign->status = 'completed';
            }
            $campaign->completed_at = now();
            $campaign->save();
        }
    }
}
