<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $text = fake()->paragraph();

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(5),
            'body' => '<p>'.e($text).'</p>',
            'body_text' => $text,
            'occurred_at' => fake()->dateTimeBetween('-60 days'),
        ];
    }
}
