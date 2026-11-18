<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;

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

    protected function updateEnvironmentFile($key, $value)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            // Escape double quotes in value
            $escapedValue = '"' . str_replace('"', '\"', $value) . '"';
            
            // Check if key exists
            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $content);
            } else {
                $content .= "\n{$key}={$escapedValue}\n";
            }
            
            file_put_contents($path, $content);
        }
    }

    public function save()
    {
        $envUpdated = false;

        foreach ($this->settings as $key => $data) {
            $value = $data['value'] ?? '';
            Setting::where('key', $key)->update(['value' => $value]);

            if (in_array($key, ['meta_access_token', 'meta_phone_number_id', 'meta_waba_id', 'meta_verify_token', 'meta_app_id', 'meta_app_secret'])) {
                $envKey = strtoupper($key);
                $this->updateEnvironmentFile($envKey, $value);
                $envUpdated = true;
            }
        }

        if ($envUpdated) {
            try {
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
            } catch (\Exception $e) {
                // Ignore if artisan fails in restricted environments
            }
            $this->successMsg = '✅ Settings saved and live server .env updated!';
        } else {
            $this->successMsg = '✅ Settings saved!';
        }

        $this->loadSettings();
    }

    public function render()
    {
        return view('livewire.settings-manager')->layout('layouts.app');
    }
}
