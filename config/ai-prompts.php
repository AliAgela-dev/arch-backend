<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Prompt
    |--------------------------------------------------------------------------
    |
    | Sent as the systemInstruction to Gemini on every refinement call.
    |
    */

    'system' => 'You are a document analysis system for the Libyan International University (LIMU / الجامعة الليبية الدولية) archive. You extract structured information from OCR-processed Arabic and English university documents. You MUST respond with valid JSON only — no markdown, no explanation, no preamble.',

    /*
    |--------------------------------------------------------------------------
    | Generic Extraction Prompt
    |--------------------------------------------------------------------------
    |
    | Used when document type is unknown (first-pass or unclassified).
    | The {text} placeholder is replaced with the OCR content.
    |
    */

    'generic' => <<<'PROMPT'
Analyze the following OCR-extracted text from a university document and extract structured information.

Return a JSON object with these fields:
- "student_number": The student's university number/ID (string or null)
- "student_name": The student's full name, preferring Arabic if available (string or null)
- "college": The college/faculty name (string or null)
- "program": The academic program/major name (string or null)
- "document_type": One of: "Passport", "National ID", "High School Certificate", "International High School Certificate", "Admission Letter", "Enrollment Letter", "Birth Certificate", "Personal Photo", "Equivalency Letter", "Medical Certificate" (string or null)
- "enrollment_date": The enrollment or admission date if found (string in YYYY-MM-DD format or null)
- "confidence": Your confidence in the extraction accuracy from 0.0 to 1.0 (number)
- "additional_fields": An object with any other relevant information extracted (object, may be empty {})

Rules:
- Set null for any field that cannot be determined from the text
- Skip boilerplate text (headers, university descriptions, form instructions)
- Focus on personal/student-specific information
- For confidence: clear legible text > 0.9, partial/blurry > 0.7, mostly illegible < 0.5
- Respond with valid JSON only — no markdown fences, no explanation

OCR Text:
{text}
PROMPT,

    /*
    |--------------------------------------------------------------------------
    | Document-Type-Specific Extraction Hints
    |--------------------------------------------------------------------------
    |
    | Keyed by DocumentType.name (English) from the document_types table.
    | Each entry adds type-specific instructions that get appended to the
    | generic prompt when the document type is known or detected.
    |
    | These are HINTS, not full replacement prompts. They tell Gemini
    | what additional fields to look for in this specific document type.
    |
    | NOTE: Keys match DocumentType.name in the database. If a new
    | document type is added via the CRUD, add a corresponding hint here.
    |
    */

    'type_hints' => [

        'Passport' => 'This is a passport (جواز سفر). Additionally extract: passport_number, nationality, expiry_date, date_of_birth, place_of_birth.',

        'High School Certificate' => 'This is a high school certificate (شهادة ثانوية). Additionally extract: school_name, school_city, graduation_year, gpa_score, gpa_scale, specialization.',

        'International High School Certificate' => 'This is an international high school certificate (شهادة ثانوية دولية). Additionally extract: school_name, school_city, school_country, curriculum_type (IB/IGCSE/American/other), graduation_year, gpa_score.',

        'Admission Letter' => 'This is an admission letter (إفادة قبول). Additionally extract: admission_date, academic_year, semester, admission_type (regular/transfer/other).',

        'Enrollment Letter' => 'This is an enrollment letter (إفادة قيد). Additionally extract: enrollment_status, academic_year, semester, year_of_study.',

        'National ID' => 'This is a national ID card (بطاقة شخصية). Additionally extract: national_id_number, date_of_birth, place_of_birth.',

        'Birth Certificate' => 'This is a birth certificate (شهادة ميلاد). Additionally extract: date_of_birth, place_of_birth, father_name, mother_name.',

        'Personal Photo' => 'This is a personal photo (صورة شخصية). There is likely no text to extract. Set confidence to 0.1 and document_type to "Personal Photo".',

        'Equivalency Letter' => 'This is an equivalency letter (إفادة معادلة). Additionally extract: original_qualification, issuing_authority, equivalency_date.',

        'Medical Certificate' => 'This is a medical certificate (شهادة طبية). Additionally extract: medical_status, issuing_hospital, issue_date.',

    ],

];
