<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faculty;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faculties = [
            [
                'name_ar' => 'كلية الهندسة',
                'name_en' => 'Faculty of Engineering',
                'code' => 'ENG',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_ar' => 'كلية العلوم',
                'name_en' => 'Faculty of Science',
                'code' => 'SCI',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_ar' => 'كلية الطب',
                'name_en' => 'Faculty of Medicine',
                'code' => 'MED',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_ar' => 'كلية الآداب',
                'name_en' => 'Faculty of Arts',
                'code' => 'ART',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_ar' => 'كلية إدارة الأعمال',
                'name_en' => 'Faculty of Business Administration',
                'code' => 'BUS',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($faculties as $faculty) {
            Faculty::create($faculty);
        }
    }
}
