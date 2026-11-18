<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppService;

class LeadWhatsAppApiController extends Controller
{
    /**
     * Shared secret key verification for Project Control
     */
    protected function authorizeRequest(Request $request): bool
    {
        $configuredKey = env('PROJECT_CONTROL_API_KEY', 'gh_pc_crm_whatsapp_sync_2026');
        
        $token = $request->bearerToken();
        if (!$token) {
            $token = $request->header('X-Project-Control-Key');
        }
        if (!$token) {
            $token = $request->input('api_key');
        }

        return !empty($token) && hash_equals($configuredKey, $token);
    }

    /**
     * List all approved Meta WhatsApp templates for Project Control
     */
    public function templates(Request $request, WhatsAppService $whatsapp)
    {
        if (!$this->authorizeRequest($request)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized access'], 401);
        }

        try {
            $rawTemplates = $whatsapp->getTemplates();
            $approved = [];

            foreach ($rawTemplates as $tpl) {
                if (($tpl['status'] ?? '') !== 'APPROVED' && $tpl['name'] !== 'welcome_green_hill_group') {
                    continue;
                }

                $headerFormat = null;
                $bodyText = '';
                $hasVariables = false;
                $buttons = [];

                foreach ($tpl['components'] ?? [] as $comp) {
                    if ($comp['type'] === 'HEADER') {
                        $headerFormat = $comp['format'] ?? 'TEXT';
                    } elseif ($comp['type'] === 'BODY') {
                        $bodyText = $comp['text'] ?? '';
                        if (preg_match('/\{\{\d+\}\}/', $bodyText)) {
                            $hasVariables = true;
                        }
                    } elseif ($comp['type'] === 'BUTTONS') {
                        foreach ($comp['buttons'] ?? [] as $b) {
                            $buttons[] = [
                                'type' => $b['type'] ?? '',
                                'text' => $b['text'] ?? ''
                            ];
                        }
                    }
                }

                $imageUrl = null;
                if ($headerFormat === 'IMAGE') {
                    $imageUrl = 'https://crm.thegreen.homes/images/welcome-banner.jpg';
                }

                $approved[] = [
                    'name'              => $tpl['name'],
                    'language'          => $tpl['language'] ?? 'en',
                    'header_format'     => $headerFormat,
                    'header_image_url'  => $imageUrl,
                    'body_text'         => $bodyText,
                    'has_variables'     => $hasVariables,
                    'buttons'           => $buttons,
                    'is_recommended'    => in_array($tpl['name'], ['welcome_green_hill_group', 'ghg_and_gh_welcome', 'property_brochure_followup'])
                ];
            }

            // Sort so welcome_green_hill_group is first
            usort($approved, function($a, $b) {
                if ($a['name'] === 'welcome_green_hill_group') return -1;
                if ($b['name'] === 'welcome_green_hill_group') return 1;
                return 0;
            });

            return response()->json([
                'success'   => true,
                'count'     => count($approved),
                'templates' => $approved
            ]);
        } catch (\Exception $e) {
            Log::error('LeadWhatsAppApiController templates error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send an official Meta WhatsApp template message to a lead
     */
    public function send(Request $request, WhatsAppService $whatsapp)
    {
        if (!$this->authorizeRequest($request)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized access'], 401);
        }

        $phone = preg_replace('/[^0-9]/', '', (string)$request->input('phone', ''));
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        if (empty($phone) || strlen($phone) < 10) {
            return response()->json(['success' => false, 'error' => 'A valid 10-digit phone number is required.'], 422);
        }

        $templateName = trim((string)$request->input('template_name', ''));
        if (empty($templateName)) {
            return response()->json(['success' => false, 'error' => 'Template name is required.'], 422);
        }

        $language = $request->input('language', 'en');
        $customerName = trim((string)$request->input('customer_name', ''));
        $documentUrl = $request->input('document_url');
        $documentFilename = $request->input('document_filename', 'Green_Hill_Group_Brochure.pdf');
        $customParameters = $request->input('parameters', []);

        // Build components according to template
        $components = [];

        // Special handling for known approved templates
        if ($templateName === 'welcome_green_hill_group') {
            $imgUrl = $request->input('image_url') ?: 'https://crm.thegreen.homes/images/welcome-banner.jpg';
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type'  => 'image',
                        'image' => [
                            'link' => $imgUrl
                        ]
                    ]
                ]
            ];
        } elseif ($templateName === 'property_brochure_followup') {
            // Document header (default to official brochure if not provided)
            $docUrl = $documentUrl ?: 'https://thegreen.homes/downloads/Green_Hill_Group_Brochure.pdf';
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type'     => 'document',
                        'document' => [
                            'link'     => $docUrl,
                            'filename' => $documentFilename ?: 'Green_Hill_Group_Brochure.pdf'
                        ]
                    ]
                ]
            ];

            // Body {{1}} is Customer Name
            $nameVal = !empty($customerName) ? $customerName : 'Valued Client';
            $components[] = [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $nameVal]
                ]
            ];
        } elseif ($templateName === 'ghg_and_gh_welcome') {
            // ghg_and_gh_welcome has no variables and static text header
            $components = [];
        } else {
            // Generic dynamic template handling
            if (!empty($documentUrl)) {
                $components[] = [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type'     => 'document',
                            'document' => [
                                'link'     => $documentUrl,
                                'filename' => $documentFilename ?: 'Document.pdf'
                            ]
                        ]
                    ]
                ];
            }

            if (!empty($customParameters) && is_array($customParameters)) {
                $params = [];
                foreach ($customParameters as $p) {
                    $params[] = ['type' => 'text', 'text' => (string)$p];
                }
                $components[] = [
                    'type' => 'body',
                    'parameters' => $params
                ];
            } elseif (!empty($customerName)) {
                $components[] = [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $customerName]
                    ]
                ];
            }
        }

        try {
            $response = $whatsapp->sendTemplate($phone, $templateName, $language, $components);

            // If template is still pending approval or fails, fallback to interactive message with banner
            if ((!$response || !isset($response['messages'][0]['id'])) && $templateName === 'welcome_green_hill_group') {
                Log::info("welcome_green_hill_group template returned non-success, attempting interactive dispatch fallback to {$phone}");
                $body = "Welcome to Green Hill Group!\n\nThank you for reaching out to us. We're here to help you find a home that fits your lifestyle and requirements.\n\nHow may we assist you today?\n\nConnect with Sales: +91 93393 61344";
                $interactivePayload = [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $phone,
                    'type'              => 'interactive',
                    'interactive'       => [
                        'type' => 'button',
                        'header' => [
                            'type' => 'image',
                            'image' => ['link' => 'https://crm.thegreen.homes/images/welcome-banner.jpg']
                        ],
                        'body' => ['text' => $body],
                        'action' => [
                            'buttons' => [
                                ['type' => 'reply', 'reply' => ['id' => 'GHG_PROJECTS', 'title' => 'Explore Properties']],
                                ['type' => 'reply', 'reply' => ['id' => 'SITE_VISIT_START', 'title' => 'Book Site Visit']],
                                ['type' => 'reply', 'reply' => ['id' => 'EXPERT', 'title' => 'Talk to Sales']]
                            ]
                        ]
                    ]
                ];
                $token = env('META_ACCESS_TOKEN');
                $phoneId = env('META_PHONE_NUMBER_ID');
                $intRes = \Illuminate\Support\Facades\Http::withoutVerifying()->withToken($token)
                    ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", $interactivePayload);
                if ($intRes->successful() && isset($intRes->json()['messages'][0]['id'])) {
                    $response = $intRes->json();
                    Log::info("Interactive welcome message sent successfully as fallback for {$phone}");
                }
            }

            if ($response && isset($response['messages'][0]['id'])) {
                $metaMessageId = $response['messages'][0]['id'];
                Log::info("Lead WhatsApp sent successfully from Project Control to {$phone} with template {$templateName} (ID: {$metaMessageId})");
                
                return response()->json([
                    'success'         => true,
                    'meta_message_id' => $metaMessageId,
                    'phone'           => $phone,
                    'template'        => $templateName,
                    'status'          => 'sent',
                    'response'        => $response
                ]);
            }

            $errorMessage = $response['error']['message'] ?? 'Meta API rejected message dispatch';
            Log::error("Lead WhatsApp send failed for {$phone}: " . json_encode($response));

            return response()->json([
                'success' => false,
                'error'   => $errorMessage,
                'meta'    => $response
            ], 400);

        } catch (\Exception $e) {
            Log::error("Exception in LeadWhatsAppApiController send: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send an automated Task Assignment WhatsApp alert for Project Control
     */
    public function sendTaskAlert(Request $request, WhatsAppService $whatsapp)
    {
        if (!$this->authorizeRequest($request)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized access'], 401);
        }

        $phone = preg_replace('/[^0-9]/', '', (string)$request->input('phone', ''));
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        if (empty($phone) || strlen($phone) < 10) {
            return response()->json(['success' => false, 'error' => 'A valid 10-digit phone number is required.'], 422);
        }

        $assigneeName = trim((string)$request->input('assignee_name', 'Team Member'));
        $assignerName = trim((string)$request->input('assigner_name', 'Management'));
        $category     = trim((string)$request->input('category', 'General'));
        $taskTitle    = trim((string)$request->input('task_title', 'New Task'));
        $description  = trim((string)$request->input('description', 'No additional details provided.'));
        $priority     = trim((string)$request->input('priority', 'Medium'));
        $frequency    = trim((string)$request->input('frequency', 'Once'));
        $dueDate      = trim((string)$request->input('due_date', 'Not specified'));

        // Format exact message requested by the user with direct link to Project Control:
        $taskUrl = "https://projectcontrol.thegreen.homes/tasks";
        $message = "Dear {$assigneeName},\n\n"
                 . "{$assignerName} has assigned you a new task.\n\n"
                 . "Category - {$category}\n"
                 . "Task - {$taskTitle}\n"
                 . "Description - {$description}\n"
                 . "Priority - {$priority}\n"
                 . "Frequency - {$frequency}\n"
                 . "Due Date - {$dueDate}\n\n"
                 . "🔗 Open in Project Control:\n{$taskUrl}\n\n"
                 . "This is an automated notification generated by Project Control";

        $response = null;
        $dispatchMode = 'template';

        // Sanitize parameters for Meta API (Meta rejects parameters containing newlines or tabs)
        $cleanAssignee = mb_substr(trim(preg_replace('/\s+/', ' ', $assigneeName)), 0, 100);
        $cleanAssigner = mb_substr(trim(preg_replace('/\s+/', ' ', $assignerName)), 0, 100);
        $cleanCategory = mb_substr(trim(preg_replace('/\s+/', ' ', $category)), 0, 100);
        $cleanTitle    = mb_substr(trim(preg_replace('/\s+/', ' ', $taskTitle)), 0, 120);
        $cleanDesc     = mb_substr(trim(preg_replace('/\s+/', ' ', $description)), 0, 750);
        $cleanPriority = mb_substr(trim(preg_replace('/\s+/', ' ', $priority)), 0, 50);
        $cleanFreq     = mb_substr(trim(preg_replace('/\s+/', ' ', $frequency)), 0, 50);
        $cleanDueDate  = mb_substr(trim(preg_replace('/\s+/', ' ', $dueDate)), 0, 80);

        // 1. Try v2 template with interactive button and link built-in
        $v2Components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $cleanAssignee],
                    ['type' => 'text', 'text' => $cleanAssigner],
                    ['type' => 'text', 'text' => $cleanCategory],
                    ['type' => 'text', 'text' => $cleanTitle],
                    ['type' => 'text', 'text' => $cleanDesc],
                    ['type' => 'text', 'text' => $cleanPriority],
                    ['type' => 'text', 'text' => $cleanFreq],
                    ['type' => 'text', 'text' => $cleanDueDate],
                ]
            ]
        ];

        try {
            $tplRes = $whatsapp->sendTemplate($phone, 'project_control_task_alert_v2', 'en', $v2Components);
            if ($tplRes && isset($tplRes['messages'][0]['id'])) {
                $response = $tplRes;
                $dispatchMode = 'template:project_control_task_alert_v2 (with button)';
            }
        } catch (\Exception $e) {
            Log::info("project_control_task_alert_v2 not yet ready: " . $e->getMessage());
        }

        // 2. Fallback to v1 approved template (with link included in description)
        if (!$response || !isset($response['messages'][0]['id'])) {
            $descWithLink = $cleanDesc . " | View: {$taskUrl}";
            $v1Components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $cleanAssignee],
                        ['type' => 'text', 'text' => $cleanAssigner],
                        ['type' => 'text', 'text' => $cleanCategory],
                        ['type' => 'text', 'text' => $cleanTitle],
                        ['type' => 'text', 'text' => mb_substr($descWithLink, 0, 900)],
                        ['type' => 'text', 'text' => $cleanPriority],
                        ['type' => 'text', 'text' => $cleanFreq],
                        ['type' => 'text', 'text' => $cleanDueDate],
                    ]
                ]
            ];

            try {
                $tplRes = $whatsapp->sendTemplate($phone, 'project_control_task_alert', 'en', $v1Components);
                if ($tplRes && isset($tplRes['messages'][0]['id'])) {
                    $response = $tplRes;
                    $dispatchMode = 'template:project_control_task_alert (with direct link)';
                } else {
                    Log::warning("project_control_task_alert fallback returned: " . json_encode($tplRes));
                    if (isset($tplRes['error'])) {
                        $response = $tplRes;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("WhatsApp template exception: " . $e->getMessage());
            }
        }

        // 3. Fallback to direct sendText
        if (!$response || !isset($response['messages'][0]['id'])) {
            try {
                $textRes = $whatsapp->sendText($phone, $message);
                if ($textRes && isset($textRes['messages'][0]['id'])) {
                    $response = $textRes;
                    $dispatchMode = 'text';
                } else {
                    Log::error("WhatsApp sendText fallback also failed for {$phone}: " . json_encode($textRes));
                    if (!$response && isset($textRes['error'])) {
                        $response = $textRes;
                    }
                }
            } catch (\Exception $e) {
                Log::error("WhatsApp sendText exception: " . $e->getMessage());
            }
        }

        if ($response && isset($response['messages'][0]['id'])) {
            $metaId = $response['messages'][0]['id'];
            Log::info("Task assignment WhatsApp alert successfully delivered to {$phone} (Meta ID: {$metaId}, mode: {$dispatchMode})");
            return response()->json([
                'success'         => true,
                'meta_message_id' => $metaId,
                'mode'            => $dispatchMode,
                'phone'           => $phone,
                'assignee'        => $assigneeName,
                'task'            => $taskTitle,
                'message_preview' => $message
            ]);
        }

        $errorMsg = $response['error']['message'] ?? 'Unable to deliver WhatsApp message via Meta Cloud API';
        return response()->json([
            'success' => false,
            'error'   => $errorMsg,
            'details' => $response
        ], 400);
    }

    /**
     * Send an automated Daily Task Digest WhatsApp notification
     */
    public function sendTaskDigest(Request $request, WhatsAppService $whatsapp)
    {
        if (!$this->authorizeRequest($request)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized access'], 401);
        }

        $phone = preg_replace('/[^0-9]/', '', (string)$request->input('phone', ''));
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        if (empty($phone) || strlen($phone) < 10) {
            return response()->json(['success' => false, 'error' => 'A valid 10-digit phone number is required.'], 422);
        }

        $assigneeName = trim((string)$request->input('assignee_name', 'Team Member'));
        $overdue      = (string)(int)$request->input('overdue', 0);
        $pending      = (string)(int)$request->input('pending', 0);
        $inProgress   = (string)(int)$request->input('in_progress', 0);
        $thisWeek     = (string)(int)$request->input('this_week', 0);
        $taskUrl      = "https://projectcontrol.thegreen.homes/tasks";

        $message = "Dear {$assigneeName},\n\n"
                 . "Here's the latest update on your tasks for today:\n"
                 . "⛔ Overdue - {$overdue}\n"
                 . "🟡 Pending - {$pending}\n"
                 . "🟠 In Progress - {$inProgress}\n"
                 . "🔵 This Week - {$thisWeek}\n\n"
                 . "Please ensure timely completion of the tasks and update their status accordingly.\n\n"
                 . "🔗 View My Tasks:\n{$taskUrl}\n\n"
                 . "This is an automated notification generated by Project Control\nPlease do not reply";

        $response = null;
        $dispatchMode = 'template';

        $cleanAssignee = mb_substr(trim(preg_replace('/\s+/', ' ', $assigneeName)), 0, 100);

        // 1. Try project_control_task_digest template
        $digestComponents = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $cleanAssignee],
                    ['type' => 'text', 'text' => $overdue],
                    ['type' => 'text', 'text' => $pending],
                    ['type' => 'text', 'text' => $inProgress],
                    ['type' => 'text', 'text' => $thisWeek],
                ]
            ]
        ];

        try {
            $tplRes = $whatsapp->sendTemplate($phone, 'project_control_task_digest', 'en', $digestComponents);
            if ($tplRes && isset($tplRes['messages'][0]['id'])) {
                $response = $tplRes;
                $dispatchMode = 'template:project_control_task_digest';
            }
        } catch (\Exception $e) {
            Log::info("project_control_task_digest not yet ready: " . $e->getMessage());
        }

        // 2. Fallback to direct sendText
        if (!$response || !isset($response['messages'][0]['id'])) {
            try {
                $textRes = $whatsapp->sendText($phone, $message);
                if ($textRes && isset($textRes['messages'][0]['id'])) {
                    $response = $textRes;
                    $dispatchMode = 'text';
                } else {
                    Log::error("WhatsApp sendText fallback also failed for {$phone}: " . json_encode($textRes));
                    if (!$response && isset($textRes['error'])) {
                        $response = $textRes;
                    }
                }
            } catch (\Exception $e) {
                Log::error("WhatsApp sendText exception: " . $e->getMessage());
            }
        }

        if ($response && isset($response['messages'][0]['id'])) {
            $metaId = $response['messages'][0]['id'];
            Log::info("Task digest WhatsApp alert successfully delivered to {$phone} (Meta ID: {$metaId}, mode: {$dispatchMode})");
            return response()->json([
                'success'         => true,
                'meta_message_id' => $metaId,
                'mode'            => $dispatchMode,
                'phone'           => $phone,
                'assignee'        => $assigneeName,
                'overdue'         => $overdue,
                'pending'         => $pending,
                'in_progress'     => $inProgress,
                'this_week'       => $thisWeek,
                'message_preview' => $message
            ]);
        }

        $errorMsg = $response['error']['message'] ?? 'Unable to deliver WhatsApp digest via Meta Cloud API';
        return response()->json([
            'success' => false,
            'error'   => $errorMsg,
            'details' => $response
        ], 400);
    }

    /**
     * Send an automated Purchase Order Approval WhatsApp alert for Project Control
     */
    public function sendPoApprovalAlert(Request $request, WhatsAppService $whatsapp)
    {
        if (!$this->authorizeRequest($request)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized access'], 401);
        }

        $phone = preg_replace('/[^0-9]/', '', (string)$request->input('phone', ''));
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        if (empty($phone) || strlen($phone) < 10) {
            return response()->json(['success' => false, 'error' => 'A valid 10-digit phone number is required.'], 422);
        }

        $approverName = trim((string)$request->input('approver_name', 'Sir / Ma\'am'));
        $creatorName  = trim((string)$request->input('creator_name', 'Site Store / Procurement'));
        $poNumber     = trim((string)$request->input('po_number', 'PO-2026'));
        $projectName  = trim((string)$request->input('project_name', 'Construction Site'));
        $vendorName   = trim((string)$request->input('vendor_name', 'Material Supplier'));
        $amount       = trim((string)$request->input('amount', '0.00'));
        $poId         = (int)$request->input('po_id', 0);
        $poUrl        = "https://projectcontrol.thegreen.homes/finance/purchase-orders/{$poId}";

        // The short, clear message requested by the user:
        // "request for approval po please approv"
        $shortMessage = "Request for approval: PO {$poNumber} please approve.\n\n"
                      . "📌 Project: {$projectName}\n"
                      . "🏢 Vendor: {$vendorName}\n"
                      . "💰 Amount: ₹{$amount}\n"
                      . "👤 Raised by: {$creatorName}\n\n"
                      . "🔗 Review & Approve PO:\n{$poUrl}\n\n"
                      . "This is an automated notification from Project Control.";

        $response = null;
        $dispatchMode = 'template';

        // 1. Send via approved Meta template project_control_task_alert_v2
        $cleanApprover = mb_substr(trim(preg_replace('/\s+/', ' ', $approverName)), 0, 100);
        $cleanCreator  = mb_substr(trim(preg_replace('/\s+/', ' ', $creatorName)), 0, 100);
        $cleanCategory = "PO Approval Request";
        $cleanTitle    = mb_substr("Request for approval: {$poNumber}", 0, 120);
        $cleanDesc     = mb_substr("Request for approval: PO {$poNumber} please approve. Amount: ₹{$amount} for {$projectName} ({$vendorName}). Direct link: {$poUrl}", 0, 750);

        $v2Components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $cleanApprover],
                    ['type' => 'text', 'text' => $cleanCreator],
                    ['type' => 'text', 'text' => $cleanCategory],
                    ['type' => 'text', 'text' => $cleanTitle],
                    ['type' => 'text', 'text' => $cleanDesc],
                    ['type' => 'text', 'text' => 'High'],
                    ['type' => 'text', 'text' => 'Immediate'],
                    ['type' => 'text', 'text' => 'Approval Needed'],
                ]
            ]
        ];

        try {
            $tplRes = $whatsapp->sendTemplate($phone, 'project_control_task_alert_v2', 'en', $v2Components);
            if ($tplRes && isset($tplRes['messages'][0]['id'])) {
                $response = $tplRes;
                $dispatchMode = 'template:project_control_task_alert_v2';
            }
        } catch (\Exception $e) {
            Log::info("sendPoApprovalAlert v2 template error: " . $e->getMessage());
        }

        // 2. Fallback to v1 approved template
        if (!$response || !isset($response['messages'][0]['id'])) {
            try {
                $tplRes = $whatsapp->sendTemplate($phone, 'project_control_task_alert', 'en', $v2Components);
                if ($tplRes && isset($tplRes['messages'][0]['id'])) {
                    $response = $tplRes;
                    $dispatchMode = 'template:project_control_task_alert';
                }
            } catch (\Exception $e) {
                Log::info("sendPoApprovalAlert v1 template error: " . $e->getMessage());
            }
        }

        // 3. Fallback or parallel direct text send
        if (!$response || !isset($response['messages'][0]['id'])) {
            try {
                $textRes = $whatsapp->sendText($phone, $shortMessage);
                if ($textRes && isset($textRes['messages'][0]['id'])) {
                    $response = $textRes;
                    $dispatchMode = 'text';
                }
            } catch (\Exception $e) {
                Log::error("sendPoApprovalAlert sendText error: " . $e->getMessage());
            }
        }

        if ($response && isset($response['messages'][0]['id'])) {
            $metaId = $response['messages'][0]['id'];
            Log::info("PO approval WhatsApp alert delivered to {$phone} (Meta ID: {$metaId}, mode: {$dispatchMode})");
            return response()->json([
                'success'         => true,
                'meta_message_id' => $metaId,
                'mode'            => $dispatchMode,
                'phone'           => $phone,
                'approver'        => $approverName,
                'po_number'       => $poNumber,
                'message'         => $shortMessage
            ]);
        }

        $errorMsg = $response['error']['message'] ?? 'Unable to deliver WhatsApp message via Meta Cloud API';
        return response()->json([
            'success' => false,
            'error'   => $errorMsg,
            'details' => $response
        ], 400);
    }
}


