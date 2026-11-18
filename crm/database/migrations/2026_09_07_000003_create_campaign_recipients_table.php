<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('phone');
            $table->string('name')->nullable();
            $table->json('variables')->nullable();
            
            $table->enum('status', ['pending', 'queued', 'sending', 'sent', 'delivered', 'read', 'failed', 'skipped', 'opted_out'])->default('pending');
            $table->string('meta_message_id')->nullable()->unique();
            
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            
            $table->timestamps();
            
            $table->index('campaign_id');
            $table->index('status');
            $table->index('meta_message_id');
            $table->index('phone');
            $table->unique(['campaign_id', 'phone']); // Prevent duplicate numbers in same campaign
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
