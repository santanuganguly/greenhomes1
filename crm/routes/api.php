<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);

// Project Control WhatsApp Bridge
Route::get('/whatsapp/templates', [\App\Http\Controllers\LeadWhatsAppApiController::class, 'templates']);
Route::post('/send-lead-whatsapp', [\App\Http\Controllers\LeadWhatsAppApiController::class, 'send']);
Route::post('/send-task-alert', [\App\Http\Controllers\LeadWhatsAppApiController::class, 'sendTaskAlert']);
Route::post('/send-task-digest', [\App\Http\Controllers\LeadWhatsAppApiController::class, 'sendTaskDigest']);
Route::post('/send-po-approval-alert', [\App\Http\Controllers\LeadWhatsAppApiController::class, 'sendPoApprovalAlert']);



