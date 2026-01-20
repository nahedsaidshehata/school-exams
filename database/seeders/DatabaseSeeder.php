<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'id' => Str::uuid(),
            'school_id' => null,
            'role' => 'admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'full_name' => 'System Administrator',
        ]);

        echo "✓ Admin created: username=admin, password=password\n";

        // Create School
        $school = School::create([
            'id' => Str::uuid(),
            'name_en' => 'Al-Noor International School',
            'name_ar' => 'مدرسة النور الدولية',
        ]);

        echo "✓ School created: {$school->name_en}\n";

        // Create School User
        $schoolUser = User::create([
            'id' => Str::uuid(),
            'school_id' => $school->id,
            'role' => 'school',
            'username' => 'school_alnoor',
            'email' => 'school@alnoor.edu',
            'password' => Hash::make('password'),
            'full_name' => 'Al-Noor School Admin',
        ]);

        echo "✓ School user created: username=school_alnoor, password=password\n";

        // Student 1
        $student1 = User::create([
            'id' => Str::uuid(),
            'school_id' => $school->id,
            'role' => 'student',
            'username' => 'ahmed_ali',
            'email' => 'ahmed.ali@student.alnoor.edu',
            'password' => Hash::make('password'),
            'full_name' => 'Ahmed Ali Mohammed',
        ]);

        StudentProfile::create([
            'user_id' => $student1->id,
            'year' => '2025-2026',
            'grade' => '6',
            'gender' => 'male',
            'send' => false,
            'parent_email' => 'parent.ahmed@alnoor.edu',
            'nationality' => 'Egypt',
        ]);

        echo "✓ Student 1 created: username=ahmed_ali, password=password\n";

        // Student 2
        $student2 = User::create([
            'id' => Str::uuid(),
            'school_id' => $school->id,
            'role' => 'student',
            'username' => 'fatima_hassan',
            'email' => 'fatima.hassan@student.alnoor.edu',
            'password' => Hash::make('password'),
            'full_name' => 'Fatima Hassan Ibrahim',
        ]);

        StudentProfile::create([
            'user_id' => $student2->id,
            'year' => '2025-2026',
            'grade' => '6',
            'gender' => 'female',
            'send' => true,
            'parent_email' => 'parent.fatima@alnoor.edu',
            'nationality' => 'UAE',
        ]);

        echo "✓ Student 2 created: username=fatima_hassan, password=password\n";

        echo "\n=== SEEDING COMPLETE ===\n";
        echo "Admin: admin / password\n";
        echo "School: school_alnoor / password\n";
        echo "Student 1: ahmed_ali / password\n";
        echo "Student 2: fatima_hassan / password\n";
    }
}
