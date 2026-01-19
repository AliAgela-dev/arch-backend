<?php

namespace Database\Factories;

use App\Models\TempUpload;
use Illuminate\Database\Eloquent\Factories\Factory;

class TempUploadFactory extends Factory
{
    protected $model = TempUpload::class;

    public function definition(): array
    {
        return [
            'original_name' => $this->faker->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1000, 5000000),
            'expires_at' => now()->addHours(24),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }
}
