<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\CampaignLog;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $recipient;

    /**
     * Create a new job instance.
     */
    public function __construct(CampaignRecipient $recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsapp): void
    {
        $this->recipient->refresh();
        $campaign = $this->recipient->campaign;

        // Skip if campaign is not active (paused, cancelled, etc.)
        if (!in_array($campaign->status, ['running', 'queued'])) {
            $this->logSkip('Campaign is not in a running state.');
            return;
        }

        // Check if opted out (Lead level)
        if ($this->recipient->lead && $this->recipient->lead->marketing_opt_out) {
            $this->markOptedOut();
            return;
        }

        // Prevent duplicates
        if (in_array($this->recipient->status, ['sent', 'delivered', 'read'])) {
            $this->logSkip('Already successfully sent.');
            return;
        }

        $this->recipient->status = 'sending';
        $this->recipient->save();

        try {
            // Build Template Components dynamically
            $components = $this->buildTemplateComponents();
            
            // If buildTemplateComponents returns null, a required variable is missing
            if ($components === null) {
                $this->handleFailure(['error' => ['message' => 'Missing required variable.', 'code' => 'VARIABLE_MISSING']], false);
                return;
            }

            $response = $whatsapp->sendTemplate(
                $this->recipient->phone,
                $campaign->template_name,
                'en', 
                $components
            );

            if ($response && isset($response['messages'][0]['id'])) {
                // Success
                $this->recipient->meta_message_id = $response['messages'][0]['id'];
                $this->recipient->status = 'sent';
                $this->recipient->sent_at = now();
                $this->recipient->save();

                $campaign->increment('sent_count');

                CampaignLog::create([
                    'campaign_id' => $campaign->id,
                    'campaign_recipient_id' => $this->recipient->id,
                    'event' => 'message_sent',
                    'metadata' => ['meta_message_id' => $this->recipient->meta_message_id]
                ]);
            } else {
                // API Failure (e.g., rate limit, invalid number)
                $this->handleFailure($response);
            }
        } catch (\Exception $e) {
            Log::error('SendCampaignMessageJob Error: ' . $e->getMessage(), ['recipient_id' => $this->recipient->id]);
            $this->handleFailure(['error' => ['message' => $e->getMessage(), 'code' => 'EXCEPTION']]);
        }
    }

    protected function buildTemplateComponents(): ?array
    {
        $components = [];
        $variables = $this->recipient->variables ?? [];

        if (!empty($variables)) {
            $parameters = [];
            foreach ($variables as $key => $val) {
                // If a variable is completely missing and null, we should fail safely
                if ($val === null || $val === '') {
                    return null;
                }
                
                $parameters[] = [
                    'type' => 'text',
                    'text' => (string) $val
                ];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $parameters
            ];
        }

        return $components;
    }

    protected function handleFailure($response, bool $canRetry = true): void
    {
        $this->recipient->status = 'failed';
        $this->recipient->failed_at = now();
        $this->recipient->error_code = $response['error']['code'] ?? 'UNKNOWN';
        $this->recipient->error_message = $response['error']['message'] ?? json_encode($response);
        $this->recipient->retry_count += 1;
        $this->recipient->save();

        $this->recipient->campaign->increment('failed_count');

        CampaignLog::create([
            'campaign_id' => $this->recipient->campaign_id,
            'campaign_recipient_id' => $this->recipient->id,
            'event' => 'message_failed',
            'metadata' => $response
        ]);

        if (!$canRetry) {
            return;
        }

        // Retry logic
        $errorCode = $this->recipient->error_code;
        $rateLimitCodes = [130429, 131048, 131056, 'EXCEPTION']; // 429s or internal exceptions
        $permanentCodes = [131026, 131031, 131047, 132001, 132015, 'VARIABLE_MISSING']; // Invalid number, policy, etc

        if (in_array($errorCode, $permanentCodes, true)) {
            return; // Don't retry permanent failures
        }

        if ((in_array($errorCode, $rateLimitCodes, true) || empty($errorCode)) && $this->recipient->retry_count < 3) {
            // Re-queue with exponential backoff (e.g. 5, 25, 125 seconds)
            $delay = now()->addSeconds(pow(5, $this->recipient->retry_count));
            $this->recipient->campaign->decrement('failed_count'); // undo fail count
            $this->recipient->status = 'queued';
            $this->recipient->save();
            
            static::dispatch($this->recipient)->delay($delay);
        }
    }

    protected function logSkip(string $reason): void
    {
        $this->recipient->status = 'skipped';
        $this->recipient->error_message = $reason;
        $this->recipient->save();
        $this->recipient->campaign->increment('skipped_count');
    }

    protected function markOptedOut(): void
    {
        $this->recipient->status = 'opted_out';
        $this->recipient->save();
        $this->recipient->campaign->increment('opted_out_count');
    }
}
