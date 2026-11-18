<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Inbox;
use App\Livewire\FlowBuilder;
use App\Livewire\BotMessageEditor;
use App\Livewire\ProjectsManager;
use App\Livewire\SettingsManager;

Route::view('/', 'welcome');

Route::get('/onboarding', App\Livewire\SaaS\OnboardingWizard::class)->name('onboarding');

Route::get('dashboard', App\Livewire\ManagementDashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('inbox', Inbox::class)
    ->middleware(['auth', 'verified'])
    ->name('inbox');

Route::get('leads', App\Livewire\LeadPipeline::class)
    ->middleware(['auth', 'verified'])
    ->name('leads');

Route::get('automation', FlowBuilder::class)
    ->middleware(['auth', 'verified'])
    ->name('automation');

Route::get('automation/messages', BotMessageEditor::class)
    ->middleware(['auth', 'verified'])
    ->name('automation.messages');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bulk', App\Livewire\BulkMessagingManager::class)->name('bulk');
    Route::get('/bulk/{id}', App\Livewire\BulkCampaignDetail::class)->name('bulk.detail');
});

Route::get('projects', ProjectsManager::class)
    ->middleware(['auth', 'verified'])
    ->name('projects');

Route::get('settings', SettingsManager::class)
    ->middleware(['auth', 'verified'])
    ->name('settings');

Route::get('/debug-meta', function () {
    $settings = \App\Models\Setting::where('key', 'like', 'meta_%')->get();
    return response()->json($settings);
});

Route::get('/webhook', [\App\Http\Controllers\WebhookController::class, 'verify']);
Route::post('/webhook', [\App\Http\Controllers\WebhookController::class, 'handle']);



require __DIR__.'/auth.php';
