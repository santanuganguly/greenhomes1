<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the first tenant (Green Hill Group)
        $orgId = DB::table('organizations')->insertGetId([
            'name' => 'Green Hill Group',
            'legal_name' => 'Green Hill Group',
            'industry' => 'Real Estate',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Migrate existing settings to organization_settings
        $oldSetting = DB::table('settings')->first();
        if ($oldSetting) {
            DB::table('organization_settings')->insert([
                'organization_id' => $orgId,
                'meta_access_token' => $oldSetting->meta_access_token ?? null,
                'phone_number_id' => $oldSetting->phone_number_id ?? null,
                'waba_id' => $oldSetting->waba_id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tables = [
            'users',
            'leads',
            'conversations',
            'messages',
            'projects',
            'project_configs',
            'flows',
            'bot_messages',
            'flow_nodes',
            'flow_edges',
            'bulk_campaigns',
            'bulk_messages',
            'lead_stage_histories',
        ];

        // 2. Add nullable organization_id to all tables
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'organization_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
                });
            }
        }

        // 3. Backfill data
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)->update(['organization_id' => $orgId]);
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'leads',
            'conversations',
            'messages',
            'projects',
            'project_configs',
            'flows',
            'bot_messages',
            'flow_nodes',
            'flow_edges',
            'bulk_campaigns',
            'bulk_messages',
            'lead_stage_histories',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'organization_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['organization_id']);
                    $table->dropColumn('organization_id');
                });
            }
        }

        DB::table('organization_settings')->truncate();
        DB::table('organizations')->truncate();
    }
};
