<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\OrganizationSetting;
use App\Models\BulkMessage;
use App\Services\BotEngine;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    /**
     * Create a new job instance.
     *
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(BotEngine $botEngine): void
    {
        Log::info('Processing Webhook in Background Job', ['payload' => $this->payload]);

        if (isset($this->payload['entry'])) {
            foreach ($this->payload['entry'] as $entry) {
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        if ($change['value']) {
                            
                            // Resolve Tenant by phone_number_id
                            $phoneNumberId = $change['value']['metadata']['phone_number_id'] ?? null;
                            if ($phoneNumberId) {
                                $setting = OrganizationSetting::where('phone_number_id', $phoneNumberId)->first();
                                if ($setting) {
                                    Config::set('tenant.organization_id', $setting->organization_id);
                                }
                            }
                            
                            // Handle incoming messages
                            if (isset($change['value']['messages'])) {
                                $contacts = $change['value']['contacts'] ?? [];
                                $messages = $change['value']['messages'];

                                foreach ($messages as $msg) {
                                    $botEngine->processMessage($msg, $contacts);
                                }
                            }

                            // Handle message delivery statuses
                            if (isset($change['value']['statuses'])) {
                                foreach ($change['value']['statuses'] as $status) {
                                    $wamid = $status['id'] ?? null;
                                    $deliveryStatus = $status['status'] ?? null;
                                    
                                    if ($wamid && $deliveryStatus) {
                                        $bulkMsg = BulkMessage::where('wamid', $wamid)->first();
                                        if ($bulkMsg) {
                                            $bulkMsg->update(['status' => $deliveryStatus]);
                                        }
                                    }
                                }
                            }

                        }
                    }
                }
            }
        }
    }
}
