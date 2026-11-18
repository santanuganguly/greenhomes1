<?php

namespace App\Livewire\Marketing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Lead;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class CampaignCreate extends Component
{
    use WithFileUploads;

    public int $step = 1;

    // Step 1: Details
    public string $name = '';
    public string $project = '';
    public string $business_vertical = '';
    public string $description = '';

    // Step 2: Template
    public array $templates = [];
    public ?string $selectedTemplateName = null;
    public ?array $selectedTemplate = null;
    public array $variableMapping = []; // UI mapping to {{1}}, {{2}}

    // Step 3: Recipients
    public string $recipientSource = 'leads'; // 'leads' or 'csv'
    public $csvFile;
    public array $parsedCsvData = [];
    public int $leadsCount = 0;
    
    // Stats
    public int $totalValidRecipients = 0;
    public int $invalidCount = 0;
    public int $optOutCount = 0;
    public int $duplicateCount = 0;

    // Step 4: Preview & Test
    public string $testPhoneNumber = '';
    public ?array $previewData = null;

    public function mount(WhatsAppService $whatsapp)
    {
        $this->templates = $whatsapp->getTemplates();
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'name' => 'required|string|max:255|unique:campaigns,name',
                'project' => 'nullable|string|max:255',
            ]);
        } elseif ($this->step === 2) {
            $this->validate([
                'selectedTemplateName' => 'required|string',
            ]);
            $this->selectedTemplate = collect($this->templates)->firstWhere('name', $this->selectedTemplateName);
        } elseif ($this->step === 3) {
            $this->processRecipients();
            if ($this->totalValidRecipients === 0) {
                $this->addError('recipients', 'No valid recipients found. Please select leads or upload a valid CSV.');
                return;
            }
            $this->generatePreview();
        }

        $this->step++;
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function selectTemplate($name)
    {
        $this->selectedTemplateName = $name;
        $this->selectedTemplate = collect($this->templates)->firstWhere('name', $name);
        
        // Initialize variable mapping
        $this->variableMapping = [];
        if (isset($this->selectedTemplate['components'])) {
            foreach ($this->selectedTemplate['components'] as $component) {
                if ($component['type'] === 'BODY' && preg_match_all('/\{\{(\d+)\}\}/', $component['text'], $matches)) {
                    foreach ($matches[1] as $varIndex) {
                        $this->variableMapping[$varIndex] = ''; // To be mapped to a column/field
                    }
                }
            }
        }
    }

    public function updatedCsvFile()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $path = $this->csvFile->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        if (count($data) < 2) {
            $this->addError('csvFile', 'CSV must contain headers and at least one row of data.');
            return;
        }

        $headers = array_shift($data);
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        if (!in_array('phone', $headers)) {
            $this->addError('csvFile', 'CSV must contain a "phone" column.');
            return;
        }

        $parsed = [];
        foreach ($data as $row) {
            if (count($row) === count($headers)) {
                $parsed[] = array_combine($headers, array_map('trim', $row));
            }
        }

        $this->parsedCsvData = $parsed;
        $this->processRecipients();
    }

    protected function processRecipients()
    {
        $this->totalValidRecipients = 0;
        $this->invalidCount = 0;
        $this->optOutCount = 0;
        $this->duplicateCount = 0;

        if ($this->recipientSource === 'leads') {
            $query = Lead::query();
            if ($this->project) {
                $query->where('project', $this->project);
            }
            $this->leadsCount = $query->count();
            
            $optOuts = clone $query;
            $this->optOutCount = $optOuts->where('marketing_opt_out', true)->count();
            
            $this->totalValidRecipients = $this->leadsCount - $this->optOutCount;

        } elseif ($this->recipientSource === 'csv') {
            $uniquePhones = [];
            foreach ($this->parsedCsvData as $row) {
                $phone = $row['phone'] ?? '';
                if (empty($phone) || strlen($phone) < 10) {
                    $this->invalidCount++;
                    continue;
                }

                if (in_array($phone, $uniquePhones)) {
                    $this->duplicateCount++;
                    continue;
                }

                // Check opt out against DB
                $lead = Lead::where('phone_number', $phone)->first();
                if ($lead && $lead->marketing_opt_out) {
                    $this->optOutCount++;
                    continue;
                }

                $uniquePhones[] = $phone;
                $this->totalValidRecipients++;
            }
        }
    }

    protected function generatePreview()
    {
        if ($this->recipientSource === 'leads') {
            $sampleLead = Lead::where('marketing_opt_out', false)->first();
            if ($sampleLead) {
                $this->previewData = [
                    'phone' => $sampleLead->phone_number,
                    'variables' => $this->resolveVariablesForPreview([
                        'customer_name' => $sampleLead->customer_name,
                        'project' => $sampleLead->project,
                    ])
                ];
            }
        } elseif ($this->recipientSource === 'csv' && !empty($this->parsedCsvData)) {
            $sampleRow = $this->parsedCsvData[0];
            $this->previewData = [
                'phone' => $sampleRow['phone'],
                'variables' => $this->resolveVariablesForPreview($sampleRow)
            ];
        }
    }

    protected function resolveVariablesForPreview(array $data)
    {
        $resolved = [];
        foreach ($this->variableMapping as $index => $mappedColumn) {
            $resolved[$index] = $data[$mappedColumn] ?? ' '; // Missing values default to empty space
        }
        return $resolved;
    }

    public function sendTestMessage(WhatsAppService $whatsapp)
    {
        $this->validate([
            'testPhoneNumber' => 'required|string|min:10',
        ]);

        if (!$this->selectedTemplateName) {
            $this->addError('test_send', 'Template not selected.');
            return;
        }

        $components = [];
        if (!empty($this->previewData['variables'])) {
            $parameters = [];
            foreach ($this->previewData['variables'] as $val) {
                $parameters[] = ['type' => 'text', 'text' => $val];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $parameters
            ];
        }

        try {
            $response = $whatsapp->sendTemplate(
                $this->testPhoneNumber,
                $this->selectedTemplateName,
                'en',
                $components
            );

            if (isset($response['messages'])) {
                session()->flash('test_success', 'Test message sent successfully to ' . $this->testPhoneNumber);
            } else {
                session()->flash('test_error', 'Failed to send test message: ' . json_encode($response));
            }
        } catch (\Exception $e) {
            session()->flash('test_error', 'Exception: ' . $e->getMessage());
        }
    }

    public function saveCampaign()
    {
        if ($this->totalValidRecipients === 0) {
            $this->addError('recipients', 'Cannot save a campaign with 0 valid recipients.');
            return;
        }

        $campaign = Campaign::create([
            'name' => $this->name,
            'project' => $this->project,
            'business_vertical' => $this->business_vertical,
            'description' => $this->description,
            'template_name' => $this->selectedTemplateName,
            'status' => 'draft',
            'total_recipients' => $this->totalValidRecipients,
            'created_by' => auth()->id(),
        ]);

        // Insert Recipients
        if ($this->recipientSource === 'leads') {
            $query = Lead::where('marketing_opt_out', false);
            if ($this->project) {
                $query->where('project', $this->project);
            }
            
            // Chunked insertion for memory safety
            $query->chunk(500, function ($leads) use ($campaign) {
                $inserts = [];
                foreach ($leads as $lead) {
                    $vars = [];
                    foreach ($this->variableMapping as $idx => $col) {
                        $vars[$idx] = $lead->{$col} ?? ' ';
                    }
                    $inserts[] = [
                        'campaign_id' => $campaign->id,
                        'lead_id' => $lead->id,
                        'phone' => $lead->phone_number,
                        'name' => $lead->customer_name,
                        'variables' => json_encode($vars),
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                CampaignRecipient::insert($inserts);
            });
        } elseif ($this->recipientSource === 'csv') {
            $uniquePhones = [];
            $inserts = [];
            foreach ($this->parsedCsvData as $row) {
                $phone = $row['phone'] ?? '';
                if (empty($phone) || strlen($phone) < 10 || in_array($phone, $uniquePhones)) continue;

                $lead = Lead::where('phone_number', $phone)->first();
                if ($lead && $lead->marketing_opt_out) continue;

                $uniquePhones[] = $phone;

                $vars = [];
                foreach ($this->variableMapping as $idx => $col) {
                    $vars[$idx] = $row[$col] ?? ' ';
                }

                $inserts[] = [
                    'campaign_id' => $campaign->id,
                    'lead_id' => $lead ? $lead->id : null,
                    'phone' => $phone,
                    'name' => $row['name'] ?? null,
                    'variables' => json_encode($vars),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($inserts) >= 500) {
                    CampaignRecipient::insert($inserts);
                    $inserts = [];
                }
            }
            if (!empty($inserts)) {
                CampaignRecipient::insert($inserts);
            }
        }

        session()->flash('message', 'Campaign created successfully and saved as Draft.');
        return $this->redirectRoute('marketing.campaigns', navigate: true);
    }

    public function render()
    {
        return view('livewire.marketing.campaign-create');
    }
}
