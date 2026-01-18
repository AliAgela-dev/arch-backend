<?php

namespace Database\Factories;

use App\Models\Cabinet;
use App\Models\Drawer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Drawer>
 */
class DrawerFactory extends Factory
{
    protected $model = Drawer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cabinet_id' => Cabinet::factory(),
            'number' => $this->faker->numberBetween(1, 10),
            'label' => $this->faker->optional()->words(2, true),
            'capacity' => $this->faker->numberBetween(50, 200),
            'status' => 'active',
        ];
    }
}
