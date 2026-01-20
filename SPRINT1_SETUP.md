# Multi-School Exams Platform - Sprint 1 Setup Guide

## Overview
This is Sprint 1 implementation of a multi-tenant school exams platform with session-based authentication, role-based access control, and content management.

## Features Implemented (Sprint 1 ONLY)
1. **Authentication** - Session-based with HTTP-only cookies + CSRF
2. **User Provisioning** - Admin creates schools and students
3. **Content Bank** - Materials, Sections, Lessons (Admin CRUD, School/Student read-only)
4. **Question Bank** - MCQ, True/False, Essay questions (Admin CRUD only)

## Tech Stack
- Laravel 11
- MySQL/MariaDB
- Blade Templates
- Session-based Authentication (NO JWT)

## Database Schema
- **schools**: UUID PK, name_en, name_ar
- **users**: UUID PK, school_id (nullable), role (admin/school/student), username, email, password
- **materials**: UUID PK, name_en, name_ar
- **sections**: UUID PK, material_id FK, title_en, title_ar
- **lessons**: UUID PK, section_id FK, title_en, title_ar
- **questions**: UUID PK, lesson_id FK, type (MCQ/TF/ESSAY), difficulty, prompt_en, prompt_ar
- **question_options**: UUID PK, question_id FK, content_en, content_ar, is_correct, order_index
- **sessions**: Laravel session table

## Installation Steps

### 1. Environment Setup
```bash
# Copy .env.example to .env
cp .env.example .env

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_exams
DB_USERNAME=root
DB_PASSWORD=

# Set session driver to database
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Generate Application Key
```bash
php artisan key:generate
```

### 4. Run Migrations
```bash
# Drop all tables and re-migrate (fresh install)
php artisan migrate:fresh

# Or just migrate
php artisan migrate
```

### 5. Seed Database
```bash
php artisan db:seed
```

This creates:
- **Admin**: username=`admin`, password=`password`
- **School**: Al-Noor International School
- **School User**: username=`school_alnoor`, password=`password`
- **Student 1**: username=`ahmed_ali`, password=`password`
- **Student 2**: username=`fatima_hassan`, password=`password`

### 6. Start Development Server
```bash
php artisan serve
```

Visit: http://localhost:8000

## User Roles & Access

### Admin (school_id = NULL)
- Full CRUD on schools, students, materials, sections, lessons, questions
- Routes: `/admin/*`
- Login: admin / password

### School (school_id required)
- Read-only access to content within their school
- View students in their school
- Routes: `/school/*`
- Login: school_alnoor / password

### Student (school_id required)
- Read-only access to content within their school
- View own profile
- Routes: `/student/*`
- Login: ahmed_ali / password OR fatima_hassan / password

## API Endpoints

### Authentication
- `POST /login` - Login with username OR email + password
- `POST /logout` - Logout (clears session)
- `GET /me` - Get current user info (role, school_id)

### Admin Routes (require role:admin)
- `/admin/dashboard` - Admin dashboard
- `/admin/schools` - Schools management
- `/admin/students` - Students management
- `/admin/materials` - Materials CRUD
- `/admin/sections` - Sections CRUD
- `/admin/lessons` - Lessons CRUD
- `/admin/questions` - Questions CRUD

### School Routes (require role:school + tenant middleware)
- `/school/dashboard` - School dashboard (read-only content)

### Student Routes (require role:student + tenant middleware)
- `/student/dashboard` - Student dashboard (read-only content)

## Security Features

### Multi-Tenancy
- Single database with school_id isolation
- Admin has school_id = NULL (system-level)
- School and Student users have school_id (tenant-bound)
- Tenant context derived from authenticated user ONLY
- No school_id accepted from request parameters

### Authentication
- Session-based with HTTP-only cookies
- CSRF protection enabled (Laravel default)
- No JWT, no localStorage/sessionStorage tokens
- Secure session configuration

### Data Isolation
- Per-school uniqueness: UNIQUE(school_id, username), UNIQUE(school_id, email)
- School users can only access their school's data
- Student users can only access their school's data
- Middleware enforces tenant boundaries

### Question Security
- Students NEVER receive correct answers in responses
- Question options with is_correct flag stored server-side only
- Admin-only access to question creation/editing

## Question Types (Sprint 1)

### MCQ (Multiple Choice)
- 2-6 options
- Exactly ONE correct answer
- Both English and Arabic content

### True/False
- Exactly 2 options (True/False)
- Exactly ONE correct answer
- Both English and Arabic content

### Essay
- No options
- Open-ended text response
- Both English and Arabic prompts

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── SchoolController.php
│   │   │   ├── StudentController.php
│   │   │   ├── MaterialController.php
│   │   │   ├── SectionController.php
│   │   │   ├── LessonController.php
│   │   │   └── QuestionController.php
│   │   ├── School/
│   │   │   └── DashboardController.php
│   │   └── Student/
│   │       └── DashboardController.php
│   └── Middleware/
│       ├── RoleMiddleware.php
│       └── TenantMiddleware.php
├── Models/
│   ├── User.php
│   ├── School.php
│   ├── Material.php
│   ├── Section.php
│   ├── Lesson.php
│   ├── Question.php
│   └── QuestionOption.php
database/
├── migrations/
│   ├── 2024_01_01_000000_create_schools_table.php
│   ├── 2024_01_01_000001_modify_users_table.php
│   ├── 2024_01_01_000002_create_materials_table.php
│   ├── 2024_01_01_000003_create_sections_table.php
│   ├── 2024_01_01_000004_create_lessons_table.php
│   ├── 2024_01_01_000005_create_questions_table.php
│   └── 2024_01_01_000006_create_question_options_table.php
└── seeders/
    └── DatabaseSeeder.php
resources/
└── views/
    ├── layouts/
    │   └── app.blade.php
    ├── auth/
    │   └── login.blade.php
    ├── admin/
    │   ├── dashboard.blade.php
    │   ├── schools/
    │   ├── students/
    │   ├── materials/
    │   ├── sections/
    │   ├── lessons/
    │   └── questions/
    ├── school/
    │   └── dashboard.blade.php
    └── student/
        └── dashboard.blade.php
```

## Testing the Application

### 1. Login as Admin
- URL: http://localhost:8000/login
- Username: `admin`
- Password: `password`
- You'll be redirected to `/admin/dashboard`

### 2. Create Content (as Admin)
1. Create a Material (e.g., "Mathematics")
2. Create a Section under that Material (e.g., "Algebra")
3. Create a Lesson under that Section (e.g., "Linear Equations")
4. Create Questions for that Lesson

### 3. Login as School User
- Logout from admin
- Login with: `school_alnoor` / `password`
- View read-only content and students

### 4. Login as Student
- Logout from school
- Login with: `ahmed_ali` / `password`
- View read-only content

## Important Notes

### Sprint 1 Limitations (By Design)
- NO exam/assignment creation
- NO exam attempts or submissions
- NO anti-cheat features
- NO grading or scoring
- NO reporting or analytics
- These are for Sprint 2+

### Security Reminders
- Never expose correct answers to students
- Always derive school_id from authenticated user
- Never accept school_id from request parameters
- Enforce tenant isolation via middleware

### Database Constraints
- Username must be unique per school (not globally)
- Email must be unique per school (not globally)
- Each school has exactly ONE school user account
- Admin users have school_id = NULL

## Troubleshooting

### Migration Errors
```bash
# Reset database completely
php artisan migrate:fresh --seed
```

### Session Issues
```bash
# Clear cache and sessions
php artisan cache:clear
php artisan config:clear
php artisan session:table  # If sessions table missing
php artisan migrate
```

### Permission Errors
```bash
# Fix storage permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Next Steps (Sprint 2+)
- Exam/Assignment creation
- Exam attempts and submissions
- Anti-cheat mechanisms
- Grading and scoring
- Reporting and analytics

---

**Sprint 1 Complete** ✓
