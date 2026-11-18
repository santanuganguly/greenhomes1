<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('marketing_opt_out')->default(false)->after('status');
            $table->timestamp('opted_out_at')->nullable()->after('marketing_opt_out');
            $table->string('opt_out_reason')->nullable()->after('opted_out_at');
            $table->string('source')->nullable()->after('opt_out_reason');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['marketing_opt_out', 'opted_out_at', 'opt_out_reason', 'source']);
        });
    }
};
