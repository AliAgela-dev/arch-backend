<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            [
                'name' => 'Passport',
                'name_ar' => 'جواز سفر',
                'description' => 'Travel document / وثيقة سفر',
                'is_required' => true,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'National ID',
                'name_ar' => 'بطاقة شخصية',
                'description' => 'National identification card / بطاقة تعريف وطنية',
                'is_required' => true,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'High School Certificate',
                'name_ar' => 'شهادة ثانوية',
                'description' => 'Libyan high school certificate / شهادة إتمام الثانوية العامة',
                'is_required' => true,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'International High School Certificate',
                'name_ar' => 'شهادة ثانوية دولية',
                'description' => 'International high school certificate (IB/IGCSE/etc.)',
                'is_required' => false,
                'requirement_conditions' => ['applies_to' => 'international_students'],
                'status' => 'active',
            ],
            [
                'name' => 'Admission Letter',
                'name_ar' => 'إفادة قبول',
                'description' => 'University admission letter / إفادة قبول بالجامعة',
                'is_required' => false,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Enrollment Letter',
                'name_ar' => 'إفادة قيد',
                'description' => 'University enrollment letter / إفادة قيد بالجامعة',
                'is_required' => false,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Birth Certificate',
                'name_ar' => 'شهادة ميلاد',
                'description' => 'Official birth certificate / شهادة ميلاد رسمية',
                'is_required' => true,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Personal Photo',
                'name_ar' => 'صورة شخصية',
                'description' => 'Student personal photo / صورة شخصية للطالب',
                'is_required' => true,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Equivalency Letter',
                'name_ar' => 'إفادة معادلة',
                'description' => 'Qualification equivalency letter / إفادة معادلة شهادة',
                'is_required' => false,
                'requirement_conditions' => ['applies_to' => 'transfer_students'],
                'status' => 'active',
            ],
            [
                'name' => 'Medical Certificate',
                'name_ar' => 'شهادة طبية',
                'description' => 'Medical health certificate / شهادة صحية',
                'is_required' => false,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::updateOrCreate(
                ['name' => $documentType['name']],
                $documentType
            );
        }
    }
}
