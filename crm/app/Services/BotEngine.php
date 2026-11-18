<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Lead;
use App\Models\Flow;
use App\Models\FlowNode;
use App\Models\FlowEdge;
use App\Models\BotMessage;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotEngine
{
    public function processMessage($messageData, $contactsData)
    {
        try {
            $from      = $messageData['from'];
            $messageId = $messageData['id'];
            $type      = $messageData['type'];
            
            Log::info("BotEngine: Started processing message from {$from} of type {$type}");

        // ── Extract input payload ──────────────────────────────────────
        $inputPayload = null;
        $inputText    = null;
        if ($type === 'text') {
            $inputText    = $messageData['text']['body'] ?? '';
            $inputPayload = $inputText;
        } elseif ($type === 'interactive') {
            $interactive = $messageData['interactive'];
            if (isset($interactive['button_reply'])) {
                $inputPayload = $interactive['button_reply']['id'];
            } elseif (isset($interactive['list_reply'])) {
                $inputPayload = $interactive['list_reply']['id'];
            }
        }

        // ── Customer Name ──────────────────────────────────────────────
        $customerName = null;
        foreach ($contactsData as $contact) {
            if ($contact['wa_id'] == $from && isset($contact['profile']['name'])) {
                $customerName = $contact['profile']['name'];
            }
        }

        // ── Find or Create Conversation & Lead ─────────────────────────
        $conversation = Conversation::firstOrCreate(
            ['phone_number' => $from],
            ['customer_name' => $customerName, 'status' => 'bot']
        );

        $lead = Lead::firstOrCreate(
            ['phone_number' => $from],
            ['customer_name' => $customerName, 'conversation_id' => $conversation->id]
        );

        if ($customerName && !$lead->customer_name) {
            $lead->customer_name = $customerName;
            $lead->save();
        }

        // ── OPT-OUT DETECTION ──
        $msgBody = strtolower(trim($messageData['text']['body'] ?? ''));
        $optOutKeywords = ['stop', 'unsubscribe', 'remove me', 'do not message', 'opt out'];
        if (in_array($msgBody, $optOutKeywords, true)) {
            $lead->marketing_opt_out = true;
            $lead->opted_out_at = now();
            $lead->opt_out_reason = 'User sent keyword: ' . $msgBody;
            $lead->save();

            $this->sendText($from, "You have been successfully unsubscribed from marketing messages.");
            Log::info("BotEngine: Lead opted out via keyword.", ['phone' => $from, 'keyword' => $msgBody]);
            return; // Stop further bot processing for this keyword
        }

        // ── Save incoming message ──────────────────────────────────────
        Message::firstOrCreate(
            ['meta_message_id' => $messageId],
            [
                'conversation_id' => $conversation->id,
                'direction'       => 'inbound',
                'type'            => $type,
                'content'         => $inputPayload ?? $inputText,
                'media_payload'   => $messageData,
                'status'          => 'delivered',
            ]
        );

        $isExpired = false;
        if ($conversation->last_message_at && $conversation->last_message_at->diffInHours(now()) >= 2) {
            $isExpired = true;
        }

        $conversation->unread_count   += 1;
        $conversation->last_message    = $inputPayload ?? $inputText;
        $conversation->last_message_at = now();
        $conversation->save();

        // ── Handle session expiration ───────────────────────────────────
        if ($isExpired) {
            $conversation->current_node_id = null;
            $conversation->status = 'bot';
            $conversation->save();
            $this->startDefaultFlow($conversation, $lead);
            return;
        }

        // ── Handle text commands (reset to welcome flow) ────────────────
        $textLower = strtolower(trim($inputText ?? ''));
        $greetings = ['menu', 'hi', 'hii', 'hiii', 'hello', 'start', 'restart', 'main menu', 'hey', 'heya', 'hola', 'good morning', 'good afternoon', 'good evening', 'help', 'info', 'test'];
        
        // If the text is in the greetings list, OR if it matches regex for 'hii'
        if (in_array($textLower, $greetings) || preg_match('/^(hi+|hello|hey|menu|main\s*menu|start)$/i', $textLower)) {
            $conversation->current_node_id = null;
            $conversation->status = 'bot'; // Force back to bot mode
            $conversation->save();
            $this->startDefaultFlow($conversation, $lead);
            return;
        }

        // ── If human mode — do not automate ───────────────────────────
        if ($conversation->status !== 'bot') {
            Log::info("BotEngine: Conversation {$conversation->id} is in human mode, skipping.");
            return;
        }

        // ── Handle global payloads ────────────────────────────────────
        // MAIN_MENU → reset to welcome flow
        if ($inputPayload === 'MAIN_MENU') {
            $conversation->current_node_id = null;
            $conversation->status = 'bot';
            $conversation->save();
            $this->startDefaultFlow($conversation, $lead);
            return;
        }

        // EXPERT → human handover anywhere
        if ($inputPayload === 'EXPERT') {
            $this->triggerHumanHandover($conversation, $lead);
            return;
        }

        // SITE_VISIT_START → jump to site visit flow
        if ($inputPayload === 'SITE_VISIT_START') {
            $svFlow = Flow::where('group_name', 'GREEN_HILL_SITE_VISIT')->where('is_active', true)->first();
            if ($svFlow) {
                $startNode = $svFlow->startNode();
                if ($startNode) {
                    $this->executeNode($conversation, $lead, $startNode, $inputPayload);
                    return;
                }
            }
        }

        // ── State machine ─────────────────────────────────────────────
        $currentNode = null;
        if ($conversation->current_node_id) {
            $currentNode = FlowNode::find($conversation->current_node_id);
        }

        // No active node → start welcome flow
        if (!$currentNode) {
            $this->startDefaultFlow($conversation, $lead);
            return;
        }

        // ── Handle cross-flow payload routing ────────────────────────
        // GHG_MAIN → start GHG Projects flow
        if ($inputPayload === 'GHG_MAIN') {
            $lead->business_vertical = 'Green Hill Group';
            $lead->save();
            $flow = Flow::where('group_name', 'GREEN_HILL_PROJECTS')->where('is_active', true)->first();
            if ($flow) {
                $node = $flow->startNode();
                if ($node) {
                    $this->executeNode($conversation, $lead, $node, $inputPayload);
                    return;
                }
            }
        }

        // GH_MAIN → start Green Homes flow
        if ($inputPayload === 'GH_MAIN') {
            $lead->business_vertical = 'Green Homes';
            $lead->save();
            $flow = Flow::where('group_name', 'GREEN_HOMES_MAIN')->where('is_active', true)->first();
            if ($flow) {
                $node = $flow->startNode();
                if ($node) {
                    $this->executeNode($conversation, $lead, $node, $inputPayload);
                    return;
                }
            }
        }

        // BROCHURE_* → Download Brochure
        if ($inputPayload && str_starts_with($inputPayload, 'BROCHURE_')) {
            $projectSlug = str_replace('BROCHURE_', '', $inputPayload);
            $filename = strtoupper($projectSlug) . '.pdf';
            $url = "https://crm.thegreen.homes/brochures/{$filename}";
            $this->sendDocument($conversation->phone_number, $url, $filename);
            
            // Re-prompt the same current node menu so they can continue navigating
            if ($currentNode) {
                $this->executeNode($conversation, $lead, $currentNode, null);
            }
            return;
        }

        // GH_BUILD → start Build My Home flow
        if ($inputPayload === 'GH_BUILD') {
            $flow = Flow::where('group_name', 'GREEN_HOMES_BUILD')->where('is_active', true)->first();
            if ($flow) {
                $node = $flow->startNode();
                if ($node) {
                    $this->executeNode($conversation, $lead, $node, $inputPayload);
                    return;
                }
            }
        }

        // GH_QUOTE → start Quote flow
        if ($inputPayload === 'GH_QUOTE') {
            $flow = Flow::where('group_name', 'GREEN_HOMES_QUOTE')->where('is_active', true)->first();
            if ($flow) {
                $node = $flow->startNode();
                if ($node) {
                    $this->executeNode($conversation, $lead, $node, $inputPayload);
                    return;
                }
            }
        }

        // ── Text-input capture (wildcard edge) ────────────────────────
        // If current node expects text input and customer typed text, save it
        if ($currentNode->input_type === 'text_input' && $inputText !== null && $currentNode->expected_lead_field) {
            $this->saveLeadField($lead, $currentNode->expected_lead_field, $inputText);
            // Advance via wildcard edge
            $edge = FlowEdge::where('source_node_id', $currentNode->id)
                ->where('condition_payload', '*')->first();
            if ($edge && $edge->target_node_id) {
                $nextNode = FlowNode::find($edge->target_node_id);
                if ($nextNode) {
                    $this->executeNode($conversation, $lead, $nextNode, $inputPayload);
                    return;
                }
            }
        }

        // ── Button/List matching ───────────────────────────────────────
        $edge = FlowEdge::where('source_node_id', $currentNode->id)
            ->where(function ($query) use ($inputPayload) {
                $query->where('condition_payload', $inputPayload)
                      ->orWhere('condition_payload', '*');
            })
            ->orderByRaw("CASE WHEN condition_payload = ? THEN 0 ELSE 1 END", [$inputPayload])
            ->first();

        if ($edge) {
            // Save lead field value from edge if defined
            if ($edge->save_value && $currentNode->expected_lead_field) {
                $this->saveLeadField($lead, $currentNode->expected_lead_field, $edge->save_value);
            } elseif ($inputText && $currentNode->expected_lead_field && $edge->condition_payload === '*') {
                $this->saveLeadField($lead, $currentNode->expected_lead_field, $inputText);
            }

            if ($edge->target_node_id) {
                $nextNode = FlowNode::find($edge->target_node_id);
                if ($nextNode) {
                    $this->executeNode($conversation, $lead, $nextNode, $inputPayload);
                    return;
                }
            }
        } else {
            // Unknown input
            $expertPhone = \App\Models\Setting::get('expert_phone_display', '+91 93393 61344');
            $this->sendText($from, "For further enquiry or clarification, please contact our Sales Executive: {$expertPhone}");
        }
        
        Log::info("BotEngine: Finished processing message.");
        } catch (\Throwable $e) {
            Log::error("BotEngine processMessage Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    private function startDefaultFlow(Conversation $conversation, Lead $lead): void
    {
        $defaultFlow = Flow::where('is_active', true)->where('is_default_welcome', true)->first();
        if ($defaultFlow) {
            $firstNode = $defaultFlow->startNode();
            if ($firstNode) {
                $this->executeNode($conversation, $lead, $firstNode, null);
            }
        }
    }

    private function executeNode(Conversation $conversation, Lead $lead, FlowNode $node, $inputPayload): void
    {
        $botMessage = $node->botMessage;

        // Process any field updates defined on the node
        if ($node->update_lead_field && $conversation->last_input_value) {
            $this->saveLeadField($lead, $node->update_lead_field, $conversation->last_input_value);
        }

        // Update conversation state
        $conversation->current_node_id = $node->id;

        if ($node->triggers_human_handover) {
            $conversation->status = 'human';
            $lead->human_requested = true;
            $lead->save();
            Log::info("BotEngine: Human handover triggered for conversation {$conversation->id}");
            
            $expertPhone = Setting::get('expert_phone_display', '+91 93393 61344');
            $expertPhoneIntl = Setting::get('expert_phone', '+919339361344');
            
            // Notify the sales expert
            $expertMessage = "🚨 *New Lead Alert!*\n\n"
                           . "👤 Name: " . ($lead->customer_name ?: 'Unknown') . "\n"
                           . "📱 Phone: " . $conversation->phone_number . "\n"
                           . "🏢 Project: " . ($lead->project ?: 'N/A') . "\n"
                           . "🏠 Config: " . ($lead->configuration ?: 'N/A') . "\n\n"
                           . "Please contact them ASAP.";
            
            $expertTargetPhone = ltrim($expertPhoneIntl, '+');
            $this->sendText($expertTargetPhone, $expertMessage);

            // Send the contact card to the customer
            $this->sendContact($conversation->phone_number, $expertPhoneIntl, "Sales Expert");
        }
        $conversation->save();

        // Get the BotMessage for this node
        $botMessage = null;
        if ($node->bot_message_id) {
            $botMessage = BotMessage::find($node->bot_message_id);
        }

        if ($botMessage) {
            $this->deliverBotMessage($conversation->phone_number, $botMessage, $lead);
        } elseif ($node->type === 'template' && $node->template_name) {
            $this->sendTemplate($conversation->phone_number, $node->template_name);
        } elseif ($node->type === 'text' && $node->text_content) {
            $this->sendText($conversation->phone_number, $this->substituteVariables($node->text_content, $lead));
        }

        // Trigger Site Visit Notification to Sales
        $isSiteVisit = false;
        if ($botMessage && ($botMessage->internal_name === 'SITE_VISIT_CONFIRMED' || str_contains($botMessage->body_text, 'Site Visit Request Received'))) {
            $isSiteVisit = true;
        } elseif ($node->type === 'text' && str_contains($node->text_content, 'Site Visit Request Received')) {
            $isSiteVisit = true;
        }

        if ($isSiteVisit) {
            $expertPhoneIntl = \App\Models\Setting::get('expert_phone', '+919339361344');
            $salesPhone = ltrim($expertPhoneIntl, '+');
            
            $visitAlert = "🚗 *Site Visit Requested!*\n\n"
                        . "👤 Name: " . ($lead->customer_name ?: 'Unknown') . "\n"
                        . "📱 Phone: " . $conversation->phone_number . "\n"
                        . "🏢 Project: " . ($lead->project ?: 'N/A') . "\n\n"
                        . "Please check the CRM and contact the lead to confirm the timing.";
            $this->sendText($salesPhone, $visitAlert);
            Log::info("BotEngine: Site Visit alert sent to sales for {$conversation->phone_number}");
        }
    }

    private function deliverBotMessage(string $phone, BotMessage $msg, Lead $lead): void
    {
        $body    = $this->substituteVariables($msg->body_text ?? '', $lead);
        
        // Add global footer to every message for direct sales contact
        $expertPhone = \App\Models\Setting::get('expert_phone_display', '+91 93393 61344');
        $body .= "\n\nConnect with Sales: {$expertPhone}";

        $header  = $msg->header_text;
        $footer  = $msg->footer_text;
        $buttons = $msg->buttons ?? [];

        switch ($msg->message_type) {
            case 'template':
                // Use sendTemplate only when meta_template_name is set and approved
                if ($msg->meta_template_name && $msg->meta_template_status === 'approved') {
                    $this->sendTemplate($phone, $msg->meta_template_name, $msg->meta_template_language ?? 'en');
                } else {
                    // Fallback: send as interactive if within session
                    $this->sendInteractive($phone, $header, $body, $footer, $buttons);
                }
                break;

            case 'list':
                $sections = is_string($msg->list_sections) ? json_decode($msg->list_sections, true) : $msg->list_sections;
                if (!empty($sections)) {
                    $locationMap = [
                        'green exotica' => 'Checkpost, Siliguri',
                        'green elegance' => 'Matigara, Siliguri',
                        'green retreat' => 'Shastri Nagar, Siliguri',
                        'green ville' => 'Iskon Road, Siliguri',
                        'green delight' => 'Shastri Nagar, Siliguri',
                        'silo kutir' => 'College Para, Siliguri',
                        'aura' => 'Shastri Nagar, Siliguri',
                        'green aura' => 'Shastri Nagar, Siliguri',
                        'europa' => 'Dagapur, Siliguri',
                    ];

                    foreach ($sections as &$sec) {
                        if (isset($sec['rows']) && is_array($sec['rows'])) {
                            foreach ($sec['rows'] as &$row) {
                                $titleLower = strtolower(trim($row['title']));
                                if (isset($locationMap[$titleLower])) {
                                    $row['description'] = $locationMap[$titleLower];
                                }
                            }
                        }
                    }
                    unset($sec, $row);

                    $this->sendList($phone, $header, $body, $footer, $msg->list_button_label ?? 'Options', $sections);
                }
                break;

            case 'interactive':
            default:
                if (!empty($buttons)) {
                    $headerImageUrl = null;
                    if ($msg->internal_name === 'WELCOME_MAIN') {
                        $headerImageUrl = 'https://crm.thegreen.homes/images/welcome-banner.jpg';
                    }
                    $this->sendInteractive($phone, $header, $body, $footer, $buttons, $headerImageUrl);
                } else {
                    $this->sendText($phone, $body);
                }
                break;
        }
    }

    private function triggerHumanHandover(Conversation $conversation, Lead $lead): void
    {
        $conversation->status = 'human';
        $conversation->save();

        $lead->human_requested = true;
        $lead->save();

        $expertPhone = Setting::get('expert_phone_display', '+91 93393 61344');
        $expertPhoneIntl = Setting::get('expert_phone', '+919339361344');

        $body = "You have been connected with our team.\n\nA Green Hill Group expert will reach out to you shortly.\n\n📞 You can also call us directly:\n{$expertPhone}";
        $buttons = [
            ['label' => '📞 Call Our Expert', 'payload' => "tel:{$expertPhoneIntl}", 'action_type' => 'call', 'action_value' => $expertPhoneIntl],
        ];
        $this->sendInteractive($conversation->phone_number, null, $body, null, $buttons);

        Log::info("BotEngine: Human handover for {$conversation->phone_number}", [
            'customer'  => $lead->customer_name,
            'project'   => $lead->project,
            'business'  => $lead->business_vertical,
        ]);
        
        // Notify the sales expert
        $expertMessage = "🚨 *New Lead Alert!*\n\n"
                       . "👤 Name: " . ($lead->customer_name ?: 'Unknown') . "\n"
                       . "📱 Phone: " . $conversation->phone_number . "\n"
                       . "🏢 Project: " . ($lead->project ?: 'N/A') . "\n"
                       . "🏠 Config: " . ($lead->configuration ?: 'N/A') . "\n\n"
                       . "Please contact them ASAP.";
        
        $expertTargetPhone = ltrim($expertPhoneIntl, '+');
        $this->sendText($expertTargetPhone, $expertMessage);
    }

    private function saveLeadField(Lead $lead, string $field, string $value): void
    {
        if (in_array($field, $lead->getFillable())) {
            $lead->{$field} = $value;
            $lead->save();
            Log::info("BotEngine: Saved lead field '{$field}' = '{$value}' for lead {$lead->id}");
            
            // Sync to Google Sheets if configured (run asynchronously after response to avoid delaying bot replies)
            $webhookUrl = env('GOOGLE_SHEETS_WEBHOOK_URL');
            if ($webhookUrl) {
                $leadData = [
                    'name' => $lead->customer_name,
                    'phone' => $lead->phone_number,
                    'vertical' => $lead->business_vertical,
                    'requirement' => $lead->project,
                    'sub_requirement' => $lead->configuration,
                    'assigned_to' => $lead->assigned_to,
                    'owns_land' => $lead->owns_land,
                    'land_location' => $lead->land_location,
                    'plot_size' => $lead->plot_size,
                    'home_type' => $lead->home_type,
                    'floors' => $lead->floors,
                    'builtup_area' => $lead->builtup_area,
                    'budget' => $lead->budget,
                    'timeline' => $lead->timeline,
                    'site_visit_date' => $lead->site_visit_date,
                    'site_visit_time' => $lead->site_visit_time,
                ];
                
                dispatch(function () use ($webhookUrl, $leadData) {
                    try {
                        \Illuminate\Support\Facades\Http::timeout(5)->post($webhookUrl, $leadData);
                    } catch (\Exception $e) {
                        Log::error("Failed to sync lead to Google Sheets: " . $e->getMessage());
                    }
                })->afterResponse();
            }
        }
    }

    private function substituteVariables(string $text, Lead $lead): string
    {
        $replacements = [
            '{{name}}'       => $lead->customer_name ?? 'there',
            '{{project}}'    => $lead->project ?? '',
            '{{config}}'     => $lead->configuration ?? '',
            '{{business}}'   => $lead->business_vertical ?? '',
            '{{phone}}'      => $lead->phone_number ?? '',
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    // ── WhatsApp API Methods ───────────────────────────────────────────────────
    private function getWhatsAppService(): WhatsAppService
    {
        return app(WhatsAppService::class);
    }

    private function sendImage(string $phone, string $imageUrl): void
    {
        $this->getWhatsAppService()->sendImage($phone, $imageUrl);
    }

    private function sendInteractive(string $phone, ?string $header, string $body, ?string $footer, array $buttons, ?string $headerImageUrl = null): void
    {
        // BotEngine's interactive logic allows headerImageUrl
        if ($headerImageUrl) {
            $token  = env('META_ACCESS_TOKEN');
            $phoneId = env('META_PHONE_NUMBER_ID');
            if (!$token || !$phoneId) return;

            $msgButtons = [];
            foreach (array_slice($buttons, 0, 3) as $btn) {
                if (($btn['action_type'] ?? 'reply') === 'call') continue;
                $msgButtons[] = [
                    'type'  => 'reply',
                    'reply' => ['id' => $btn['payload'], 'title' => mb_substr($btn['label'], 0, 20)],
                ];
            }

            if (empty($msgButtons)) {
                $this->sendText($phone, $body);
                return;
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $phone,
                'type'              => 'interactive',
                'interactive'       => [
                    'type' => 'button',
                    'header' => ['type' => 'image', 'image' => ['link' => $headerImageUrl]],
                    'body' => ['text' => mb_substr(($header ? "*".trim($header)."*\n\n" : "") . $body, 0, 1024)],
                    'action' => ['buttons' => $msgButtons],
                ],
            ];

            if ($footer) $payload['interactive']['footer'] = ['text' => mb_substr($footer, 0, 60)];

            $response = Http::withoutVerifying()->withToken($token)
                ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", $payload);
            
            // Add a public method to WhatsAppService for manual logging or just replicate logic
            try {
                $conversation = \App\Models\Conversation::firstOrCreate(['phone_number' => $phone], ['status' => 'bot']);
                $metaMessageId = $response->successful() ? ($response->json()['messages'][0]['id'] ?? null) : null;
                \App\Models\Message::create([
                    'conversation_id' => $conversation->id,
                    'meta_message_id' => $metaMessageId,
                    'direction'       => 'outbound',
                    'type'            => 'interactive',
                    'content'         => $body,
                    'media_payload'   => $payload,
                    'status'          => $metaMessageId ? 'sent' : 'failed'
                ]);
            } catch (\Exception $e) {}

        } else {
            $this->getWhatsAppService()->sendInteractive($phone, $header, $body, $footer, $buttons);
        }
    }

    private function sendList(string $phone, ?string $header, string $body, ?string $footer, string $buttonLabel, array $sections): void
    {
        $this->getWhatsAppService()->sendList($phone, $header, $body, $footer, $buttonLabel, $sections);
    }

    public function sendText(string $phoneNumber, string $text): void
    {
        $this->getWhatsAppService()->sendText($phoneNumber, $text);
    }

    private function sendDocument(string $phoneNumber, string $documentUrl, string $filename): void
    {
        $this->getWhatsAppService()->sendDocument($phoneNumber, $documentUrl, $filename);
    }

    public function sendTemplate(string $phoneNumber, string $templateName, string $language = 'en', array $components = []): void
    {
        $this->getWhatsAppService()->sendTemplate($phoneNumber, $templateName, $language, $components);
    }
}
