<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\BulkCampaign;
use App\Models\BulkMessage;
use App\Jobs\SendBulkMessageJob;
use Illuminate\Support\Facades\Log;

class BulkMessagingManager extends Component
{
    use WithFileUploads;

    public $name;
    public $message_template = 'Hi {name}, ';
    public $file;
    public $campaigns;
    
    public $successMsg = '';
    public $errorMsg = '';

    public function mount()
    {
        $this->loadCampaigns();
    }

    public function loadCampaigns()
    {
        $this->campaigns = BulkCampaign::withCount('messages')->latest()->get();
    }

    public function createCampaign()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'message_template' => 'required|string',
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $path = $this->file->getRealPath();
        $file = fopen($path, 'r');
        
        // Read headers
        $headers = fgetcsv($file);
        if (!$headers) {
            $this->errorMsg = 'Invalid CSV file format.';
            return;
        }

        $headers = array_map('strtolower', array_map('trim', $headers));
        
        $nameIndex = array_search('name', $headers);
        $phoneIndex = array_search('phone', $headers);
        
        if ($phoneIndex === false) {
            $phoneIndex = array_search('number', $headers); // fallback
        }

        if ($phoneIndex === false) {
            $this->errorMsg = 'CSV must contain a column named "phone" or "number".';
            return;
        }

        $contacts = [];
        while (($data = fgetcsv($file)) !== false) {
            if (!isset($data[$phoneIndex]) || empty($data[$phoneIndex])) continue;
            
            $phone = preg_replace('/[^0-9]/', '', $data[$phoneIndex]);
            if (empty($phone)) continue;

            $contactName = '';
            if ($nameIndex !== false && isset($data[$nameIndex])) {
                $contactName = trim($data[$nameIndex]);
            }

            $contacts[] = [
                'phone' => $phone,
                'name' => $contactName
            ];
        }
        fclose($file);

        if (empty($contacts)) {
            $this->errorMsg = 'No valid contacts found in the CSV.';
            return;
        }

        $campaign = BulkCampaign::create([
            'name' => $this->name,
            'message_template' => $this->message_template,
            'total_contacts' => count($contacts)
        ]);

        foreach ($contacts as $contact) {
            $bulkMsg = BulkMessage::create([
                'bulk_campaign_id' => $campaign->id,
                'phone_number' => $contact['phone'],
                'contact_name' => $contact['name'],
                'status' => 'pending'
            ]);

            SendBulkMessageJob::dispatchSync($bulkMsg->id);
        }

        $this->successMsg = "Campaign '{$this->name}' created and " . count($contacts) . " messages queued!";
        $this->reset(['name', 'message_template', 'file']);
        $this->loadCampaigns();
    }

    public function render()
    {
        return view('livewire.bulk-messaging-manager')->layout('layouts.app');
    }
}
