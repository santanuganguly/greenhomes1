<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('phone_number')->unique();
            $table->string('business_vertical')->nullable(); // Green Hill Group, Green Homes
            $table->string('project')->nullable();
            $table->string('owns_land')->nullable(); // Yes, No
            $table->string('home_type')->nullable(); // Villa, Independent House
            $table->string('floors')->nullable(); // G, G+1, G+2
            $table->string('area')->nullable(); // 1500-2500
            $table->string('budget')->nullable(); // 50L-1CR
            $table->string('timeline')->nullable(); // 3-6 months
            $table->integer('score')->default(0);
            $table->enum('status', ['new', 'contacted', 'qualified', 'converted', 'lost'])->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
