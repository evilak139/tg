<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_snapshot', function (Blueprint $table) {
            $table->id();
            $table->string('period');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('rank');
            $table->integer('invite_count_this_period');
            $table->integer('reward_points');
            $table->timestamps();

            $table->unique(['period', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_snapshot');
    }
};
