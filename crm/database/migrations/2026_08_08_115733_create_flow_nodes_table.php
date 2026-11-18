<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['template', 'text', 'action'])->default('template');
            
            // For templates:
            $table->string('template_name')->nullable();
            
            // For raw text:
            $table->text('text_content')->nullable();

            // Mapping customer reply to Lead field (e.g., 'budget', 'timeline')
            // This means when we ARRIVE at this node, whatever they answered to get here, 
            // or whatever they answer NEXT, we need to know what field they are answering.
            // Actually, mapping should happen on the EDGE or the NODE. 
            // Better on the NODE: "This node asks for budget".
            $table->string('expected_lead_field')->nullable();
            
            // Should this node trigger a human handover?
            $table->boolean('triggers_human_handover')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_nodes');
    }
};
