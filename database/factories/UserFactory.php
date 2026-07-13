<?php

namespace Database\Factories;

use App\Enums\ActivityTag;
use App\Enums\IdentityLevel;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tg_user_id' => fake()->unique()->numberBetween(100000000, 999999999),
            'tg_username' => fake()->optional()->userName(),
            'nickname' => fake()->name(),
            'points_balance' => 0,
            'register_time' => now(),
            'last_active_time' => now(),
            'checkin_streak' => 0,
            'identity_level' => IdentityLevel::RegisteredMember,
            'activity_tag' => ActivityTag::Active,
            'is_high_value' => false,
            'status' => UserStatus::Normal,
        ];
    }
}
