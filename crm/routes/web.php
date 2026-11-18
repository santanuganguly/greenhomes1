<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Inbox;
use App\Livewire\LeadsList;
use App\Livewire\FlowBuilder;
use App\Livewire\BotMessageEditor;
use App\Livewire\ProjectsManager;
use App\Livewire\SettingsManager;
use App\Livewire\Marketing\MarketingDashboard;
use App\Livewire\Marketing\CampaignsList;
use App\Livewire\Marketing\CampaignCreate;
use App\Livewire\Marketing\CampaignDetails;

Route::view('/', 'welcome');

Route::get('dashboard', \App\Livewire\Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('inbox', Inbox::class)
    ->middleware(['auth', 'verified'])
    ->name('inbox');

Route::get('leads', LeadsList::class)
    ->middleware(['auth', 'verified'])
    ->name('leads');

Route::get('automation', FlowBuilder::class)
    ->middleware(['auth', 'verified'])
    ->name('automation');

Route::get('automation/messages', BotMessageEditor::class)
    ->middleware(['auth', 'verified'])
    ->name('automation.messages');

Route::get('projects', ProjectsManager::class)
    ->middleware(['auth', 'verified'])
    ->name('projects');

Route::get('settings', SettingsManager::class)
    ->middleware(['auth', 'verified'])
    ->name('settings');

// Marketing Module Routes
Route::prefix('marketing')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', MarketingDashboard::class)->name('marketing.dashboard');
    Route::get('/campaigns', CampaignsList::class)->name('marketing.campaigns');
    Route::get('/campaigns/create', CampaignCreate::class)->name('marketing.campaign.create');
    Route::get('/campaigns/{id}', CampaignDetails::class)->name('marketing.campaign.details');
});

Route::get('/webhook', [\App\Http\Controllers\WebhookController::class, 'verify']);
Route::post('/webhook', [\App\Http\Controllers\WebhookController::class, 'handle']);

Route::get('/run-migrations', function() {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return \Illuminate\Support\Facades\Artisan::output();
});

Route::get('/trigger-queue', function() {
    \Illuminate\Support\Facades\Artisan::call('queue:work', ['--stop-when-empty' => true]);
    return \Illuminate\Support\Facades\Artisan::output();
});

require __DIR__.'/auth.php';
