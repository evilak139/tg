<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('message_templates')->restrictOnDelete();
            $table->json('target_filter');
            $table->dateTime('scheduled_time');
            $table->string('status')->default('待发送');
            $table->integer('total_target_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->string('created_by')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_tasks');
    }
};
