<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $token;
    protected string $phoneId;
    protected string $wabaId;

    public function __construct()
    {
        $this->token = env('META_ACCESS_TOKEN', '');
        $this->phoneId = env('META_PHONE_NUMBER_ID', '');
        $this->wabaId = env('META_WABA_ID', '');
    }

    public function sendInteractive(string $phone, ?string $header, string $body, ?string $footer, array $buttons)
    {
        if (!$this->token || !$this->phoneId) return null;

        $buttonChunks = array_chunk($buttons, 3);
        $lastResponse = null;

        foreach ($buttonChunks as $chunk) {
            $msgButtons = [];
            foreach ($chunk as $btn) {
                if (($btn['action_type'] ?? 'reply') === 'call') {
                    continue;
                }
                $msgButtons[] = [
                    'type'  => 'reply',
                    'reply' => [
                        'id'    => $btn['payload'],
                        'title' => mb_substr($btn['label'], 0, 20),
                    ],
                ];
            }

            if (empty($msgButtons)) {
                return $this->sendText($phone, $body);
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $phone,
                'type'              => 'interactive',
                'interactive'       => [
                    'type' => 'button',
                    'body' => ['text' => $body],
                    'action' => ['buttons' => $msgButtons],
                ],
            ];

            if ($header) {
                $payload['interactive']['header'] = ['type' => 'text', 'text' => mb_substr($header, 0, 60)];
            }
            if ($footer) {
                $payload['interactive']['footer'] = ['text' => mb_substr($footer, 0, 60)];
            }

            $lastResponse = Http::withoutVerifying()->withToken($this->token)
                ->post("https://graph.facebook.com/v19.0/{$this->phoneId}/messages", $payload);
            
            Log::info("WhatsAppService sendInteractive: " . $lastResponse->body());
            $this->logOutboundMessage($phone, 'interactive', $body, $payload, $lastResponse);
        }

        return $lastResponse ? $lastResponse->json() : null;
    }

    public function sendList(string $phone, ?string $header, string $body, ?string $footer, string $buttonLabel, array $sections)
    {
        if (!$this->token || !$this->phoneId) return null;

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $phone,
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'list',
                'body' => ['text' => $body],
                'action' => [
                    'button'   => mb_substr($buttonLabel, 0, 20),
                    'sections' => $sections,
                ],
            ],
        ];

        if ($header) {
            $payload['interactive']['header'] = ['type' => 'text', 'text' => mb_substr($header, 0, 60)];
        }
        if ($footer) {
            $payload['interactive']['footer'] = ['text' => mb_substr($footer, 0, 60)];
        }

        $response = Http::withoutVerifying()->withToken($this->token)
            ->post("https://graph.facebook.com/v19.0/{$this->phoneId}/messages", $payload);
        
        Log::info("WhatsAppService sendList: " . $response->body());
        $this->logOutboundMessage($phone, 'interactive', $body, $payload, $response);
        return $response->json();
    }

    public function sendText(string $phoneNumber, string $text)
    {
        if (!$this->token || !$this->phoneId) return null;

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $phoneNumber,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $text],
        ];

        $response = Http::withoutVerifying()->withToken($this->token)
            ->post("https://graph.facebook.com/v19.0/{$this->phoneId}/messages", $payload);
        
        Log::info("WhatsAppService sendText: " . $response->body());
        $this->logOutboundMessage($phoneNumber, 'text', $text, $payload, $response);
        return $response->json();
    }

    public function sendTemplate(string $phoneNumber, string $templateName, string $language = 'en', array $components = [])
    {
        if (!$this->token || !$this->phoneId) return null;

        $templatePayload = [
            'name'     => $templateName,
            'language' => ['code' => $language],
        ];
        if (!empty($components)) {
            $templatePayload['components'] = $components;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $phoneNumber,
            'type'              => 'template',
            'template'          => $templatePayload,
        ];

        Log::info("WhatsAppService sendTemplate payload: " . json_encode($payload));
        $response = Http::withoutVerifying()->withToken($this->token)
            ->post("https://graph.facebook.com/v19.0/{$this->phoneId}/messages", $payload);
        
        Log::info("WhatsAppService sendTemplate response: " . $response->body());
        $this->logOutboundMessage($phoneNumber, 'template', $templateName, $payload, $response);
        return $response->json();
    }

    public function getTemplates()
    {
        if (!$this->token || !$this->wabaId) {
            Log::warning("WhatsAppService getTemplates: Missing META_ACCESS_TOKEN or META_WABA_ID.");
            return [];
        }

        $response = Http::withoutVerifying()->withToken($this->token)
            ->get("https://graph.facebook.com/v19.0/{$this->wabaId}/message_templates", [
                'fields' => 'name,language,status,category,components',
                'limit' => 100,
            ]);

        if ($response->successful()) {
            return $response->json()['data'] ?? [];
        }

        Log::error("WhatsAppService getTemplates error: " . $response->body());
        return [];
    }

    public function sendDocument(string $phoneNumber, string $documentUrl, string $filename)
    {
        if (!$this->token || !$this->phoneId) return null;

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $phoneNumber,
            'type'              => 'document',
            'document'          => [
                'link' => $documentUrl,
                'filename' => $filename
            ],
        ];

        $response = Http::withoutVerifying()->withToken($this->token)
            ->post("https://graph.facebook.com/v19.0/{$this->phoneId}/messages", $payload);
        
        Log::info("WhatsAppService sendDocument: " . $response->body());
        $this->logOutboundMessage($phoneNumber, 'document', $filename, $payload, $response);
        return $response->json();
    }

    public function sendImage(string $phoneNumber, string $imageUrl)
    {
        if (!$this->token || !$this->phoneId) return null;

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $phoneNumber,
            'type'              => 'image',
            'image'             => [
                'link' => $imageUrl
            ],
        ];

        $response = Http::withoutVerifying()->withToken($this->token)
            ->post("https://graph.facebook.com/v19.0/{$this->phoneId}/messages", $payload);
        
        Log::info("WhatsAppService sendImage: " . $response->body());
        $this->logOutboundMessage($phoneNumber, 'image', 'Image', $payload, $response);
        return $response->json();
    }

    protected function logOutboundMessage(string $phone, string $type, string $content, ?array $payload, $response)
    {
        try {
            $conversation = \App\Models\Conversation::firstOrCreate(
                ['phone_number' => $phone],
                ['status' => 'bot']
            );
            
            $metaMessageId = null;
            if ($response && $response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['messages'][0]['id'])) {
                    $metaMessageId = $responseData['messages'][0]['id'];
                }
            }

            \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'meta_message_id' => $metaMessageId,
                'direction'       => 'outbound',
                'type'            => $type,
                'content'         => $content,
                'media_payload'   => $payload,
                'status'          => $metaMessageId ? 'sent' : 'failed'
            ]);

            if (in_array($type, ['text', 'template', 'interactive', 'document', 'image'])) {
                $summary = $content ?: ucfirst($type) . ' message';
                $conversation->update([
                    'last_message' => 'You: ' . mb_substr($summary, 0, 100),
                    'last_message_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to log outbound message: " . $e->getMessage());
        }
    }
}
