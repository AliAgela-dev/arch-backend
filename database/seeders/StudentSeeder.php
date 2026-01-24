<?php

namespace Database\Seeders;

use App\Models\Drawer;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing faculties and programs
        $faculties = Faculty::all();
        $programs = Program::all();
        $drawers = Drawer::all();

        if ($faculties->isEmpty() || $programs->isEmpty()) {
            $this->command->warn('No faculties or programs found. Please run FacultySeeder and ProgramSeeder first.');
            return;
        }

        // Create students with random faculty and program assignments
        // Override factory defaults to use existing IDs
        for ($i = 0; $i < 50; $i++) {
            Student::factory()->create([
                'faculty_id' => $faculties->random()->id,
                'program_id' => $programs->random()->id,
            ]);
        }

        // Randomly assign some students to drawers (optional assignment)
        if (!$drawers->isEmpty()) {
            Student::inRandomOrder()->limit(30)->get()->each(function ($student) use ($drawers) {
                $student->update([
                    'drawer_id' => $drawers->random()->id,
                ]);
            });
        }
    }
}
