<?php

namespace App\Livewire\SaaS;

use Livewire\Component;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class OnboardingWizard extends Component
{
    public $currentStep = 1;
    public $totalSteps = 8;
    public $progress = 0;

    // Step 1: Company Information
    public $company_name, $legal_name, $industry, $company_email, $company_phone, $website;
    
    // Step 2: Create Admin Profile
    public $admin_name, $admin_email, $admin_password, $admin_password_confirmation;

    // Step 3-7 fields (stubs for this iteration)
    public $meta_access_token, $phone_number_id, $waba_id;
    
    public $organization_id;

    public function mount()
    {
        $this->updateProgress();
    }

    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep == 2) {
            $this->createTenant();
        }

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            $this->updateProgress();
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->updateProgress();
        }
    }

    public function updateProgress()
    {
        $this->progress = round(($this->currentStep / $this->totalSteps) * 100);
    }

    protected function validateCurrentStep()
    {
        if ($this->currentStep == 1) {
            $this->validate([
                'company_name' => 'required|min:3',
                'company_email' => 'required|email',
            ]);
        } elseif ($this->currentStep == 2) {
            $this->validate([
                'admin_name' => 'required|min:3',
                'admin_email' => 'required|email|unique:users,email',
                'admin_password' => 'required|min:8|confirmed',
            ]);
        } elseif ($this->currentStep == 4) {
            $this->validate([
                'meta_access_token' => 'nullable|string',
                'phone_number_id' => 'nullable|string',
                'waba_id' => 'nullable|string',
            ]);
        }
    }

    protected function createTenant()
    {
        // 1. Create Organization
        $org = Organization::create([
            'name' => $this->company_name,
            'legal_name' => $this->legal_name,
            'industry' => $this->industry,
            'email' => $this->company_email,
            'phone' => $this->company_phone,
            'website' => $this->website,
        ]);

        $this->organization_id = $org->id;

        // 2. Create Organization Settings
        OrganizationSetting::create([
            'organization_id' => $org->id,
            'primary_color' => '#a9c43d'
        ]);

        // 3. Create Admin Role
        $role = \App\Models\Role::create([
            'organization_id' => $org->id,
            'name' => 'Admin'
        ]);

        // 4. Create Admin User
        $user = User::create([
            'name' => $this->admin_name,
            'email' => $this->admin_email,
            'password' => Hash::make($this->admin_password),
            'organization_id' => $org->id,
            'role_id' => $role->id,
            'is_super_admin' => false,
        ]);

        // Login as the new admin user
        Auth::login($user);
    }

    public function completeSetup()
    {
        if ($this->organization_id) {
            $settings = OrganizationSetting::where('organization_id', $this->organization_id)->first();
            if ($settings) {
                $settings->update([
                    'meta_access_token' => $this->meta_access_token,
                    'phone_number_id' => $this->phone_number_id,
                    'waba_id' => $this->waba_id,
                ]);
            }
        }
        
        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.saas.onboarding-wizard')->layout('layouts.guest');
    }
}
