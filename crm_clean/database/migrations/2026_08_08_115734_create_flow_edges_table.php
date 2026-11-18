<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_node_id')->constrained('flow_nodes')->cascadeOnDelete();
            $table->foreignId('target_node_id')->nullable()->constrained('flow_nodes')->nullOnDelete();
            
            // The exact button payload or text match required to trigger this edge
            $table->string('condition_payload'); 
            
            // Value to save to the Lead field defined in target_node's expected_lead_field
            $table->string('save_value')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_edges');
    }
};
