<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Flow;
use App\Models\FlowNode;
use App\Models\FlowEdge;
use App\Models\BotMessage;
use App\Models\Project;
use App\Models\ProjectConfig;
use App\Models\Setting;
use Illuminate\Support\Str;

class GreenHomesFlowSeeder extends Seeder
{
    private string $expertPhone = '+91 93393 61344';
    private string $expertPhoneIntl = '+919339361344';

    public function run(): void
    {
        FlowEdge::query()->delete();
        FlowNode::query()->delete();
        Flow::query()->delete();
        BotMessage::query()->delete();
        Project::query()->delete();
        ProjectConfig::query()->delete();
        Setting::query()->delete();

        $this->seedSettings();
        $this->seedProjects();
        $this->seedBotMessages();
        $this->seedFlows();
    }

    private function slugify($text)
    {
        return Str::slug($text, '_');
    }

    private function seedSettings(): void
    {
        Setting::create(['key' => 'expert_phone', 'value' => $this->expertPhoneIntl, 'label' => 'Expert Phone Number', 'group' => 'contact']);
        Setting::create(['key' => 'expert_phone_display', 'value' => $this->expertPhone, 'label' => 'Expert Phone Display', 'group' => 'contact']);
        Setting::create(['key' => 'company_name', 'value' => 'Green Hill Group', 'label' => 'Company Name', 'group' => 'general']);
        Setting::create(['key' => 'meta_phone_number_id', 'value' => '', 'label' => 'Meta Phone Number ID', 'group' => 'meta']);
        Setting::create(['key' => 'meta_access_token', 'value' => '', 'label' => 'Meta Access Token', 'group' => 'meta']);
        Setting::create(['key' => 'meta_verify_token', 'value' => 'my_secure_token_123', 'label' => 'Meta Verify Token', 'group' => 'meta']);
        Setting::create(['key' => 'meta_waba_id', 'value' => '', 'label' => 'WhatsApp Business Account ID', 'group' => 'meta']);
    }

    private function seedProjects(): void
    {
        $projects = [
            ['name' => 'Green Exotica', 'slug' => 'green_exotica', 'configs' => [
                ['config_name' => '2 BHK', 'price_label' => '₹40–45 Lakh', 'sort_order' => 1],
                ['config_name' => '3 BHK', 'price_label' => '₹55–60 Lakh', 'sort_order' => 2],
            ]],
            ['name' => 'Green Elegance', 'slug' => 'green_elegance', 'configs' => [
                ['config_name' => '2 BHK', 'price_label' => '₹30–48 Lakh', 'sort_order' => 1],
                ['config_name' => '3 BHK', 'price_label' => '₹45–60 Lakh', 'sort_order' => 2],
            ]],
            ['name' => 'Green Retreat', 'slug' => 'green_retreat', 'configs' => [
                ['config_name' => '2 BHK', 'price_label' => '₹55 Lakh', 'sort_order' => 1],
                ['config_name' => '3 BHK', 'price_label' => '₹67–75 Lakh', 'sort_order' => 2],
            ]],
            ['name' => 'Green Ville', 'slug' => 'green_ville', 'configs' => [
                ['config_name' => '2 BHK', 'price_label' => '₹55 Lakh', 'sort_order' => 1],
                ['config_name' => '3 BHK', 'price_label' => '₹75 Lakh', 'sort_order' => 2],
                ['config_name' => '4 BHK', 'price_label' => '₹95 Lakh', 'sort_order' => 3],
            ]],
            ['name' => 'Green Delight', 'slug' => 'green_delight', 'configs' => [
                ['config_name' => '2 BHK', 'price_label' => '₹58 Lakh', 'sort_order' => 1],
            ]],
            ['name' => 'Europa', 'slug' => 'europa', 'configs' => [
                ['config_name' => 'Villa', 'price_label' => '₹1.31 Crore', 'sort_order' => 1],
            ]],
            ['name' => 'Silo Kutir', 'slug' => 'silo_kutir', 'configs' => [
                ['config_name' => '2 BHK', 'price_label' => '₹67 Lakh', 'sort_order' => 1],
                ['config_name' => '3 BHK', 'price_label' => '₹85 Lakh–₹1 Crore', 'sort_order' => 2],
            ]],
            ['name' => 'Aura', 'slug' => 'aura', 'configs' => [
                ['config_name' => '2 BHK', 'price_label' => '₹48 Lakh', 'sort_order' => 1],
                ['config_name' => '3 BHK', 'price_label' => '₹60–70 Lakh', 'sort_order' => 2],
            ]],
        ];

        foreach ($projects as $p) {
            $project = Project::create([
                'name' => $p['name'],
                'slug' => $p['slug'],
                'business_vertical' => 'Green Hill Group',
            ]);
            foreach ($p['configs'] as $cfg) {
                $project->configs()->create(array_merge($cfg, ['price_type' => 'Indicative Price']));
            }
        }
    }

    private function getPropertyType(Project $project): string
    {
        $hasVilla = $project->configs()->where('config_name', 'like', '%Villa%')->exists();
        $hasBungalow = $project->configs()->where('config_name', 'like', '%Bungalow%')->exists();
        
        if ($hasBungalow) return 'Bungalow';
        if ($hasVilla) return 'Villa';
        return 'Flat / Apartment';
    }

    private function seedBotMessages(): void
    {
        $messages = [];
        
        // FLOW 1: MAIN WELCOME MESSAGE
        $messages[] = [
            'internal_name' => 'WELCOME_MAIN',
            'display_name' => 'Main Welcome',
            'header_text' => 'Welcome to Green Hill Group!',
            'body_text' => "Thank you for reaching out to us. We're here to help you find a home that fits your lifestyle and requirements.\n\nHow may we assist you today?",
            'buttons' => [
                ['label' => ' Explore Properties', 'payload' => 'EXPLORE_PROPS', 'action_type' => 'reply'],
                ['label' => ' Book Site Visit', 'payload' => 'BOOK_VISIT', 'action_type' => 'reply'],
                ['label' => ' Talk to Sales', 'payload' => 'TALK_SALES', 'action_type' => 'reply'],
            ],
            'message_type' => 'interactive',
        ];

        // FLOW 1: EXPLORE PROPERTIES
        $messages[] = [
            'internal_name' => 'EXPLORE_PROPS',
            'display_name' => 'Explore Properties',
            'body_text' => "Explore Green Hill Group Properties\n\nWe have a range of residential properties designed for different lifestyles and budgets.\n\nWhat type of property are you looking for?",
            'list_button_label' => 'Properties',
            'list_sections' => [[
                'title' => 'Property Types',
                'rows' => [
                    ['id' => 'TYPE_FLAT', 'title' => ' Flat / Apartment'],
                    ['id' => 'TYPE_VILLA', 'title' => ' Villa'],
                    ['id' => 'TYPE_BUNGALOW', 'title' => ' Bungalow'],
                    ['id' => 'MAIN_MENU', 'title' => ' Main Menu'],
                ]
            ]],
            'message_type' => 'list',
        ];

        // DYNAMIC PROJECT LISTS
        $projects = Project::with('configs')->get();
        $flats = $projects->filter(fn($p) => $this->getPropertyType($p) === 'Flat / Apartment');
        $villas = $projects->filter(fn($p) => $this->getPropertyType($p) === 'Villa');
        $bungalows = $projects->filter(fn($p) => $this->getPropertyType($p) === 'Bungalow');

        $this->addProjectListMessage($messages, 'FLAT_LIST', 'Flat / Apartment Projects', 'Active Flat/Apartment Projects:', $flats);
        $this->addProjectListMessage($messages, 'VILLA_LIST', 'Villa Projects', 'Active Villa Projects:', $villas);
        $this->addProjectListMessage($messages, 'BUNGALOW_LIST', 'Bungalow Projects', 'Active Bungalow Projects:', $bungalows);

        // FLOW 5: PROJECT DETAILS (Dynamically generated)
        foreach ($projects as $p) {
            $configsStr = $p->configs->pluck('config_name')->implode(', ');
            $messages[] = [
                'internal_name' => 'PROJ_DETAIL_' . strtoupper($p->slug),
                'display_name' => "Project Details: {$p->name}",
                'body_text' => "Project: {$p->name}\nType: " . $this->getPropertyType($p) . "\nConfigurations: {$configsStr}\n\nWhat would you like to do?",
                'list_button_label' => ' Select Option',
                'list_sections' => [[
                    'title' => 'Options',
                    'rows' => [
                        ['id' => 'BROCHURE_' . strtoupper($p->slug), 'title' => '📄 Download Brochure'],
                        ['id' => 'PROJ_PRICE', 'title' => ' Price / Budget'],
                        ['id' => 'BOOK_VISIT', 'title' => ' Book Site Visit'],
                        ['id' => 'TALK_SALES', 'title' => ' Talk to Sales'],
                        ['id' => 'EXPLORE_PROPS', 'title' => ' Back to Projects'],
                    ]
                ]],
                'message_type' => 'list',
            ];
        }

        // FLOW 6: PRICE / BUDGET
        $messages[] = [
            'internal_name' => 'PRICE_BUDGET',
            'display_name' => 'Price / Budget',
            'body_text' => 'What is your approximate budget?',
            'list_button_label' => ' Select Budget',
            'list_sections' => [[
                'title' => 'Budget Ranges',
                'rows' => [
                    ['id' => 'BUDGET_30_50', 'title' => ' ₹30–50 Lakh'],
                    ['id' => 'BUDGET_50_75', 'title' => ' ₹50–75 Lakh'],
                    ['id' => 'BUDGET_75_1CR', 'title' => ' ₹75 Lakh–₹1 Crore'],
                    ['id' => 'BUDGET_1CR_PLUS', 'title' => ' ₹1 Crore+'],
                    ['id' => 'BUDGET_UNSURE', 'title' => ' Not Sure'],
                ]
            ]],
            'message_type' => 'list',
        ];

        $messages[] = [
            'internal_name' => 'BUDGET_NEXT',
            'display_name' => 'Budget Next Steps',
            'body_text' => 'Thank you. We have noted your budget requirements. How would you like to proceed?',
            'buttons' => [
                ['label' => ' Book Site Visit', 'payload' => 'BOOK_VISIT', 'action_type' => 'reply'],
                ['label' => ' Talk to Sales', 'payload' => 'TALK_SALES', 'action_type' => 'reply'],
            ],
            'message_type' => 'interactive',
        ];

        // FLOW 7: BOOK SITE VISIT
        $svPropRows = [];
        foreach ($projects as $p) {
            $svPropRows[] = ['id' => 'PROJ_' . strtoupper($p->slug), 'title' => substr($p->name, 0, 24)];
        }

        $messages[] = [
            'internal_name' => 'SV_PROPERTY',
            'display_name' => 'SV: Property',
            'body_text' => 'Which property/project are you interested in visiting?',
            'list_button_label' => ' Properties',
            'list_sections' => [[
                'title' => 'Projects',
                'rows' => $svPropRows
            ]],
            'message_type' => 'list',
        ];
        $messages[] = [
            'internal_name' => 'SV_DATE',
            'display_name' => 'SV: Date',
            'body_text' => 'What is your preferred date for the visit? (e.g., Tomorrow, Next Sunday)',
            'message_type' => 'interactive',
        ];
        $messages[] = [
            'internal_name' => 'SV_TIME',
            'display_name' => 'SV: Time',
            'body_text' => 'What is your preferred time?',
            'buttons' => [
                ['label' => ' Morning (10-1)', 'payload' => 'TIME_MORNING', 'action_type' => 'reply'],
                ['label' => ' Afternoon (1-4)', 'payload' => 'TIME_AFTERNOON', 'action_type' => 'reply'],
                ['label' => ' Evening (4-7)', 'payload' => 'TIME_EVENING', 'action_type' => 'reply'],
            ],
            'message_type' => 'interactive',
        ];
        $messages[] = [
            'internal_name' => 'SV_NAME',
            'display_name' => 'SV: Name',
            'body_text' => 'Please provide your full name.',
            'message_type' => 'interactive',
        ];
        $messages[] = [
            'internal_name' => 'SV_PURPOSE',
            'display_name' => 'SV: Purpose',
            'body_text' => 'What is the purpose of your requirement?',
            'list_button_label' => ' Select Purpose',
            'list_sections' => [[
                'title' => 'Purpose',
                'rows' => [
                    ['id' => 'PURPOSE_MYSELF', 'title' => ' For Myself'],
                    ['id' => 'PURPOSE_FAMILY', 'title' => ' For Family'],
                    ['id' => 'PURPOSE_INVESTMENT', 'title' => ' Investment'],
                    ['id' => 'PURPOSE_EXPLORING', 'title' => ' Just Exploring'],
                ]
            ]],
            'message_type' => 'list',
        ];
        $messages[] = [
            'internal_name' => 'SV_CONFIRM',
            'display_name' => 'SV: Confirm',
            'body_text' => "Site Visit Request Received!\n\nThank you!\nOur sales team will contact you shortly to confirm the visit.",
            'buttons' => [
                ['label' => ' Main Menu', 'payload' => 'MAIN_MENU', 'action_type' => 'reply'],
            ],
            'message_type' => 'interactive',
        ];

        // FLOW 8: TALK TO SALES
        $messages[] = [
            'internal_name' => 'TALK_SALES_MAIN',
            'display_name' => 'Talk to Sales',
            'body_text' => "Talk to Green Hill Group\n\nOur sales team can help you with:\n• Property availability\n• Pricing & payment options\n• Project details\n• Site visits\n• Booking assistance",
            'buttons' => [
                ['label' => ' Request a Call', 'payload' => 'CALL_REQUEST', 'action_type' => 'reply'],
                ['label' => ' Continue on WhatsApp', 'payload' => 'CONTINUE_WA', 'action_type' => 'reply'],
                ['label' => ' Main Menu', 'payload' => 'MAIN_MENU', 'action_type' => 'reply'],
            ],
            'message_type' => 'interactive',
        ];

        // FLOW 9: REQUEST A CALL
        $messages[] = [
            'internal_name' => 'CALL_NAME',
            'display_name' => 'Call: Name',
            'body_text' => 'Please provide your name so our sales team can contact you.',
            'message_type' => 'interactive',
        ];
        $messages[] = [
            'internal_name' => 'CALL_PROPERTY',
            'display_name' => 'Call: Property',
            'body_text' => 'Which property or project are you interested in?',
            'message_type' => 'interactive',
        ];
        $messages[] = [
            'internal_name' => 'CALL_REQUIREMENT',
            'display_name' => 'Call: Requirement',
            'body_text' => 'Briefly describe your requirement (e.g. looking for a 3BHK).',
            'message_type' => 'interactive',
        ];
        $messages[] = [
            'internal_name' => 'CALL_CONFIRM',
            'display_name' => 'Call: Confirm',
            'body_text' => "Thank you! We have logged your request. A sales representative will call you shortly.",
            'buttons' => [
                ['label' => ' Main Menu', 'payload' => 'MAIN_MENU', 'action_type' => 'reply'],
            ],
            'message_type' => 'interactive',
        ];

        // FLOW 11: HUMAN HANDOVER
        $messages[] = [
            'internal_name' => 'CONTINUE_WA_MSG',
            'display_name' => 'Continue on WA',
            'body_text' => "You will now be connected to our sales team.\n\nPlease type your message below.",
            'buttons' => [
                ['label' => ' Main Menu', 'payload' => 'MAIN_MENU', 'action_type' => 'reply'],
            ],
            'message_type' => 'interactive',
        ];

        // FLOW 12: OUTSIDE BUSINESS HOURS
        $messages[] = [
            'internal_name' => 'OUTSIDE_HOURS',
            'display_name' => 'Outside Hours',
            'body_text' => "Thank you for contacting Green Hill Group.\n\nOur sales team is currently unavailable. Please leave your details and we will get back to you as soon as possible.",
            'buttons' => [
                ['label' => ' Leave My Details', 'payload' => 'CALL_REQUEST', 'action_type' => 'reply'],
                ['label' => ' Explore Properties', 'payload' => 'EXPLORE_PROPS', 'action_type' => 'reply'],
                ['label' => ' Main Menu', 'payload' => 'MAIN_MENU', 'action_type' => 'reply'],
            ],
            'message_type' => 'interactive',
        ];

        // FLOW 13: INVALID INPUT
        $messages[] = [
            'internal_name' => 'INVALID_INPUT',
            'display_name' => 'Invalid Input',
            'body_text' => "Sorry, I couldn't quite understand that.\n\nPlease choose an option below, or type 'Sales' to speak with our team.",
            'list_button_label' => ' Options',
            'list_sections' => [[
                'title' => 'Main Options',
                'rows' => [
                    ['id' => 'EXPLORE_PROPS', 'title' => ' Explore Properties'],
                    ['id' => 'BOOK_VISIT', 'title' => ' Book Site Visit'],
                    ['id' => 'TALK_SALES', 'title' => ' Talk to Sales'],
                    ['id' => 'MAIN_MENU', 'title' => ' Main Menu'],
                ]
            ]],
            'message_type' => 'list',
        ];

        foreach ($messages as $data) {
            BotMessage::create(array_merge([
                'flow_group' => null,
                'business_vertical' => 'Green Hill Group',
                'header_text' => null,
                'footer_text' => null,
                'buttons' => null,
                'list_sections' => null,
                'list_button_label' => null,
                'meta_template_language' => 'en',
                'meta_template_status' => 'not_connected'
            ], $data));
        }
    }

    private function addProjectListMessage(&$messages, $internalName, $displayName, $bodyText, $projects)
    {
        $rows = [];
        foreach ($projects as $p) {
            $rows[] = ['id' => 'PROJ_' . strtoupper($p->slug), 'title' => substr($p->name, 0, 24)];
        }
        $rows[] = ['id' => 'MAIN_MENU', 'title' => ' Main Menu'];
        
        $messages[] = [
            'internal_name' => $internalName,
            'display_name' => $displayName,
            'body_text' => $bodyText . "\n\nPlease select a project to know more:",
            'list_button_label' => ' Select Project',
            'list_sections' => [[
                'title' => 'Projects',
                'rows' => $rows
            ]],
            'message_type' => 'list',
        ];
    }

    private function seedFlows(): void
    {
        $msg = fn($name) => BotMessage::where('internal_name', $name)->first();

        // FLOW: MAIN
        $flowMain = Flow::create(['name' => 'Main Flow', 'group_name' => 'MAIN', 'is_active' => true, 'is_default_welcome' => true]);
        
        $n_welcome = FlowNode::create(['flow_id' => $flowMain->id, 'bot_message_id' => $msg('WELCOME_MAIN')->id, 'name' => 'Welcome', 'type' => 'text', 'is_start' => true, 'sort_order' => 1, 'input_type' => 'button']);
        
        $n_explore = FlowNode::create(['flow_id' => $flowMain->id, 'bot_message_id' => $msg('EXPLORE_PROPS')->id, 'name' => 'Explore Properties', 'type' => 'text', 'sort_order' => 2, 'input_type' => 'list']);
        $n_flat = FlowNode::create(['flow_id' => $flowMain->id, 'bot_message_id' => $msg('FLAT_LIST')->id, 'name' => 'Flat List', 'type' => 'text', 'sort_order' => 3, 'input_type' => 'list']);
        $n_villa = FlowNode::create(['flow_id' => $flowMain->id, 'bot_message_id' => $msg('VILLA_LIST')->id, 'name' => 'Villa List', 'type' => 'text', 'sort_order' => 4, 'input_type' => 'list']);
        $n_bungalow = FlowNode::create(['flow_id' => $flowMain->id, 'bot_message_id' => $msg('BUNGALOW_LIST')->id, 'name' => 'Bungalow List', 'type' => 'text', 'sort_order' => 5, 'input_type' => 'list']);

        FlowEdge::create(['source_node_id' => $n_welcome->id, 'target_node_id' => $n_explore->id, 'condition_payload' => 'EXPLORE_PROPS']);
        FlowEdge::create(['source_node_id' => $n_explore->id, 'target_node_id' => $n_flat->id, 'condition_payload' => 'TYPE_FLAT', 'save_value' => 'Flat / Apartment']);
        FlowEdge::create(['source_node_id' => $n_explore->id, 'target_node_id' => $n_welcome->id, 'condition_payload' => 'MAIN_MENU']);

        // Project details dynamic routing
        $projects = Project::all();
        foreach ($projects as $p) {
            $nodeId = 'PROJ_' . strtoupper($p->slug);
            $n_proj = FlowNode::create(['flow_id' => $flowMain->id, 'bot_message_id' => $msg('PROJ_DETAIL_' . strtoupper($p->slug))->id, 'name' => 'Proj ' . $p->name, 'type' => 'text', 'sort_order' => 10, 'input_type' => 'list']);
            
            // Link lists to this node
            FlowEdge::create(['source_node_id' => $n_flat->id, 'target_node_id' => $n_proj->id, 'condition_payload' => $nodeId, 'save_value' => $p->name]);
            FlowEdge::create(['source_node_id' => $n_villa->id, 'target_node_id' => $n_proj->id, 'condition_payload' => $nodeId, 'save_value' => $p->name]);
            FlowEdge::create(['source_node_id' => $n_bungalow->id, 'target_node_id' => $n_proj->id, 'condition_payload' => $nodeId, 'save_value' => $p->name]);

            // Back button
            FlowEdge::create(['source_node_id' => $n_proj->id, 'target_node_id' => $n_explore->id, 'condition_payload' => 'EXPLORE_PROPS']);
        }

        // PRICE BUDGET
        $n_budget = FlowNode::create(['flow_id' => $flowMain->id, 'bot_message_id' => $msg('PRICE_BUDGET')->id, 'name' => 'Price Budget', 'type' => 'text', 'expected_lead_field' => 'budget', 'sort_order' => 20, 'input_type' => 'list']);
        $n_budget_next = FlowNode::create(['flow_id' => $flowMain->id, 'bot_message_id' => $msg('BUDGET_NEXT')->id, 'name' => 'Budget Next', 'type' => 'text', 'sort_order' => 21, 'input_type' => 'button']);

        // Link all PROJ nodes to Price Budget
        foreach ($projects as $p) {
            $n_proj = FlowNode::where('bot_message_id', $msg('PROJ_DETAIL_' . strtoupper($p->slug))->id)->first();
            FlowEdge::create(['source_node_id' => $n_proj->id, 'target_node_id' => $n_budget->id, 'condition_payload' => 'PROJ_PRICE']);
        }

        foreach (['BUDGET_30_50' => '₹30-50 Lakh', 'BUDGET_50_75' => '₹50-75 Lakh', 'BUDGET_75_1CR' => '₹75L-1Cr', 'BUDGET_1CR_PLUS' => '₹1Cr+', 'BUDGET_UNSURE' => 'Not Sure'] as $payload => $val) {
            FlowEdge::create(['source_node_id' => $n_budget->id, 'target_node_id' => $n_budget_next->id, 'condition_payload' => $payload, 'save_value' => $val]);
        }

        // SITE VISIT
        $flowSV = Flow::create(['name' => 'Site Visit Flow', 'group_name' => 'SITE_VISIT', 'is_active' => true]);
        $n_sv_prop = FlowNode::create(['flow_id' => $flowSV->id, 'bot_message_id' => $msg('SV_PROPERTY')->id, 'name' => 'SV Prop', 'type' => 'text', 'expected_lead_field' => 'requirement', 'is_start' => true, 'sort_order' => 1, 'input_type' => 'text_input']);
        $n_sv_date = FlowNode::create(['flow_id' => $flowSV->id, 'bot_message_id' => $msg('SV_DATE')->id, 'name' => 'SV Date', 'type' => 'text', 'expected_lead_field' => 'site_visit_date', 'sort_order' => 2, 'input_type' => 'text_input']);
        $n_sv_time = FlowNode::create(['flow_id' => $flowSV->id, 'bot_message_id' => $msg('SV_TIME')->id, 'name' => 'SV Time', 'type' => 'text', 'expected_lead_field' => 'site_visit_time', 'sort_order' => 3, 'input_type' => 'button']);
        $n_sv_name = FlowNode::create(['flow_id' => $flowSV->id, 'bot_message_id' => $msg('SV_NAME')->id, 'name' => 'SV Name', 'type' => 'text', 'expected_lead_field' => 'customer_name', 'sort_order' => 4, 'input_type' => 'text_input']);
        $n_sv_purp = FlowNode::create(['flow_id' => $flowSV->id, 'bot_message_id' => $msg('SV_PURPOSE')->id, 'name' => 'SV Purpose', 'type' => 'text', 'expected_lead_field' => 'sub_requirement', 'sort_order' => 5, 'input_type' => 'list']);
        $n_sv_conf = FlowNode::create(['flow_id' => $flowSV->id, 'bot_message_id' => $msg('SV_CONFIRM')->id, 'name' => 'SV Confirm', 'type' => 'text', 'sort_order' => 6, 'input_type' => 'button']);

        FlowEdge::create(['source_node_id' => $n_sv_prop->id, 'target_node_id' => $n_sv_date->id, 'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_sv_date->id, 'target_node_id' => $n_sv_time->id, 'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_sv_time->id, 'target_node_id' => $n_sv_name->id, 'condition_payload' => 'TIME_MORNING', 'save_value' => 'Morning']);
        FlowEdge::create(['source_node_id' => $n_sv_time->id, 'target_node_id' => $n_sv_name->id, 'condition_payload' => 'TIME_AFTERNOON', 'save_value' => 'Afternoon']);
        FlowEdge::create(['source_node_id' => $n_sv_time->id, 'target_node_id' => $n_sv_name->id, 'condition_payload' => 'TIME_EVENING', 'save_value' => 'Evening']);
        FlowEdge::create(['source_node_id' => $n_sv_name->id, 'target_node_id' => $n_sv_purp->id, 'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_sv_purp->id, 'target_node_id' => $n_sv_conf->id, 'condition_payload' => 'PURPOSE_MYSELF', 'save_value' => 'For Myself']);
        FlowEdge::create(['source_node_id' => $n_sv_purp->id, 'target_node_id' => $n_sv_conf->id, 'condition_payload' => 'PURPOSE_FAMILY', 'save_value' => 'For Family']);
        FlowEdge::create(['source_node_id' => $n_sv_purp->id, 'target_node_id' => $n_sv_conf->id, 'condition_payload' => 'PURPOSE_INVESTMENT', 'save_value' => 'Investment']);
        FlowEdge::create(['source_node_id' => $n_sv_purp->id, 'target_node_id' => $n_sv_conf->id, 'condition_payload' => 'PURPOSE_EXPLORING', 'save_value' => 'Exploring']);
        FlowEdge::create(['source_node_id' => $n_sv_conf->id, 'target_node_id' => $n_welcome->id, 'condition_payload' => 'MAIN_MENU']);

        // Cross flow connections to SITE_VISIT
        FlowEdge::create(['source_node_id' => $n_welcome->id, 'target_node_id' => $n_sv_prop->id, 'condition_payload' => 'BOOK_VISIT']);
        FlowEdge::create(['source_node_id' => $n_budget_next->id, 'target_node_id' => $n_sv_date->id, 'condition_payload' => 'BOOK_VISIT']);
        foreach ($projects as $p) {
            $n_proj = FlowNode::where('bot_message_id', $msg('PROJ_DETAIL_' . strtoupper($p->slug))->id)->first();
            FlowEdge::create(['source_node_id' => $n_proj->id, 'target_node_id' => $n_sv_date->id, 'condition_payload' => 'BOOK_VISIT']);
        }

        // TALK TO SALES
        $flowSales = Flow::create(['name' => 'Sales Flow', 'group_name' => 'SALES', 'is_active' => true]);
        $n_sales_main = FlowNode::create(['flow_id' => $flowSales->id, 'bot_message_id' => $msg('TALK_SALES_MAIN')->id, 'name' => 'Sales Main', 'type' => 'text', 'is_start' => true, 'sort_order' => 1, 'input_type' => 'button']);
        
        // Redirect Bungalow and Villa directly to Sales Main since there are no active projects for them yet
        FlowEdge::create(['source_node_id' => $n_explore->id, 'target_node_id' => $n_sales_main->id, 'condition_payload' => 'TYPE_BUNGALOW', 'save_value' => 'Bungalow']);
        FlowEdge::create(['source_node_id' => $n_explore->id, 'target_node_id' => $n_sales_main->id, 'condition_payload' => 'TYPE_VILLA', 'save_value' => 'Villa']);

        
        $n_call_name = FlowNode::create(['flow_id' => $flowSales->id, 'bot_message_id' => $msg('CALL_NAME')->id, 'name' => 'Call Name', 'type' => 'text', 'expected_lead_field' => 'customer_name', 'sort_order' => 2, 'input_type' => 'text_input']);
        $n_call_prop = FlowNode::create(['flow_id' => $flowSales->id, 'bot_message_id' => $msg('CALL_PROPERTY')->id, 'name' => 'Call Prop', 'type' => 'text', 'expected_lead_field' => 'requirement', 'sort_order' => 3, 'input_type' => 'text_input']);
        $n_call_req = FlowNode::create(['flow_id' => $flowSales->id, 'bot_message_id' => $msg('CALL_REQUIREMENT')->id, 'name' => 'Call Req', 'type' => 'text', 'expected_lead_field' => 'sub_requirement', 'sort_order' => 4, 'input_type' => 'text_input']);
        $n_call_conf = FlowNode::create(['flow_id' => $flowSales->id, 'bot_message_id' => $msg('CALL_CONFIRM')->id, 'name' => 'Call Confirm', 'type' => 'text', 'triggers_human_handover' => true, 'sort_order' => 5, 'input_type' => 'button']);

        $n_cont_wa = FlowNode::create(['flow_id' => $flowSales->id, 'bot_message_id' => $msg('CONTINUE_WA_MSG')->id, 'name' => 'Cont WA', 'type' => 'text', 'triggers_human_handover' => true, 'sort_order' => 6, 'input_type' => 'button']);

        FlowEdge::create(['source_node_id' => $n_sales_main->id, 'target_node_id' => $n_call_name->id, 'condition_payload' => 'CALL_REQUEST']);
        FlowEdge::create(['source_node_id' => $n_sales_main->id, 'target_node_id' => $n_cont_wa->id, 'condition_payload' => 'CONTINUE_WA']);
        FlowEdge::create(['source_node_id' => $n_sales_main->id, 'target_node_id' => $n_welcome->id, 'condition_payload' => 'MAIN_MENU']);

        FlowEdge::create(['source_node_id' => $n_call_name->id, 'target_node_id' => $n_call_prop->id, 'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_call_prop->id, 'target_node_id' => $n_call_req->id, 'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_call_req->id, 'target_node_id' => $n_call_conf->id, 'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_call_conf->id, 'target_node_id' => $n_welcome->id, 'condition_payload' => 'MAIN_MENU']);
        FlowEdge::create(['source_node_id' => $n_cont_wa->id, 'target_node_id' => $n_welcome->id, 'condition_payload' => 'MAIN_MENU']);

        // Cross flow connections to SALES
        FlowEdge::create(['source_node_id' => $n_welcome->id, 'target_node_id' => $n_sales_main->id, 'condition_payload' => 'TALK_SALES']);
        FlowEdge::create(['source_node_id' => $n_explore->id, 'target_node_id' => $n_sales_main->id, 'condition_payload' => 'TALK_SALES']);
        FlowEdge::create(['source_node_id' => $n_budget_next->id, 'target_node_id' => $n_sales_main->id, 'condition_payload' => 'TALK_SALES']);
        foreach ($projects as $p) {
            $n_proj = FlowNode::where('bot_message_id', $msg('PROJ_DETAIL_' . strtoupper($p->slug))->id)->first();
            FlowEdge::create(['source_node_id' => $n_proj->id, 'target_node_id' => $n_sales_main->id, 'condition_payload' => 'TALK_SALES']);
        }

        // GLOBAL FALLBACK / INVALID INPUT
        $flowGlobal = Flow::create(['name' => 'Global Fallback', 'group_name' => 'GLOBAL', 'is_active' => true]);
        $n_invalid = FlowNode::create(['flow_id' => $flowGlobal->id, 'bot_message_id' => $msg('INVALID_INPUT')->id, 'name' => 'Invalid Input', 'type' => 'text', 'is_start' => true, 'sort_order' => 1, 'input_type' => 'list']);
        
        FlowEdge::create(['source_node_id' => $n_invalid->id, 'target_node_id' => $n_explore->id, 'condition_payload' => 'EXPLORE_PROPS']);
        FlowEdge::create(['source_node_id' => $n_invalid->id, 'target_node_id' => $n_sv_prop->id, 'condition_payload' => 'BOOK_VISIT']);
        FlowEdge::create(['source_node_id' => $n_invalid->id, 'target_node_id' => $n_sales_main->id, 'condition_payload' => 'TALK_SALES']);
        FlowEdge::create(['source_node_id' => $n_invalid->id, 'target_node_id' => $n_welcome->id, 'condition_payload' => 'MAIN_MENU']);
    }
}
