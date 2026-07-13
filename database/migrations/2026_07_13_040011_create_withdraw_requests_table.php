<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('points_amount');
            $table->decimal('exchange_amount', 12, 2);
            $table->string('status')->default('待处理');
            $table->boolean('risk_flag')->default(false);
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->string('operator')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_requests');
    }
};
