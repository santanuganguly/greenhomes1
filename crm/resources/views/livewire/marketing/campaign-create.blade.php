<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Marketing Campaign') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <!-- Progress Bar -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div class="text-sm {{ $step >= 1 ? 'text-green-600 font-bold' : 'text-gray-400' }}">1. Details</div>
                        <div class="flex-1 border-t-2 mx-4 {{ $step >= 2 ? 'border-green-600' : 'border-gray-200' }}"></div>
                        <div class="text-sm {{ $step >= 2 ? 'text-green-600 font-bold' : 'text-gray-400' }}">2. Template</div>
                        <div class="flex-1 border-t-2 mx-4 {{ $step >= 3 ? 'border-green-600' : 'border-gray-200' }}"></div>
                        <div class="text-sm {{ $step >= 3 ? 'text-green-600 font-bold' : 'text-gray-400' }}">3. Recipients</div>
                        <div class="flex-1 border-t-2 mx-4 {{ $step >= 4 ? 'border-green-600' : 'border-gray-200' }}"></div>
                        <div class="text-sm {{ $step >= 4 ? 'text-green-600 font-bold' : 'text-gray-400' }}">4. Preview</div>
                    </div>
                </div>

                <!-- Errors -->
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Oops!</strong>
                        <ul class="mt-2 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Step 1: Details -->
                @if ($step === 1)
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Campaign Details</h3>
                        <div>
                            <x-input-label for="name" :value="__('Campaign Name *')" />
                            <x-text-input id="name" wire:model.defer="name" class="block mt-1 w-full" type="text" required />
                        </div>
                        <div>
                            <x-input-label for="project" :value="__('Project (Optional)')" />
                            <x-text-input id="project" wire:model.defer="project" class="block mt-1 w-full" type="text" />
                        </div>
                        <div>
                            <x-input-label for="business_vertical" :value="__('Business Vertical (Optional)')" />
                            <x-text-input id="business_vertical" wire:model.defer="business_vertical" class="block mt-1 w-full" type="text" />
                        </div>
                        <div>
                            <x-input-label for="description" :value="__('Internal Description')" />
                            <textarea id="description" wire:model.defer="description" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full" rows="3"></textarea>
                        </div>
                    </div>
                @endif

                <!-- Step 2: Template -->
                @if ($step === 2)
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Select WhatsApp Template</h3>
                        
                        @if(empty($templates))
                            <p class="text-red-500 text-sm">No templates found. Check your META_WABA_ID configuration.</p>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($templates as $template)
                                    @if($template['status'] === 'APPROVED')
                                        <div wire:click="selectTemplate('{{ $template['name'] }}')" 
                                             class="cursor-pointer border p-4 rounded-lg {{ $selectedTemplateName === $template['name'] ? 'border-green-500 bg-green-50 dark:bg-green-900' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                            <h4 class="font-bold text-gray-900 dark:text-gray-100">{{ $template['name'] }} ({{ $template['language'] }})</h4>
                                            <p class="text-xs text-gray-500 mt-1">{{ $template['category'] }}</p>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        @if(!empty($variableMapping))
                            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Template Variables Detected</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Map the template variables to your CSV columns or Lead fields (e.g., 'customer_name', 'phone_number').</p>
                                @foreach($variableMapping as $index => $mappedTo)
                                    <div class="mb-2 flex items-center">
                                        <span class="mr-3 font-mono bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-sm">&#123;&#123; {{ $index }} &#125;&#125;</span>
                                        <input wire:model.defer="variableMapping.{{ $index }}" placeholder="e.g. customer_name" class="block w-full sm:w-1/2 glass-input border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" type="text" />
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Step 3: Recipients -->
                @if ($step === 3)
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Select Recipients</h3>
                        
                        <div class="flex space-x-4 mb-4">
                            <label class="flex items-center">
                                <input type="radio" wire:model.live="recipientSource" value="leads" class="mr-2"> Existing Leads
                            </label>
                            <label class="flex items-center">
                                <input type="radio" wire:model.live="recipientSource" value="csv" class="mr-2"> Import CSV
                            </label>
                        </div>

                        @if($recipientSource === 'leads')
                            <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded-lg text-sm text-blue-800 dark:text-blue-200 mb-4">
                                This will target all existing leads. If a project was selected in Step 1, it will filter by that project. Opted-out leads are automatically excluded.
                            </div>
                            <button wire:click="processRecipients" class="px-4 py-2 bg-gray-600 text-white rounded">Calculate Total Recipients</button>
                        @endif

                        @if($recipientSource === 'csv')
                            <div class="mb-4">
                                <x-input-label for="csvFile" :value="__('Upload CSV File')" />
                                <p class="text-xs text-gray-500 mb-2">Must contain a 'phone' column.</p>
                                <input type="file" wire:model="csvFile" id="csvFile" accept=".csv" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                <div wire:loading wire:target="csvFile" class="text-sm text-blue-500 mt-2">Uploading and parsing...</div>
                            </div>
                        @endif

                        @if($totalValidRecipients > 0 || $invalidCount > 0 || $duplicateCount > 0)
                            <div class="mt-4 border rounded p-4 dark:border-gray-700 bg-white dark:bg-gray-800">
                                <h4 class="font-medium mb-2">Recipient Analysis</h4>
                                <ul class="space-y-1 text-sm">
                                    <li class="text-green-600"><strong>Valid & Ready:</strong> {{ $totalValidRecipients }}</li>
                                    <li class="text-red-500"><strong>Invalid Phone Length:</strong> {{ $invalidCount }}</li>
                                    <li class="text-yellow-600"><strong>Duplicates Skipped:</strong> {{ $duplicateCount }}</li>
                                    <li class="text-gray-500"><strong>Opted-Out Excluded:</strong> {{ $optOutCount }}</li>
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Step 4: Preview -->
                @if ($step === 4)
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Review & Test</h3>
                        
                        <div class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-700">
                            <h4 class="font-bold text-gray-800 dark:text-gray-200">Campaign Summary</h4>
                            <ul class="text-sm mt-2 text-gray-600 dark:text-gray-300">
                                <li><strong>Name:</strong> {{ $name }}</li>
                                <li><strong>Template:</strong> {{ $selectedTemplateName }}</li>
                                <li><strong>Total Recipients:</strong> {{ $totalValidRecipients }}</li>
                            </ul>
                        </div>

                        <div class="mt-6 border-t pt-4">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Send Test Message</h4>
                            <div class="flex space-x-2">
                                <x-text-input wire:model.defer="testPhoneNumber" placeholder="e.g. 919876543210" class="block w-full sm:w-1/2" />
                                <button wire:click="sendTestMessage" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500">
                                    Send Test
                                </button>
                            </div>
                            <div wire:loading wire:target="sendTestMessage" class="text-sm text-blue-500 mt-2">Sending test...</div>
                            @if (session()->has('test_success'))
                                <div class="mt-2 text-sm text-green-600">{{ session('test_success') }}</div>
                            @endif
                            @if (session()->has('test_error'))
                                <div class="mt-2 text-sm text-red-600">{{ session('test_error') }}</div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Footer Buttons -->
                <div class="mt-8 flex justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        @if ($step > 1)
                            <button wire:click="previousStep" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Back</button>
                        @endif
                    </div>
                    <div>
                        @if ($step < 4)
                            <button wire:click="nextStep" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-500">Next Step</button>
                        @else
                            <button wire:click="saveCampaign" wire:loading.attr="disabled" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-500 font-bold">
                                Save as Draft
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
