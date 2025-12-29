<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\Faculty;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get faculty IDs
        $engineering = Faculty::where('code', 'ENG')->first();
        $science = Faculty::where('code', 'SCI')->first();
        $medicine = Faculty::where('code', 'MED')->first();
        $arts = Faculty::where('code', 'ART')->first();
        $business = Faculty::where('code', 'BUS')->first();

        $programs = [
            // Engineering Programs
            [
                'faculty_id' => $engineering->id,
                'code' => 'CSE',
                'name_ar' => 'هندسة الحاسوب',
                'name_en' => 'Computer Science Engineering',
                'status' => 'active',
            ],
            [
                'faculty_id' => $engineering->id,
                'code' => 'ELE',
                'name_ar' => 'الهندسة الكهربائية',
                'name_en' => 'Electrical Engineering',
                'status' => 'active',
            ],
            [
                'faculty_id' => $engineering->id,
                'code' => 'MEC',
                'name_ar' => 'الهندسة الميكانيكية',
                'name_en' => 'Mechanical Engineering',
                'status' => 'active',
            ],
            [
                'faculty_id' => $engineering->id,
                'code' => 'CIV',
                'name_ar' => 'الهندسة المدنية',
                'name_en' => 'Civil Engineering',
                'status' => 'active',
            ],

            // Science Programs
            [
                'faculty_id' => $science->id,
                'code' => 'PHY',
                'name_ar' => 'الفيزياء',
                'name_en' => 'Physics',
                'status' => 'active',
            ],
            [
                'faculty_id' => $science->id,
                'code' => 'CHE',
                'name_ar' => 'الكيمياء',
                'name_en' => 'Chemistry',
                'status' => 'active',
            ],
            [
                'faculty_id' => $science->id,
                'code' => 'BIO',
                'name_ar' => 'الأحياء',
                'name_en' => 'Biology',
                'status' => 'active',
            ],
            [
                'faculty_id' => $science->id,
                'code' => 'MAT',
                'name_ar' => 'الرياضيات',
                'name_en' => 'Mathematics',
                'status' => 'active',
            ],

            // Medicine Programs
            [
                'faculty_id' => $medicine->id,
                'code' => 'MED',
                'name_ar' => 'الطب البشري',
                'name_en' => 'Human Medicine',
                'status' => 'active',
            ],
            [
                'faculty_id' => $medicine->id,
                'code' => 'DEN',
                'name_ar' => 'طب الأسنان',
                'name_en' => 'Dentistry',
                'status' => 'active',
            ],
            [
                'faculty_id' => $medicine->id,
                'code' => 'PHA',
                'name_ar' => 'الصيدلة',
                'name_en' => 'Pharmacy',
                'status' => 'active',
            ],

            // Arts Programs
            [
                'faculty_id' => $arts->id,
                'code' => 'ARA',
                'name_ar' => 'اللغة العربية',
                'name_en' => 'Arabic Language',
                'status' => 'active',
            ],
            [
                'faculty_id' => $arts->id,
                'code' => 'ENG',
                'name_ar' => 'اللغة الإنجليزية',
                'name_en' => 'English Language',
                'status' => 'active',
            ],
            [
                'faculty_id' => $arts->id,
                'code' => 'HIS',
                'name_ar' => 'التاريخ',
                'name_en' => 'History',
                'status' => 'active',
            ],

            // Business Programs
            [
                'faculty_id' => $business->id,
                'code' => 'MBA',
                'name_ar' => 'إدارة الأعمال',
                'name_en' => 'Business Administration',
                'status' => 'active',
            ],
            [
                'faculty_id' => $business->id,
                'code' => 'ACC',
                'name_ar' => 'المحاسبة',
                'name_en' => 'Accounting',
                'status' => 'active',
            ],
            [
                'faculty_id' => $business->id,
                'code' => 'FIN',
                'name_ar' => 'التمويل',
                'name_en' => 'Finance',
                'status' => 'active',
            ],
            [
                'faculty_id' => $business->id,
                'code' => 'MKT',
                'name_ar' => 'التسويق',
                'name_en' => 'Marketing',
                'status' => 'active',
            ],
        ];

        foreach ($programs as $program) {
            Program::create($program);
        }
    }
}
