<?php

namespace Database\Factories;

use App\Enums\LocationStatus;
use App\Enums\StudentStatus;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'student_number' => $this->faker->unique()->numerify('STU-######'),
            'name' => $this->faker->name(),
            'nationality' => $this->faker->country(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'faculty_id' => Faculty::factory(),
            'program_id' => Program::factory(),
            'drawer_id' => null,
            'enrollment_year' => $this->faker->numberBetween(2018, 2024),
            'graduation_year' => null,
            'student_status' => StudentStatus::ACTIVE,
            'location_status' => LocationStatus::IN_LOCATION,
        ];
    }

    public function graduated(): static
    {
        return $this->state(fn (array $attributes) => [
            'student_status' => StudentStatus::GRADUATED,
            'graduation_year' => $this->faker->numberBetween(2020, 2025),
        ]);
    }

    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_status' => LocationStatus::BORROWED,
        ]);
    }
}
