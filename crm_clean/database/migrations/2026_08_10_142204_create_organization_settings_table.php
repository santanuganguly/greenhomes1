<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            
            // Branding
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->default('#a9c43d');
            
            // WhatsApp Credentials
            $table->text('meta_access_token')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('waba_id')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};
