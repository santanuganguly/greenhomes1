<div class="min-h-screen bg-black text-[#a9c43d] font-mono p-6">
    <div class="max-w-3xl mx-auto border border-[#a9c43d] p-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold uppercase tracking-widest text-white">> SAAS_INITIALIZATION_PROTOCOL</h1>
            <p class="text-sm text-[#5f7022] mt-2">Setting up a new secure tenant container.</p>
        </div>

        <!-- PROGRESS BAR -->
        <div class="mb-8">
            <div class="flex justify-between text-xs text-[#a9c43d] mb-1">
                <span>SETUP PROGRESS</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="w-full bg-[#111] h-2">
                <div class="bg-[#a9c43d] h-2 transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        <form wire:submit.prevent="nextStep">
            
            @if ($currentStep == 1)
                <!-- STEP 1: COMPANY INFO -->
                <div class="space-y-4">
                    <h2 class="text-xl text-white border-b border-[#3d4a13] pb-2 mb-4">Step 1: Organization Data</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase text-[#5f7022] mb-1">Company Name *</label>
                            <input type="text" wire:model="company_name" class="w-full bg-[#050505] border border-[#a9c43d] text-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#a9c43d]">
                            @error('company_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs uppercase text-[#5f7022] mb-1">Legal Name</label>
                            <input type="text" wire:model="legal_name" class="w-full bg-[#050505] border border-[#3d4a13] text-white px-3 py-2 text-sm focus:outline-none focus:border-[#a9c43d]">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase text-[#5f7022] mb-1">Business Email *</label>
                            <input type="email" wire:model="company_email" class="w-full bg-[#050505] border border-[#a9c43d] text-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#a9c43d]">
                            @error('company_email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs uppercase text-[#5f7022] mb-1">Industry</label>
                            <input type="text" wire:model="industry" class="w-full bg-[#050505] border border-[#3d4a13] text-white px-3 py-2 text-sm focus:outline-none focus:border-[#a9c43d]">
                        </div>
                    </div>
                </div>

            @elseif ($currentStep == 2)
                <!-- STEP 2: ADMIN PROFILE -->
                <div class="space-y-4">
                    <h2 class="text-xl text-white border-b border-[#3d4a13] pb-2 mb-4">Step 2: SuperUser Creation</h2>
                    
                    <div>
                        <label class="block text-xs uppercase text-[#5f7022] mb-1">Full Name *</label>
                        <input type="text" wire:model="admin_name" class="w-full bg-[#050505] border border-[#a9c43d] text-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#a9c43d]">
                        @error('admin_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs uppercase text-[#5f7022] mb-1">Login Email *</label>
                        <input type="email" wire:model="admin_email" class="w-full bg-[#050505] border border-[#a9c43d] text-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#a9c43d]">
                        @error('admin_email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase text-[#5f7022] mb-1">Password *</label>
                            <input type="password" wire:model="admin_password" class="w-full bg-[#050505] border border-[#a9c43d] text-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#a9c43d]">
                            @error('admin_password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs uppercase text-[#5f7022] mb-1">Confirm Password *</label>
                            <input type="password" wire:model="admin_password_confirmation" class="w-full bg-[#050505] border border-[#a9c43d] text-white px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#a9c43d]">
                        </div>
                    </div>
                </div>

            @elseif ($currentStep == 3)
                <!-- STEP 3: EMPLOYEES (STUB) -->
                <div class="space-y-4 text-center py-8">
                    <h2 class="text-xl text-white mb-2">Step 3: Add Employees</h2>
                    <p class="text-sm text-[#5f7022]">You can invite employees later from the dashboard.</p>
                </div>

            @elseif ($currentStep == 4)
                <!-- STEP 4: WHATSAPP -->
                <div class="space-y-4">
                    <h2 class="text-xl text-white border-b border-[#3d4a13] pb-2 mb-4">Step 4: Meta API Integration</h2>
                    
                    <div>
                        <label class="block text-xs uppercase text-[#5f7022] mb-1">Meta Access Token</label>
                        <input type="text" wire:model="meta_access_token" class="w-full bg-[#050505] border border-[#3d4a13] text-white px-3 py-2 text-sm focus:outline-none focus:border-[#a9c43d]">
                        @error('meta_access_token') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase text-[#5f7022] mb-1">Phone Number ID</label>
                            <input type="text" wire:model="phone_number_id" class="w-full bg-[#050505] border border-[#3d4a13] text-white px-3 py-2 text-sm focus:outline-none focus:border-[#a9c43d]">
                            @error('phone_number_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs uppercase text-[#5f7022] mb-1">WABA ID</label>
                            <input type="text" wire:model="waba_id" class="w-full bg-[#050505] border border-[#3d4a13] text-white px-3 py-2 text-sm focus:outline-none focus:border-[#a9c43d]">
                            @error('waba_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

            @elseif ($currentStep == 5)
                <!-- STEP 5: CRM CONFIG (STUB) -->
                <div class="space-y-4 text-center py-8">
                    <h2 class="text-xl text-white mb-2">Step 5: CRM Configuration</h2>
                    <p class="text-sm text-[#5f7022]">Default stages and lead settings will be applied.</p>
                </div>
            
            @elseif ($currentStep == 6)
                <!-- STEP 6: IMPORT (STUB) -->
                <div class="space-y-4 text-center py-8">
                    <h2 class="text-xl text-white mb-2">Step 6: Import Data</h2>
                    <p class="text-sm text-[#5f7022]">You can bulk import leads via CSV later.</p>
                </div>

            @elseif ($currentStep == 7)
                <!-- STEP 7: PROJECT (STUB) -->
                <div class="space-y-4 text-center py-8">
                    <h2 class="text-xl text-white mb-2">Step 7: Create Project</h2>
                    <p class="text-sm text-[#5f7022]">Configure your first project or property later.</p>
                </div>

            @elseif ($currentStep == 8)
                <!-- STEP 8: COMPLETE -->
                <div class="space-y-4 text-center py-12">
                    <div class="text-6xl mb-4 text-[#a9c43d]">✓</div>
                    <h2 class="text-2xl text-white mb-2">INITIALIZATION COMPLETE</h2>
                    <p class="text-sm text-[#5f7022]">Tenant container successfully provisioned.</p>
                </div>
            @endif

            <!-- NAVIGATION BUTTONS -->
            <div class="mt-8 pt-4 border-t border-[#3d4a13] flex justify-between">
                @if ($currentStep > 1 && $currentStep < 8)
                    <button type="button" wire:click="previousStep" class="px-4 py-2 bg-transparent border border-[#5f7022] text-[#5f7022] hover:bg-[#111] hover:text-white transition-colors text-sm uppercase">
                        << Go Back
                    </button>
                @else
                    <div></div>
                @endif
                
                @if ($currentStep < 8)
                    <button type="submit" class="px-4 py-2 bg-[#a9c43d] text-black font-bold hover:bg-white transition-colors text-sm uppercase">
                        {{ $currentStep == 2 ? 'Create Account & Continue' : 'Continue >>' }}
                    </button>
                @else
                    <button type="button" wire:click="completeSetup" class="px-6 py-3 bg-[#a9c43d] text-black font-bold hover:bg-white transition-colors text-sm uppercase w-full">
                        ENTER_SYSTEM
                    </button>
                @endif
            </div>

        </form>
    </div>
</div>
