<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\School;
use App\Models\User;

class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Create / Get School
        $school = School::firstOrCreate(
            ['name_en' => 'Al-Noor International School'],
            ['name_ar' => 'مدرسة النور الدولية']
        );

        // 2) Admin user
        User::updateOrCreate(
            ['role' => 'admin', 'username' => 'admin'],
            [
                'school_id' => null,
                'email' => 'admin@demo.local',
                'full_name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        // 3) School user
        User::updateOrCreate(
            ['role' => 'school', 'school_id' => $school->id, 'username' => 'school_alnoor'],
            [
                'email' => 'school_alnoor@demo.local',
                'full_name' => 'Al-Noor School Account',
                'password' => Hash::make('password'),
            ]
        );

        // 4) Student 1
        User::updateOrCreate(
            ['role' => 'student', 'school_id' => $school->id, 'username' => 'ahmed_ali'],
            [
                'email' => 'ahmed.ali@student.alnoor.edu',
                'full_name' => 'Ahmed Ali Mohammed',
                'password' => Hash::make('password'),

                // Optional student fields (لو موجودة عندك)
                'year' => '2025-2026',
                'grade' => '6',
                'gender' => 'male',
                'send' => false,
                'parent_email' => 'parent.ahmed@example.com',
                'nationality' => 'UAE',
            ]
        );
    }
}
