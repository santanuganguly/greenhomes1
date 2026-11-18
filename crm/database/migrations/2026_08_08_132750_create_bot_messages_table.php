<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_messages', function (Blueprint $table) {
            $table->id();
            $table->string('internal_name')->unique();   // e.g. WELCOME_MAIN
            $table->string('display_name');               // Human-readable label
            $table->string('flow_group')->nullable();     // e.g. GREEN_HILL_MAIN
            $table->string('business_vertical')->nullable(); // Green Hill Group / Green Homes
            $table->string('header_text')->nullable();
            $table->text('body_text')->nullable();
            $table->string('footer_text')->nullable();
            // buttons: JSON array of {label, payload, action_type (reply|url|call), action_value}
            $table->json('buttons')->nullable();
            // list_sections: for WhatsApp list messages
            $table->json('list_sections')->nullable();
            $table->string('list_button_label')->nullable(); // "Select Project"
            // message_type: interactive | template | text | list
            $table->string('message_type')->default('interactive');
            $table->string('meta_template_name')->nullable();
            $table->string('meta_template_language')->default('en');
            // Status: not_connected | pending | approved | rejected | paused
            $table->string('meta_template_status')->default('not_connected');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_messages');
    }
};
