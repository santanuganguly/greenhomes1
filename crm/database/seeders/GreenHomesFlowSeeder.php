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

class GreenHomesFlowSeeder extends Seeder
{
    private string $expertPhone = '+91 93393 61344';
    private string $expertPhoneIntl = '+919339361344';

    public function run(): void
    {
        // Clear existing flow data (fresh seed)
        FlowEdge::query()->delete();
        FlowNode::query()->delete();
        Flow::query()->delete();
        BotMessage::query()->delete();
        Project::query()->delete();
        ProjectConfig::query()->delete();
        Setting::query()->delete();

        // 1. Seed Settings
        $this->seedSettings();

        // 2. Seed Projects
        $this->seedProjects();

        // 3. Seed BotMessages
        $this->seedBotMessages();

        // 4. Seed Flows (nodes + edges)
        $this->seedFlows();
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

    private function seedBotMessages(): void
    {
        $ep = $this->expertPhone;
        $epIntl = $this->expertPhoneIntl;

        $messages = [
            // ─── WELCOME / MAIN ────────────────────────────────────────────────────────
            [
                'internal_name'  => 'WELCOME_MAIN',
                'display_name'   => 'Welcome — Main Menu',
                'flow_group'     => 'GREEN_HILL_MAIN',
                'business_vertical' => null,
                'header_text'    => 'WELCOME TO GREEN HILL GROUP',
                'body_text'      => "Welcome to Green Hill Group.\n\nWe are one of North Bengal's leading names in real estate and construction.\n\nWhether you are looking for a new home or planning to build your dream home, we're here to help.\n\nWhat are you looking for today?",
                'footer_text'    => "📞 Call our expert: {$ep}",
                'buttons'        => [
                    ['label' => '🏢 Green Hill Group', 'payload' => 'GHG_MAIN', 'action_type' => 'reply'],
                    ['label' => '🏡 Green Homes',     'payload' => 'GH_MAIN',  'action_type' => 'reply'],
                    ['label' => '👨‍💼 Talk to Our Expert', 'payload' => 'EXPERT', 'action_type' => 'reply'],
                ],
                'message_type'         => 'template',
                'meta_template_name'   => 'green_hill_welcome',
                'meta_template_status' => 'not_connected',
            ],

            // ─── EXPERT CTA ────────────────────────────────────────────────────────────
            [
                'internal_name'  => 'EXPERT_CTA',
                'display_name'   => 'Talk to Expert',
                'flow_group'     => 'GLOBAL',
                'body_text'      => "Our team will be happy to assist you personally.\n\nYou can speak directly with our expert:\n\n📞 {$ep}",
                'buttons'        => [
                    ['label' => '📞 Call Our Expert', 'payload' => "tel:{$epIntl}", 'action_type' => 'call', 'action_value' => $epIntl],
                    ['label' => '🏠 Back to Main Menu', 'payload' => 'MAIN_MENU', 'action_type' => 'reply'],
                ],
                'message_type' => 'interactive',
                'meta_template_name' => 'green_hill_expert_cta',
                'meta_template_status' => 'not_connected',
            ],

            // ─── GREEN HILL GROUP MENU ─────────────────────────────────────────────────
            [
                'internal_name'  => 'GHG_MENU',
                'display_name'   => 'Green Hill Group — Main Menu',
                'flow_group'     => 'GREEN_HILL_MAIN',
                'business_vertical' => 'Green Hill Group',
                'body_text'      => "Welcome to Green Hill Group.\n\nExplore our residential projects and find a home that suits your requirements.\n\nWhat would you like to explore?",
                'footer_text'    => "Need help choosing?\n📞 {$ep}",
                'buttons'        => [
                    ['label' => '🏠 Our Projects',          'payload' => 'GHG_PROJECTS', 'action_type' => 'reply'],
                    ['label' => '📍 Available Properties',   'payload' => 'GHG_PROPERTIES', 'action_type' => 'reply'],
                    ['label' => '👨‍💼 Talk to Our Expert',    'payload' => 'EXPERT', 'action_type' => 'reply'],
                ],
                'message_type' => 'interactive',
                'meta_template_name' => 'green_hill_group_menu',
                'meta_template_status' => 'not_connected',
            ],

            // ─── PROJECTS LIST ─────────────────────────────────────────────────────────
            [
                'internal_name'  => 'GHG_PROJECTS_LIST',
                'display_name'   => 'Green Hill Group — Projects List',
                'flow_group'     => 'GREEN_HILL_PROJECTS',
                'business_vertical' => 'Green Hill Group',
                'body_text'      => "Explore our residential projects.\n\nPlease select a project to know more:",
                'footer_text'    => "📞 {$ep}",
                'list_button_label' => '🏠 Select Project',
                'list_sections'  => [
                    [
                        'title' => 'Our Projects',
                        'rows'  => [
                            ['id' => 'proj_green_exotica',  'title' => 'Green Exotica',  'description' => 'Choose from 2 BHK & 3 BHK'],
                            ['id' => 'proj_green_elegance', 'title' => 'Green Elegance', 'description' => 'Choose from 2 BHK & 3 BHK'],
                            ['id' => 'proj_green_retreat',  'title' => 'Green Retreat',  'description' => 'Choose from 2 BHK & 3 BHK'],
                            ['id' => 'proj_green_ville',    'title' => 'Green Ville',    'description' => 'Choose from 2 BHK, 3 BHK & 4 BHK'],
                            ['id' => 'proj_green_delight',  'title' => 'Green Delight',  'description' => '2 BHK — ₹58 Lakh'],
                            ['id' => 'proj_europa',         'title' => 'Europa',         'description' => 'Villa — ₹1.31 Crore'],
                            ['id' => 'proj_silo_kutir',     'title' => 'Silo Kutir',     'description' => 'Choose from 2 BHK & 3 BHK'],
                            ['id' => 'proj_aura',           'title' => 'Aura',           'description' => 'Choose from 2 BHK & 3 BHK'],
                        ],
                    ],
                    [
                        'title' => 'Get Help',
                        'rows'  => [
                            ['id' => 'EXPERT', 'title' => '👨‍💼 Talk to Our Expert', 'description' => "Call {$ep}"],
                        ],
                    ],
                ],
                'message_type' => 'list',
                'meta_template_name' => 'green_hill_projects',
                'meta_template_status' => 'not_connected',
            ],
        ];

        // ─── Per-Project BotMessages ──────────────────────────────────────────────────
        $projectDefs = [
            'green_exotica'  => ['Green Exotica',  [['2 BHK','₹40–45 Lakh'],['3 BHK','₹55–60 Lakh']]],
            'green_elegance' => ['Green Elegance', [['2 BHK','₹30–48 Lakh'],['3 BHK','₹45–60 Lakh']]],
            'green_retreat'  => ['Green Retreat',  [['2 BHK','₹55 Lakh'],   ['3 BHK','₹67–75 Lakh']]],
            'green_ville'    => ['Green Ville',    [['2 BHK','₹55 Lakh'],   ['3 BHK','₹75 Lakh'],   ['4 BHK','₹95 Lakh']]],
            'green_delight'  => ['Green Delight',  [['2 BHK','₹58 Lakh']]],
            'europa'         => ['Europa',          [['Villa','₹1.31 Crore']]],
            'silo_kutir'     => ['Silo Kutir',     [['2 BHK','₹67 Lakh'],   ['3 BHK','₹85 Lakh–₹1 Crore']]],
            'aura'           => ['Aura',            [['2 BHK','₹48 Lakh'],   ['3 BHK','₹60–70 Lakh']]],
        ];

        foreach ($projectDefs as $slug => [$name, $configs]) {
            $configButtons = [];
            foreach ($configs as $cfg) {
                $configButtons[] = ['label' => $cfg[0], 'payload' => "proj_{$slug}_{$this->slugify($cfg[0])}", 'action_type' => 'reply'];
            }
            $configButtons[] = ['label' => '👨‍💼 Talk to Expert', 'payload' => 'EXPERT', 'action_type' => 'reply'];

            // Project selection node (shows config options)
            $messages[] = [
                'internal_name'  => strtoupper("PROJ_{$slug}_SELECT"),
                'display_name'   => "{$name} — Select Configuration",
                'flow_group'     => 'GREEN_HILL_PROJECTS',
                'business_vertical' => 'Green Hill Group',
                'header_text'    => "🏡 " . strtoupper($name),
                'body_text'      => "Choose your preferred configuration:\n\n" . implode("\n", array_map(fn($c) => "{$c[0]} — {$c[1]}", $configs)),
                'footer_text'    => "📞 {$ep}",
                'buttons'        => $configButtons,
                'message_type'   => 'interactive',
                'meta_template_name' => "ghg_{$slug}_select",
                'meta_template_status' => 'not_connected',
            ];

            // Per-config detail nodes
            foreach ($configs as $cfg) {
                [$cfgName, $price] = $cfg;
                $cfgSlug = $this->slugify($cfgName);
                $messages[] = [
                    'internal_name'  => strtoupper("PROJ_{$slug}_{$cfgSlug}_DETAIL"),
                    'display_name'   => "{$name} — {$cfgName} Details",
                    'flow_group'     => 'GREEN_HILL_PROJECTS',
                    'business_vertical' => 'Green Hill Group',
                    'header_text'    => "🏡 {$name} — {$cfgName}",
                    'body_text'      => "🏡 {$name} — {$cfgName}\n\nIndicative Price:\n{$price}\n\nWould you like to know more?",
                    'footer_text'    => "📞 {$ep}",
                    'buttons'        => [
                        ['label' => '📄 View Brochure',    'payload' => "brochure_{$slug}_{$cfgSlug}", 'action_type' => 'reply'],
                        ['label' => '📅 Book Site Visit',  'payload' => 'SITE_VISIT_START', 'action_type' => 'reply'],
                        ['label' => '👨‍💼 Talk to Expert', 'payload' => 'EXPERT', 'action_type' => 'reply'],
                    ],
                    'message_type'   => 'interactive',
                    'meta_template_name' => "ghg_{$slug}_{$cfgSlug}",
                    'meta_template_status' => 'not_connected',
                ];
            }
        }

        // ─── SITE VISIT FLOW ──────────────────────────────────────────────────────────
        $siteVisitMessages = [
            [
                'internal_name'  => 'SITE_VISIT_ASK_NAME',
                'display_name'   => 'Site Visit — Ask Name',
                'flow_group'     => 'GREEN_HILL_SITE_VISIT',
                'body_text'      => "Great! We'd be happy to arrange a site visit for you.\n\nMay I know your name?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'ghg_site_visit_name',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'SITE_VISIT_ASK_DATE',
                'display_name'   => 'Site Visit — Ask Date',
                'flow_group'     => 'GREEN_HILL_SITE_VISIT',
                'body_text'      => "Thank you, {{name}}.\n\nWhich date would you prefer for your site visit?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'ghg_site_visit_date',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'SITE_VISIT_ASK_TIME',
                'display_name'   => 'Site Visit — Ask Time',
                'flow_group'     => 'GREEN_HILL_SITE_VISIT',
                'body_text'      => "What time would be convenient?",
                'buttons'        => [
                    ['label' => '10 AM – 1 PM', 'payload' => 'sv_time_morning',   'action_type' => 'reply'],
                    ['label' => '1 PM – 4 PM',  'payload' => 'sv_time_afternoon', 'action_type' => 'reply'],
                    ['label' => '4 PM – 7 PM',  'payload' => 'sv_time_evening',   'action_type' => 'reply'],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'ghg_site_visit_time',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'SITE_VISIT_CONFIRMED',
                'display_name'   => 'Site Visit — Confirmed',
                'flow_group'     => 'GREEN_HILL_SITE_VISIT',
                'body_text'      => "Thank you.\n\nYour site visit request has been received.\n\nOur team will confirm the appointment with you shortly.\n\nIf you would like immediate assistance:\n📞 {$ep}",
                'buttons'        => [
                    ['label' => '📞 Call Our Expert', 'payload' => "tel:{$epIntl}", 'action_type' => 'call', 'action_value' => $epIntl],
                    ['label' => '🏠 Main Menu',       'payload' => 'MAIN_MENU', 'action_type' => 'reply'],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'ghg_site_visit_confirmed',
                'meta_template_status' => 'not_connected',
            ],
        ];

        // ─── GREEN HOMES MESSAGES ─────────────────────────────────────────────────────
        $greenHomesMessages = [
            [
                'internal_name'  => 'GH_WELCOME',
                'display_name'   => 'Green Homes — Welcome',
                'flow_group'     => 'GREEN_HOMES_MAIN',
                'business_vertical' => 'Green Homes',
                'header_text'    => '🏡 Green Homes',
                'body_text'      => "🏡 Welcome to Green Homes.\n\nFrom Land to Landmark, We Build It All.\n\nHave a plot but no time or expertise to build your home?\n\nGreen Homes manages your home-building journey from planning and design to construction, finishing and handover.\n\nWhat would you like to do?",
                'footer_text'    => "📞 {$ep}",
                'buttons'        => [
                    ['label' => '🏡 Build My Home', 'payload' => 'GH_BUILD', 'action_type' => 'reply'],
                    ['label' => '💰 Get a Quote',   'payload' => 'GH_QUOTE', 'action_type' => 'reply'],
                    ['label' => '👨‍💼 Talk to Expert', 'payload' => 'EXPERT', 'action_type' => 'reply'],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'green_homes_welcome',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_OWN_LAND',
                'display_name'   => 'Green Homes — Own Land?',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "Let's start with one simple question.\n\nDo you already own a plot of land?",
                'buttons'        => [
                    ['label' => '✅ Yes, I Own Land', 'payload' => 'GH_LAND_YES',    'action_type' => 'reply'],
                    ['label' => '❌ No, Not Yet',     'payload' => 'GH_LAND_NO',     'action_type' => 'reply'],
                    ['label' => '👨‍💼 Talk to Expert', 'payload' => 'EXPERT',         'action_type' => 'reply'],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_own_land',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_HOME_TYPE',
                'display_name'   => 'Green Homes — Home Type',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "Perfect.\n\nWhat type of home are you planning?",
                'buttons'        => [
                    ['label' => '🏠 Independent House', 'payload' => 'ht_independent', 'action_type' => 'reply'],
                    ['label' => '🏡 Villa',             'payload' => 'ht_villa',       'action_type' => 'reply'],
                    ['label' => '🏢 Other',             'payload' => 'ht_other',       'action_type' => 'reply'],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_home_type',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_FLOORS',
                'display_name'   => 'Green Homes — Floors',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "How many floors are you planning?",
                'list_button_label' => 'Select Floors',
                'list_sections'  => [[
                    'title' => 'Number of Floors',
                    'rows'  => [
                        ['id' => 'fl_ground',   'title' => 'Ground Floor'],
                        ['id' => 'fl_g1',       'title' => 'G+1'],
                        ['id' => 'fl_g2',       'title' => 'G+2'],
                        ['id' => 'fl_g3plus',   'title' => 'G+3+'],
                        ['id' => 'fl_undecided','title' => 'Not Decided'],
                        ['id' => 'EXPERT',      'title' => '👨‍💼 Talk to Expert'],
                    ],
                ]],
                'message_type'   => 'list',
                'meta_template_name' => 'gh_floors',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_AREA',
                'display_name'   => 'Green Homes — Built-up Area',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "What is your approximate built-up area?",
                'list_button_label' => 'Select Area',
                'list_sections'  => [[
                    'title' => 'Built-up Area',
                    'rows'  => [
                        ['id' => 'area_under_1500',  'title' => 'Under 1,500 sq.ft.'],
                        ['id' => 'area_1500_2500',   'title' => '1,500–2,500 sq.ft.'],
                        ['id' => 'area_2500_4000',   'title' => '2,500–4,000 sq.ft.'],
                        ['id' => 'area_4000plus',    'title' => '4,000+ sq.ft.'],
                        ['id' => 'area_undecided',   'title' => 'Not Decided'],
                        ['id' => 'EXPERT',           'title' => '👨‍💼 Talk to Expert'],
                    ],
                ]],
                'message_type'   => 'list',
                'meta_template_name' => 'gh_area',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_BUDGET',
                'display_name'   => 'Green Homes — Budget',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "What is your approximate construction budget?",
                'list_button_label' => 'Select Budget',
                'list_sections'  => [[
                    'title' => 'Construction Budget',
                    'rows'  => [
                        ['id' => 'bud_under50',    'title' => 'Under ₹50 Lakh'],
                        ['id' => 'bud_50_1cr',     'title' => '₹50L–₹1 Crore'],
                        ['id' => 'bud_1_2cr',      'title' => '₹1–2 Crore'],
                        ['id' => 'bud_2crplus',    'title' => '₹2 Crore+'],
                        ['id' => 'bud_guidance',   'title' => 'Need Guidance'],
                        ['id' => 'EXPERT',         'title' => '👨‍💼 Talk to Expert'],
                    ],
                ]],
                'message_type'   => 'list',
                'meta_template_name' => 'gh_budget',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_TIMELINE',
                'display_name'   => 'Green Homes — Timeline',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "When are you planning to start construction?",
                'list_button_label' => 'Select Timeline',
                'list_sections'  => [[
                    'title' => 'Construction Timeline',
                    'rows'  => [
                        ['id' => 'tl_3months',  'title' => 'Within 3 Months'],
                        ['id' => 'tl_3_6',      'title' => '3–6 Months'],
                        ['id' => 'tl_6_12',     'title' => '6–12 Months'],
                        ['id' => 'tl_later',    'title' => 'Later'],
                        ['id' => 'EXPERT',      'title' => '👨‍💼 Talk to Expert'],
                    ],
                ]],
                'message_type'   => 'list',
                'meta_template_name' => 'gh_timeline',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_QUALIFIED',
                'display_name'   => 'Green Homes — Qualified Summary',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "Thank you for sharing your requirements.\n\nWe now have a better understanding of your home-building plans.\n\nOur Green Homes expert can guide you through the next steps.\n\n📞 Call our expert:\n{$ep}",
                'buttons'        => [
                    ['label' => '📞 Call Our Expert', 'payload' => "tel:{$epIntl}", 'action_type' => 'call', 'action_value' => $epIntl],
                    ['label' => '👨‍💼 Talk to Expert', 'payload' => 'EXPERT',        'action_type' => 'reply'],
                    ['label' => '🏠 Main Menu',       'payload' => 'MAIN_MENU',     'action_type' => 'reply'],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_qualified',
                'meta_template_status' => 'not_connected',
            ],
            // No Land path
            [
                'internal_name'  => 'GH_NO_LAND_MENU',
                'display_name'   => 'Green Homes — No Land Options',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "No problem.\n\nHow can we help you?",
                'buttons'        => [
                    ['label' => '📍 Looking for Land',    'payload' => 'GH_LOOK_LAND',  'action_type' => 'reply'],
                    ['label' => '🏡 Planning to Buy Land', 'payload' => 'GH_PLAN_LAND',  'action_type' => 'reply'],
                    ['label' => '👨‍💼 Need Guidance',      'payload' => 'EXPERT',          'action_type' => 'reply'],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_no_land_menu',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_LAND_LOCATION',
                'display_name'   => 'Green Homes — Land Location',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "Which location are you considering?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_land_location',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_LAND_BUDGET',
                'display_name'   => 'Green Homes — Land Budget',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "What is your approximate land budget?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_land_budget',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_LAND_HOME_TYPE',
                'display_name'   => 'Green Homes — Land Home Type',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "What type of home are you planning to build?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_land_home_type',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_LAND_TIMELINE',
                'display_name'   => 'Green Homes — Land Timeline',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "When are you planning to start?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_land_timeline',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_NO_LAND_FINAL',
                'display_name'   => 'Green Homes — No Land Final',
                'flow_group'     => 'GREEN_HOMES_BUILD',
                'business_vertical' => 'Green Homes',
                'body_text'      => "Thank you.\n\nOur Green Homes team can guide you regarding your requirements.\n\n📞 Call our expert:\n{$ep}",
                'buttons'        => [
                    ['label' => '📞 Call Our Expert', 'payload' => "tel:{$epIntl}", 'action_type' => 'call', 'action_value' => $epIntl],
                    ['label' => '👨‍💼 Talk to Expert', 'payload' => 'EXPERT', 'action_type' => 'reply'],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_no_land_final',
                'meta_template_status' => 'not_connected',
            ],
            // Get a Quote flow
            [
                'internal_name'  => 'GH_QUOTE_NAME',
                'display_name'   => 'Green Homes — Quote: Name',
                'flow_group'     => 'GREEN_HOMES_QUOTE',
                'business_vertical' => 'Green Homes',
                'body_text'      => "We'd be happy to understand your project and help you with the next steps.\n\nFirst, may I know your name?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_quote_name',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_QUOTE_LOCATION',
                'display_name'   => 'Green Homes — Quote: Location',
                'flow_group'     => 'GREEN_HOMES_QUOTE',
                'business_vertical' => 'Green Homes',
                'body_text'      => "What is the location of your plot?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_quote_location',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_QUOTE_PLOT_SIZE',
                'display_name'   => 'Green Homes — Quote: Plot Size',
                'flow_group'     => 'GREEN_HOMES_QUOTE',
                'business_vertical' => 'Green Homes',
                'body_text'      => "What is the approximate plot size?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_quote_plot_size',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_QUOTE_BUILTUP',
                'display_name'   => 'Green Homes — Quote: Built-up Area',
                'flow_group'     => 'GREEN_HOMES_QUOTE',
                'business_vertical' => 'Green Homes',
                'body_text'      => "What is the approximate built-up area you are considering?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_quote_builtup',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_QUOTE_FLOORS',
                'display_name'   => 'Green Homes — Quote: Floors',
                'flow_group'     => 'GREEN_HOMES_QUOTE',
                'business_vertical' => 'Green Homes',
                'body_text'      => "How many floors are you planning?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_quote_floors',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_QUOTE_HOME_TYPE',
                'display_name'   => 'Green Homes — Quote: Home Type',
                'flow_group'     => 'GREEN_HOMES_QUOTE',
                'business_vertical' => 'Green Homes',
                'body_text'      => "What type of home are you planning?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_quote_home_type',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_QUOTE_BUDGET',
                'display_name'   => 'Green Homes — Quote: Budget',
                'flow_group'     => 'GREEN_HOMES_QUOTE',
                'business_vertical' => 'Green Homes',
                'body_text'      => "What is your approximate construction budget?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_quote_budget',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_QUOTE_TIMELINE',
                'display_name'   => 'Green Homes — Quote: Timeline',
                'flow_group'     => 'GREEN_HOMES_QUOTE',
                'business_vertical' => 'Green Homes',
                'body_text'      => "When are you planning to start construction?",
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_quote_timeline',
                'meta_template_status' => 'not_connected',
            ],
            [
                'internal_name'  => 'GH_QUOTE_FINAL',
                'display_name'   => 'Green Homes — Quote: Final',
                'flow_group'     => 'GREEN_HOMES_QUOTE',
                'business_vertical' => 'Green Homes',
                'body_text'      => "Thank you, {{name}}.\n\nWe have received your home construction requirements.\n\nOur Green Homes expert will assist you with the next steps.\n\n📞 Call our expert:\n{$ep}",
                'buttons'        => [
                    ['label' => '📞 Call Our Expert', 'payload' => "tel:{$epIntl}", 'action_type' => 'call', 'action_value' => $epIntl],
                    ['label' => '👨‍💼 Talk to Expert', 'payload' => 'EXPERT',        'action_type' => 'reply'],
                    ['label' => '🏠 Main Menu',       'payload' => 'MAIN_MENU',     'action_type' => 'reply'],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'gh_quote_final',
                'meta_template_status' => 'not_connected',
            ],
            // Human Handover
            [
                'internal_name'  => 'HUMAN_HANDOVER',
                'display_name'   => 'Human Handover',
                'flow_group'     => 'HUMAN_HANDOVER',
                'body_text'      => "You have been connected with our team.\n\nA Green Hill Group expert will reach out to you shortly.\n\n📞 You can also call us directly:\n{$ep}",
                'buttons'        => [
                    ['label' => '📞 Call Our Expert', 'payload' => "tel:{$epIntl}", 'action_type' => 'call', 'action_value' => $epIntl],
                ],
                'message_type'   => 'interactive',
                'meta_template_name' => 'human_handover',
                'meta_template_status' => 'not_connected',
            ],
        ];

        $allMessages = array_merge($messages, $siteVisitMessages, $greenHomesMessages);

        foreach ($allMessages as $data) {
            BotMessage::create(array_merge([
                'flow_group'     => null,
                'business_vertical' => null,
                'header_text'    => null,
                'footer_text'    => null,
                'buttons'        => null,
                'list_sections'  => null,
                'list_button_label' => null,
                'meta_template_language' => 'en',
            ], $data));
        }
    }

    private function seedFlows(): void
    {
        // Helper to get a BotMessage by internal name
        $msg = fn(string $name) => BotMessage::where('internal_name', $name)->firstOrFail();

        // ═══════════════════════════════════════════════════════════════
        // FLOW 1: GREEN_HILL_MAIN (Default Welcome)
        // ═══════════════════════════════════════════════════════════════
        $flow1 = Flow::create([
            'name'               => 'Green Hill Group — Main Flow',
            'group_name'         => 'GREEN_HILL_MAIN',
            'business_vertical'  => null,
            'is_active'          => true,
            'is_default_welcome' => true,
        ]);

        $n_welcome = FlowNode::create([
            'flow_id'        => $flow1->id,
            'bot_message_id' => $msg('WELCOME_MAIN')->id,
            'name'           => 'Welcome Message',
            'type'           => 'template',
            'template_name'  => 'first_message',
            'is_start'       => true,
            'sort_order'     => 1,
            'input_type'     => 'button',
        ]);

        // ═══════════════════════════════════════════════════════════════
        // FLOW 2: GREEN_HILL_PROJECTS
        // ═══════════════════════════════════════════════════════════════
        $flow2 = Flow::create([
            'name'              => 'Green Hill Group — Projects Flow',
            'group_name'        => 'GREEN_HILL_PROJECTS',
            'business_vertical' => 'Green Hill Group',
            'is_active'         => true,
        ]);

        $n_ghg_menu = FlowNode::create([
            'flow_id'        => $flow2->id,
            'bot_message_id' => $msg('GHG_MENU')->id,
            'name'           => 'GHG Main Menu',
            'type'           => 'text',
            'is_start'       => true,
            'sort_order'     => 1,
            'input_type'     => 'button',
        ]);

        $n_projects_list = FlowNode::create([
            'flow_id'              => $flow2->id,
            'bot_message_id'       => $msg('GHG_PROJECTS_LIST')->id,
            'name'                 => 'Projects List',
            'type'                 => 'text',
            'expected_lead_field'  => 'project',
            'sort_order'           => 2,
            'input_type'           => 'list',
        ]);

        // Edge: GHG_MENU → Projects List
        FlowEdge::create(['source_node_id' => $n_ghg_menu->id, 'target_node_id' => $n_projects_list->id, 'condition_payload' => 'GHG_PROJECTS']);

        // Expert CTA node in this flow
        $n_expert_ghg = FlowNode::create([
            'flow_id'                => $flow2->id,
            'bot_message_id'         => $msg('EXPERT_CTA')->id,
            'name'                   => 'Expert CTA',
            'type'                   => 'text',
            'triggers_human_handover'=> true,
            'sort_order'             => 3,
            'input_type'             => 'button',
        ]);

        FlowEdge::create(['source_node_id' => $n_ghg_menu->id, 'target_node_id' => $n_expert_ghg->id, 'condition_payload' => 'EXPERT']);

        // Per-project nodes
        $projectDefs = [
            'green_exotica'  => [['2 BHK', '2_bhk'], ['3 BHK', '3_bhk']],
            'green_elegance' => [['2 BHK', '2_bhk'], ['3 BHK', '3_bhk']],
            'green_retreat'  => [['2 BHK', '2_bhk'], ['3 BHK', '3_bhk']],
            'green_ville'    => [['2 BHK', '2_bhk'], ['3 BHK', '3_bhk'], ['4 BHK', '4_bhk']],
            'green_delight'  => [['2 BHK', '2_bhk']],
            'europa'         => [['Villa', 'villa']],
            'silo_kutir'     => [['2 BHK', '2_bhk'], ['3 BHK', '3_bhk']],
            'aura'           => [['2 BHK', '2_bhk'], ['3 BHK', '3_bhk']],
        ];

        $sortOrder = 10;
        foreach ($projectDefs as $slug => $configs) {
            // Project select node
            $n_proj_select = FlowNode::create([
                'flow_id'             => $flow2->id,
                'bot_message_id'      => $msg(strtoupper("PROJ_{$slug}_SELECT"))->id,
                'name'                => "Project: " . ucwords(str_replace('_', ' ', $slug)),
                'type'                => 'text',
                'expected_lead_field' => 'project',
                'sort_order'          => $sortOrder++,
                'input_type'          => 'button',
            ]);

            // Edge from projects list → project select
            FlowEdge::create([
                'source_node_id'  => $n_projects_list->id,
                'target_node_id'  => $n_proj_select->id,
                'condition_payload' => "proj_{$slug}",
                'save_value'      => ucwords(str_replace('_', ' ', $slug)),
            ]);
            FlowEdge::create(['source_node_id' => $n_proj_select->id, 'target_node_id' => $n_expert_ghg->id, 'condition_payload' => 'EXPERT']);

            // Config detail nodes
            foreach ($configs as [$cfgLabel, $cfgSlug]) {
                $n_cfg = FlowNode::create([
                    'flow_id'             => $flow2->id,
                    'bot_message_id'      => $msg(strtoupper("PROJ_{$slug}_{$cfgSlug}_DETAIL"))->id,
                    'name'                => ucwords(str_replace('_', ' ', $slug)) . " — {$cfgLabel}",
                    'type'                => 'text',
                    'expected_lead_field' => 'configuration',
                    'sort_order'          => $sortOrder++,
                    'input_type'          => 'button',
                ]);

                FlowEdge::create([
                    'source_node_id'  => $n_proj_select->id,
                    'target_node_id'  => $n_cfg->id,
                    'condition_payload' => "proj_{$slug}_{$cfgSlug}",
                    'save_value'      => $cfgLabel,
                ]);
                FlowEdge::create(['source_node_id' => $n_cfg->id, 'target_node_id' => $n_expert_ghg->id, 'condition_payload' => 'EXPERT']);
            }
        }

        // Expert CTA → edges (call and main menu handled by BotEngine special logic)

        // ═══════════════════════════════════════════════════════════════
        // FLOW 3: GREEN_HILL_SITE_VISIT
        // ═══════════════════════════════════════════════════════════════
        $flow3 = Flow::create([
            'name'              => 'Green Hill Group — Site Visit Flow',
            'group_name'        => 'GREEN_HILL_SITE_VISIT',
            'business_vertical' => 'Green Hill Group',
            'is_active'         => true,
        ]);

        $n_sv_name = FlowNode::create([
            'flow_id'             => $flow3->id,
            'bot_message_id'      => $msg('SITE_VISIT_ASK_NAME')->id,
            'name'                => 'Site Visit: Ask Name',
            'type'                => 'text',
            'expected_lead_field' => 'customer_name',
            'is_start'            => true,
            'sort_order'          => 1,
            'input_type'          => 'text_input',
        ]);

        $n_sv_date = FlowNode::create([
            'flow_id'             => $flow3->id,
            'bot_message_id'      => $msg('SITE_VISIT_ASK_DATE')->id,
            'name'                => 'Site Visit: Ask Date',
            'type'                => 'text',
            'expected_lead_field' => 'site_visit_date',
            'sort_order'          => 2,
            'input_type'          => 'text_input',
        ]);

        $n_sv_time = FlowNode::create([
            'flow_id'             => $flow3->id,
            'bot_message_id'      => $msg('SITE_VISIT_ASK_TIME')->id,
            'name'                => 'Site Visit: Ask Time',
            'type'                => 'text',
            'expected_lead_field' => 'site_visit_time',
            'sort_order'          => 3,
            'input_type'          => 'button',
        ]);

        $n_sv_confirmed = FlowNode::create([
            'flow_id'        => $flow3->id,
            'bot_message_id' => $msg('SITE_VISIT_CONFIRMED')->id,
            'name'           => 'Site Visit: Confirmed',
            'type'           => 'text',
            'sort_order'     => 4,
            'input_type'     => 'button',
        ]);

        // Edges: text input → next node via wildcard '*'
        FlowEdge::create(['source_node_id' => $n_sv_name->id, 'target_node_id' => $n_sv_date->id, 'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_sv_date->id, 'target_node_id' => $n_sv_time->id, 'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_sv_time->id, 'target_node_id' => $n_sv_confirmed->id, 'condition_payload' => 'sv_time_morning',   'save_value' => '10 AM – 1 PM']);
        FlowEdge::create(['source_node_id' => $n_sv_time->id, 'target_node_id' => $n_sv_confirmed->id, 'condition_payload' => 'sv_time_afternoon', 'save_value' => '1 PM – 4 PM']);
        FlowEdge::create(['source_node_id' => $n_sv_time->id, 'target_node_id' => $n_sv_confirmed->id, 'condition_payload' => 'sv_time_evening',   'save_value' => '4 PM – 7 PM']);

        // ═══════════════════════════════════════════════════════════════
        // FLOW 4: GREEN_HOMES_MAIN
        // ═══════════════════════════════════════════════════════════════
        $flow4 = Flow::create([
            'name'              => 'Green Homes — Main Flow',
            'group_name'        => 'GREEN_HOMES_MAIN',
            'business_vertical' => 'Green Homes',
            'is_active'         => true,
        ]);

        $n_gh_welcome = FlowNode::create([
            'flow_id'        => $flow4->id,
            'bot_message_id' => $msg('GH_WELCOME')->id,
            'name'           => 'Green Homes Welcome',
            'type'           => 'text',
            'is_start'       => true,
            'sort_order'     => 1,
            'input_type'     => 'button',
        ]);

        // ═══════════════════════════════════════════════════════════════
        // FLOW 5: GREEN_HOMES_BUILD
        // ═══════════════════════════════════════════════════════════════
        $flow5 = Flow::create([
            'name'              => 'Green Homes — Build My Home Flow',
            'group_name'        => 'GREEN_HOMES_BUILD',
            'business_vertical' => 'Green Homes',
            'is_active'         => true,
        ]);

        $n_gh_own_land = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_OWN_LAND')->id,
            'name'                => 'Own Land?',
            'type'                => 'text',
            'expected_lead_field' => 'owns_land',
            'is_start'            => true,
            'sort_order'          => 1,
            'input_type'          => 'button',
        ]);

        $n_gh_home_type = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_HOME_TYPE')->id,
            'name'                => 'Home Type',
            'type'                => 'text',
            'expected_lead_field' => 'home_type',
            'sort_order'          => 2,
            'input_type'          => 'button',
        ]);

        $n_gh_floors = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_FLOORS')->id,
            'name'                => 'Floors',
            'type'                => 'text',
            'expected_lead_field' => 'floors',
            'sort_order'          => 3,
            'input_type'          => 'list',
        ]);

        $n_gh_area = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_AREA')->id,
            'name'                => 'Built-up Area',
            'type'                => 'text',
            'expected_lead_field' => 'builtup_area',
            'sort_order'          => 4,
            'input_type'          => 'list',
        ]);

        $n_gh_budget = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_BUDGET')->id,
            'name'                => 'Budget',
            'type'                => 'text',
            'expected_lead_field' => 'budget',
            'sort_order'          => 5,
            'input_type'          => 'list',
        ]);

        $n_gh_timeline = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_TIMELINE')->id,
            'name'                => 'Timeline',
            'type'                => 'text',
            'expected_lead_field' => 'timeline',
            'sort_order'          => 6,
            'input_type'          => 'list',
        ]);

        $n_gh_qualified = FlowNode::create([
            'flow_id'        => $flow5->id,
            'bot_message_id' => $msg('GH_QUALIFIED')->id,
            'name'           => 'Qualified',
            'type'           => 'text',
            'sort_order'     => 7,
            'input_type'     => 'button',
        ]);

        // Expert node in build flow
        $n_expert_build = FlowNode::create([
            'flow_id'                => $flow5->id,
            'bot_message_id'         => $msg('EXPERT_CTA')->id,
            'name'                   => 'Expert CTA',
            'type'                   => 'text',
            'triggers_human_handover'=> true,
            'sort_order'             => 8,
            'input_type'             => 'button',
        ]);

        // No land path
        $n_gh_no_land_menu = FlowNode::create([
            'flow_id'        => $flow5->id,
            'bot_message_id' => $msg('GH_NO_LAND_MENU')->id,
            'name'           => 'No Land Menu',
            'type'           => 'text',
            'sort_order'     => 9,
            'input_type'     => 'button',
        ]);

        $n_gh_land_loc = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_LAND_LOCATION')->id,
            'name'                => 'Land Location',
            'type'                => 'text',
            'expected_lead_field' => 'land_location',
            'sort_order'          => 10,
            'input_type'          => 'text_input',
        ]);

        $n_gh_land_budget = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_LAND_BUDGET')->id,
            'name'                => 'Land Budget',
            'type'                => 'text',
            'expected_lead_field' => 'budget',
            'sort_order'          => 11,
            'input_type'          => 'text_input',
        ]);

        $n_gh_land_ht = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_LAND_HOME_TYPE')->id,
            'name'                => 'Land Home Type',
            'type'                => 'text',
            'expected_lead_field' => 'home_type',
            'sort_order'          => 12,
            'input_type'          => 'text_input',
        ]);

        $n_gh_land_tl = FlowNode::create([
            'flow_id'             => $flow5->id,
            'bot_message_id'      => $msg('GH_LAND_TIMELINE')->id,
            'name'                => 'Land Timeline',
            'type'                => 'text',
            'expected_lead_field' => 'timeline',
            'sort_order'          => 13,
            'input_type'          => 'text_input',
        ]);

        $n_gh_no_land_final = FlowNode::create([
            'flow_id'        => $flow5->id,
            'bot_message_id' => $msg('GH_NO_LAND_FINAL')->id,
            'name'           => 'No Land Final',
            'type'           => 'text',
            'sort_order'     => 14,
            'input_type'     => 'button',
        ]);

        // Edges: Build My Home YES path
        FlowEdge::create(['source_node_id' => $n_gh_own_land->id, 'target_node_id' => $n_gh_home_type->id, 'condition_payload' => 'GH_LAND_YES', 'save_value' => 'Yes']);
        FlowEdge::create(['source_node_id' => $n_gh_own_land->id, 'target_node_id' => $n_gh_no_land_menu->id, 'condition_payload' => 'GH_LAND_NO', 'save_value' => 'No']);
        FlowEdge::create(['source_node_id' => $n_gh_own_land->id, 'target_node_id' => $n_expert_build->id, 'condition_payload' => 'EXPERT']);
        // Home type → Floors (any button)
        foreach (['ht_independent', 'ht_villa', 'ht_other'] as $payload) {
            $label = match($payload) { 'ht_independent' => 'Independent House', 'ht_villa' => 'Villa', default => 'Other' };
            FlowEdge::create(['source_node_id' => $n_gh_home_type->id, 'target_node_id' => $n_gh_floors->id, 'condition_payload' => $payload, 'save_value' => $label]);
        }
        FlowEdge::create(['source_node_id' => $n_gh_home_type->id, 'target_node_id' => $n_expert_build->id, 'condition_payload' => 'EXPERT']);
        // Floors → Area (any list item)
        $floorValues = ['fl_ground' => 'Ground Floor', 'fl_g1' => 'G+1', 'fl_g2' => 'G+2', 'fl_g3plus' => 'G+3+', 'fl_undecided' => 'Not Decided'];
        foreach ($floorValues as $payload => $label) {
            FlowEdge::create(['source_node_id' => $n_gh_floors->id, 'target_node_id' => $n_gh_area->id, 'condition_payload' => $payload, 'save_value' => $label]);
        }
        FlowEdge::create(['source_node_id' => $n_gh_floors->id, 'target_node_id' => $n_expert_build->id, 'condition_payload' => 'EXPERT']);
        // Area → Budget
        $areaValues = ['area_under_1500' => 'Under 1,500 sq.ft.', 'area_1500_2500' => '1,500–2,500 sq.ft.', 'area_2500_4000' => '2,500–4,000 sq.ft.', 'area_4000plus' => '4,000+ sq.ft.', 'area_undecided' => 'Not Decided'];
        foreach ($areaValues as $payload => $label) {
            FlowEdge::create(['source_node_id' => $n_gh_area->id, 'target_node_id' => $n_gh_budget->id, 'condition_payload' => $payload, 'save_value' => $label]);
        }
        FlowEdge::create(['source_node_id' => $n_gh_area->id, 'target_node_id' => $n_expert_build->id, 'condition_payload' => 'EXPERT']);
        // Budget → Timeline
        $budgetValues = ['bud_under50' => 'Under ₹50 Lakh', 'bud_50_1cr' => '₹50L–₹1 Crore', 'bud_1_2cr' => '₹1–2 Crore', 'bud_2crplus' => '₹2 Crore+', 'bud_guidance' => 'Need Guidance'];
        foreach ($budgetValues as $payload => $label) {
            FlowEdge::create(['source_node_id' => $n_gh_budget->id, 'target_node_id' => $n_gh_timeline->id, 'condition_payload' => $payload, 'save_value' => $label]);
        }
        FlowEdge::create(['source_node_id' => $n_gh_budget->id, 'target_node_id' => $n_expert_build->id, 'condition_payload' => 'EXPERT']);
        // Timeline → Qualified
        $timelineValues = ['tl_3months' => 'Within 3 Months', 'tl_3_6' => '3–6 Months', 'tl_6_12' => '6–12 Months', 'tl_later' => 'Later'];
        foreach ($timelineValues as $payload => $label) {
            FlowEdge::create(['source_node_id' => $n_gh_timeline->id, 'target_node_id' => $n_gh_qualified->id, 'condition_payload' => $payload, 'save_value' => $label]);
        }
        FlowEdge::create(['source_node_id' => $n_gh_timeline->id, 'target_node_id' => $n_expert_build->id, 'condition_payload' => 'EXPERT']);

        // No Land path edges
        FlowEdge::create(['source_node_id' => $n_gh_no_land_menu->id, 'target_node_id' => $n_gh_land_loc->id, 'condition_payload' => 'GH_LOOK_LAND']);
        FlowEdge::create(['source_node_id' => $n_gh_no_land_menu->id, 'target_node_id' => $n_gh_land_loc->id, 'condition_payload' => 'GH_PLAN_LAND']);
        FlowEdge::create(['source_node_id' => $n_gh_no_land_menu->id, 'target_node_id' => $n_expert_build->id, 'condition_payload' => 'EXPERT']);
        FlowEdge::create(['source_node_id' => $n_gh_land_loc->id,    'target_node_id' => $n_gh_land_budget->id, 'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_gh_land_budget->id, 'target_node_id' => $n_gh_land_ht->id,     'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_gh_land_ht->id,     'target_node_id' => $n_gh_land_tl->id,     'condition_payload' => '*']);
        FlowEdge::create(['source_node_id' => $n_gh_land_tl->id,     'target_node_id' => $n_gh_no_land_final->id,'condition_payload' => '*']);

        // ═══════════════════════════════════════════════════════════════
        // FLOW 6: GREEN_HOMES_QUOTE
        // ═══════════════════════════════════════════════════════════════
        $flow6 = Flow::create([
            'name'              => 'Green Homes — Get a Quote Flow',
            'group_name'        => 'GREEN_HOMES_QUOTE',
            'business_vertical' => 'Green Homes',
            'is_active'         => true,
        ]);

        $quoteFields = [
            ['GH_QUOTE_NAME',      'customer_name',  'text_input', true],
            ['GH_QUOTE_LOCATION',  'land_location',  'text_input', false],
            ['GH_QUOTE_PLOT_SIZE', 'plot_size',       'text_input', false],
            ['GH_QUOTE_BUILTUP',   'builtup_area',    'text_input', false],
            ['GH_QUOTE_FLOORS',    'floors',          'text_input', false],
            ['GH_QUOTE_HOME_TYPE', 'home_type',       'text_input', false],
            ['GH_QUOTE_BUDGET',    'budget',          'text_input', false],
            ['GH_QUOTE_TIMELINE',  'timeline',        'text_input', false],
            ['GH_QUOTE_FINAL',     null,             'button',     false],
        ];

        $prevNode = null;
        foreach ($quoteFields as $i => [$msgName, $leadField, $inputType, $isStart]) {
            $node = FlowNode::create([
                'flow_id'             => $flow6->id,
                'bot_message_id'      => $msg($msgName)->id,
                'name'                => str_replace(['GH_QUOTE_', '_'], ['', ' '], $msgName),
                'type'                => 'text',
                'expected_lead_field' => $leadField,
                'is_start'            => $isStart,
                'sort_order'          => $i + 1,
                'input_type'          => $inputType,
            ]);

            if ($prevNode) {
                FlowEdge::create(['source_node_id' => $prevNode->id, 'target_node_id' => $node->id, 'condition_payload' => '*']);
            }
            $prevNode = $node;
        }

        // ═══════════════════════════════════════════════════════════════
        // FLOW 7: HUMAN_HANDOVER
        // ═══════════════════════════════════════════════════════════════
        $flow7 = Flow::create([
            'name'       => 'Human Handover Flow',
            'group_name' => 'HUMAN_HANDOVER',
            'is_active'  => true,
        ]);

        FlowNode::create([
            'flow_id'                => $flow7->id,
            'bot_message_id'         => $msg('HUMAN_HANDOVER')->id,
            'name'                   => 'Human Handover',
            'type'                   => 'text',
            'triggers_human_handover'=> true,
            'is_start'               => true,
            'sort_order'             => 1,
            'input_type'             => 'button',
        ]);

        // ─── Cross-flow routing: Welcome → GHG / GH / Expert ─────────────────────────
        // Welcome button → GHG Menu node (redirect edge to another flow via special logic in BotEngine)
        // We store these as "virtual" edges. BotEngine will look up the target node by bot_message_id.

        // Site Visit: triggered from any project detail node via SITE_VISIT_START payload
        // BotEngine handles this by finding flow3 start node when it encounters SITE_VISIT_START

        $this->command->info('✅ GreenHomesFlowSeeder complete!');
        $this->command->info('   Flows: 7');
        $this->command->info('   Projects: 8');
        $this->command->info('   BotMessages: ' . BotMessage::count());
        $this->command->info('   FlowNodes: ' . FlowNode::count());
        $this->command->info('   FlowEdges: ' . FlowEdge::count());
    }

    private function slugify(string $text): string
    {
        return strtolower(str_replace([' ', '+'], ['_', 'plus'], $text));
    }
}
