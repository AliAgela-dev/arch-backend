<?php

namespace Database\Factories;

use App\Models\Faculty;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'faculty_id' => Faculty::factory(),
            'code' => strtoupper($this->faker->unique()->lexify('????')),
            'name_ar' => $this->faker->words(3, true),
            'name_en' => $this->faker->words(3, true),
            'status' => 'active',
        ];
    }
}
