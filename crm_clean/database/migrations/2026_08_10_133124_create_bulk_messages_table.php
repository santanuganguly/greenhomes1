<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bulk_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_campaign_id')->constrained('bulk_campaigns')->onDelete('cascade');
            $table->string('phone_number');
            $table->string('contact_name')->nullable();
            $table->string('status')->default('pending'); // pending, sent, delivered, read, failed
            $table->string('wamid')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_messages');
    }
};
