<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;

class SettingsManager extends Component
{
    public $settings = [];
    public $successMsg = '';

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $this->settings = Setting::all()->keyBy('key')->toArray();
    }

    public function save()
    {
        foreach ($this->settings as $key => $data) {
            Setting::where('key', $key)->update(['value' => $data['value'] ?? '']);
        }
        $this->successMsg = '✅ Settings saved!';
        $this->loadSettings();
    }

    public function render()
    {
        return view('livewire.settings-manager')->layout('layouts.app');
    }
}
