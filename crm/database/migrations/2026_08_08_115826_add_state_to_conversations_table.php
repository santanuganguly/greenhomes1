<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('current_node_id')->nullable()->constrained('flow_nodes')->nullOnDelete();
            $table->json('context')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['current_node_id']);
            $table->dropColumn(['current_node_id', 'context']);
        });
    }
};
