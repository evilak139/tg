<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_monthly_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('batch_month');
            $table->integer('points_earned_total')->default(0);
            $table->integer('points_consumed_total')->default(0);
            $table->dateTime('expire_at');
            $table->string('status')->default('有效');
            $table->timestamps();

            $table->unique(['user_id', 'batch_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_monthly_batches');
    }
};
