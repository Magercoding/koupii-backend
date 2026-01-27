<?php

namespace Database\Factories;

use App\Models\Test;
use App\Models\User;
use App\Models\Classes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Test>
 */
class TestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Test::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'class_id' => Classes::factory(),
            'type' => fake()->randomElement(['reading', 'writing', 'listening', 'speaking']),
            'difficulty' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'test_type' => 'single',
            'timer_mode' => 'none',
            'timer_settings' => null,
            'allow_repetition' => false,
            'max_repetition_count' => null,
            'is_public' => false,
            'is_published' => true,
            'settings' => null,
        ];
    }

    /**
     * Indicate that the test is a reading test.
     */
    public function reading(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reading',
        ]);
    }

    /**
     * Indicate that the test is a writing test.
     */
    public function writing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'writing',
        ]);
    }

    /**
     * Indicate that the test is a listening test.
     */
    public function listening(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'listening',
        ]);
    }

    /**
     * Indicate that the test is a speaking test.
     */
    public function speaking(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'speaking',
        ]);
    }

    /**
     * Indicate that the test is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    /**
     * Indicate that the test is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}