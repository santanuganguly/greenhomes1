<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BulkMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class SendBulkMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bulkMessageId;

    public function __construct($bulkMessageId)
    {
        $this->bulkMessageId = $bulkMessageId;
    }

    public function handle(): void
    {
        $bulkMsg = BulkMessage::with('campaign')->find($this->bulkMessageId);
        if (!$bulkMsg) return;

        $template = $bulkMsg->campaign->message_template;
        $name = $bulkMsg->contact_name ?: 'there'; // Fallback if no name
        
        $messageText = str_replace('{name}', trim($name), $template);

        $phoneId = env('META_PHONE_NUMBER_ID', Setting::where('key', 'meta_phone_number_id')->value('value'));
        $token = env('META_ACCESS_TOKEN', Setting::where('key', 'meta_access_token')->value('value'));

        if (!$phoneId || !$token) {
            $bulkMsg->update(['status' => 'failed', 'error_message' => 'Missing Meta credentials']);
            return;
        }

        $url = "https://graph.facebook.com/v19.0/{$phoneId}/messages";
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $bulkMsg->phone_number,
            'type' => 'text',
            'text' => [
                'body' => $messageText
            ]
        ];

        try {
            $response = Http::withToken($token)->post($url, $payload);
            $resData = $response->json();

            if ($response->successful() && isset($resData['messages'][0]['id'])) {
                $bulkMsg->update([
                    'status' => 'sent',
                    'wamid' => $resData['messages'][0]['id']
                ]);
            } else {
                $bulkMsg->update([
                    'status' => 'failed',
                    'error_message' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            $bulkMsg->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }
}
