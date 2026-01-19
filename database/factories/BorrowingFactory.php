<?php

namespace Database\Factories;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BorrowingFactory extends Factory
{
    protected $model = Borrowing::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'student_document_id' => StudentDocument::factory(),
            'status' => BorrowingStatus::PENDING,
            'requested_at' => now(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BorrowingStatus::PENDING,
            'requested_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BorrowingStatus::APPROVED,
            'approved_at' => now(),
            'due_date' => now()->addDays(config('borrowing.default_duration_days', 14)),
            'admin_notes' => $this->faker->optional()->sentence(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BorrowingStatus::REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
            'admin_notes' => $this->faker->optional()->sentence(),
        ]);
    }

    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BorrowingStatus::BORROWED,
            'approved_at' => now()->subDays(2),
            'borrowed_at' => now()->subDay(),
            'due_date' => now()->addDays(config('borrowing.default_duration_days', 14)),
            'admin_notes' => $this->faker->optional()->sentence(),
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BorrowingStatus::RETURNED,
            'approved_at' => now()->subDays(15),
            'borrowed_at' => now()->subDays(14),
            'due_date' => now()->subDay(),
            'returned_at' => now(),
            'admin_notes' => $this->faker->optional()->sentence(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BorrowingStatus::BORROWED,
            'approved_at' => now()->subDays(20),
            'borrowed_at' => now()->subDays(18),
            'due_date' => now()->subDays(3), // 3 days overdue
            'admin_notes' => $this->faker->optional()->sentence(),
        ]);
    }
}
