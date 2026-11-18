<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('pipeline_stage')->default('New Lead')->after('status');
            $table->string('lead_source')->nullable()->after('pipeline_stage');
            $table->string('temperature')->default('Cold')->after('lead_source'); // Hot, Warm, Cold
            $table->foreignId('assigned_to')->nullable()->after('temperature')->constrained('users')->nullOnDelete();
            $table->timestamp('last_contact_date')->nullable()->after('assigned_to');
            $table->timestamp('next_follow_up_date')->nullable()->after('last_contact_date');
        });

        // Migrate existing status to pipeline_stage
        DB::table('leads')->where('status', 'new')->update(['pipeline_stage' => 'New Lead']);
        DB::table('leads')->where('status', 'contacted')->update(['pipeline_stage' => 'Contacted']);
        DB::table('leads')->where('status', 'qualified')->update(['pipeline_stage' => 'Qualified']);
        DB::table('leads')->where('status', 'converted')->update(['pipeline_stage' => 'Converted / Customer']);
        DB::table('leads')->where('status', 'lost')->update(['pipeline_stage' => 'Lost']);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn([
                'pipeline_stage',
                'lead_source',
                'temperature',
                'assigned_to',
                'last_contact_date',
                'next_follow_up_date',
            ]);
        });
    }
};
