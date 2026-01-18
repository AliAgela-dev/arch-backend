<?php

namespace Database\Factories;

use App\Models\Cabinet;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cabinet>
 */
class CabinetFactory extends Factory
{
    protected $model = Cabinet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'name' => 'Cabinet ' . $this->faker->unique()->numberBetween(1, 1000),
            'position_x' => $this->faker->numberBetween(0, 500),
            'position_y' => $this->faker->numberBetween(0, 500),
            'status' => 'active',
        ];
    }
}
