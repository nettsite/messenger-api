<?php

namespace NettSite\Messenger\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NettSite\Messenger\Tests\Models\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ];
    }
}
