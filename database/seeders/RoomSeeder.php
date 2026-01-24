<?php

namespace Database\Seeders;

use App\Models\Cabinet;
use App\Models\Drawer;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create multiple rooms with cabinets and drawers
        // Each cabinet must contain exactly 4 drawers
        Room::factory()
            ->has(
                Cabinet::factory()
                    ->has(Drawer::factory()->count(4))
                    ->count(3)
            )
            ->count(2)
            ->create();

        // Create additional rooms with more cabinets
        Room::factory()
            ->has(
                Cabinet::factory()
                    ->has(Drawer::factory()->count(4))
                    ->count(5)
            )
            ->create();
    }
}
