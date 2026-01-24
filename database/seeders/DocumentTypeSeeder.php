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
                'name' => 'High School Transcript',
                'description' => 'Official high school transcript',
                'is_required' => true,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Identification Card',
                'description' => 'National identification card or passport',
                'is_required' => true,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Birth Certificate',
                'description' => 'Official birth certificate',
                'is_required' => true,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Medical Certificate',
                'description' => 'Medical health certificate',
                'is_required' => false,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Transfer Certificate',
                'description' => 'Transfer certificate from previous institution',
                'is_required' => false,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Recommendation Letter',
                'description' => 'Letter of recommendation',
                'is_required' => false,
                'requirement_conditions' => null,
                'status' => 'active',
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::firstOrCreate(
                ['name' => $documentType['name']],
                $documentType
            );
        }
    }
}
