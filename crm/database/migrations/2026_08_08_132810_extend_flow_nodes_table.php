<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_nodes', function (Blueprint $table) {
            $table->foreignId('bot_message_id')->nullable()->constrained('bot_messages')->nullOnDelete()->after('flow_id');
            $table->boolean('is_start')->default(false)->after('triggers_human_handover');
            $table->integer('sort_order')->default(0)->after('is_start');
            // input_type: none | text_input (freetext capture) | button | list
            $table->string('input_type')->default('none')->after('sort_order');
        });

        Schema::table('flows', function (Blueprint $table) {
            $table->string('group_name')->nullable()->after('name'); // e.g. GREEN_HILL_MAIN
            $table->string('business_vertical')->nullable()->after('group_name');
        });
    }

    public function down(): void
    {
        Schema::table('flow_nodes', function (Blueprint $table) {
            $table->dropForeign(['bot_message_id']);
            $table->dropColumn(['bot_message_id', 'is_start', 'sort_order', 'input_type']);
        });
        Schema::table('flows', function (Blueprint $table) {
            $table->dropColumn(['group_name', 'business_vertical']);
        });
    }
};
