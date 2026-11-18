<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">⚙️ System Settings</h1>
            <p class="text-sm text-gray-500 mt-1">Configure expert contact number, Meta API credentials, and other system settings.</p>
        </div>

        @if($successMsg)
        <div class="mb-4 bg-green-100/60 border border-green-200/60 backdrop-blur-sm rounded-lg p-3 text-green-800 text-sm font-medium">
            {{ $successMsg }}
        </div>
        @endif

        <form wire:submit.prevent="save">
            @foreach(['contact', 'meta', 'general'] as $group)
            @php
                $groupSettings = collect($settings)->filter(fn($s) => $s['group'] === $group);
                $groupLabels   = ['contact' => '📞 Contact Settings', 'meta' => '🔗 Meta WhatsApp API', 'general' => '🏢 General'];
            @endphp
            @if($groupSettings->count())
            <div class="glass-panel p-6 mb-6">
                <h2 class="text-base font-bold text-gray-800 mb-4">{{ $groupLabels[$group] ?? $group }}</h2>
                @if($group === 'meta')
                <div class="mb-3 bg-yellow-100/60 border border-yellow-200/60 backdrop-blur-sm rounded-lg p-3 text-sm text-yellow-800">
                    ⚠️ Meta API credentials are loaded from <code>.env</code> file at runtime. The values stored here are for display/reference. Update <code>.env</code> to change live credentials.
                </div>
                @endif
                <div class="space-y-4">
                    @foreach($groupSettings as $key => $setting)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $setting['label'] }}</label>
                        <input
                            type="{{ in_array($key, ['meta_access_token']) ? 'password' : 'text' }}"
                            wire:model="settings.{{ $key }}.value"
                            class="glass-input font-{{ in_array($key, ['meta_access_token', 'meta_phone_number_id', 'meta_waba_id', 'meta_verify_token']) ? 'mono' : 'sans' }}"
                            placeholder="{{ $setting['label'] }}"
                        >
                        @if($key === 'expert_phone')
                            <p class="text-xs text-gray-400 mt-1">Use international format without spaces: +919339361344</p>
                        @elseif($key === 'expert_phone_display')
                            <p class="text-xs text-gray-400 mt-1">This is shown in WhatsApp messages: +91 93393 61344</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach

            <div class="flex">
                <button type="submit" class="glass-button-primary px-8">
                    💾 Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
