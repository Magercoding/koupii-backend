<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Classes;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Assignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => Classes::factory(),
            'test_id' => Test::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'close_date' => null,
            'is_published' => true,
            'source_type' => 'manual',
            'source_id' => null,
            'assignment_settings' => null,
            'auto_created_at' => null,
            'type' => $this->faker->randomElement(['reading_task', 'writing_task', 'listening_task', 'speaking_task']),
        ];
    }

    /**
     * Indicate that the assignment was auto-created from a test.
     */
    public function autoCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'auto_test',
            'source_id' => $attributes['test_id'],
            'auto_created_at' => now(),
        ]);
    }

    /**
     * Indicate that the assignment is for a reading task.
     */
    public function readingTask(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reading_task',
        ]);
    }

    /**
     * Indicate that the assignment is for a writing task.
     */
    public function writingTask(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'writing_task',
        ]);
    }

    /**
     * Indicate that the assignment is for a listening task.
     */
    public function listeningTask(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'listening_task',
        ]);
    }

    /**
     * Indicate that the assignment is for a speaking task.
     */
    public function speakingTask(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'speaking_task',
        ]);
    }

    /**
     * Indicate that the assignment is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}