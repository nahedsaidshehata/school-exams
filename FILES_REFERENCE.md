# Sprint 1 - Complete Files Reference

## Quick File Lookup

### Migrations (7 files)
1. `database/migrations/2024_01_01_000000_create_schools_table.php` - Schools table
2. `database/migrations/2024_01_01_000001_modify_users_table.php` - Users with UUID, roles, tenant
3. `database/migrations/2024_01_01_000002_create_materials_table.php` - Materials table
4. `database/migrations/2024_01_01_000003_create_sections_table.php` - Sections table
5. `database/migrations/2024_01_01_000004_create_lessons_table.php` - Lessons table
6. `database/migrations/2024_01_01_000005_create_questions_table.php` - Questions table
7. `database/migrations/2024_01_01_000006_create_question_options_table.php` - Question options table

### Models (7 files)
1. `app/Models/User.php` - User model with roles, UUID, relationships
2. `app/Models/School.php` - School model
3. `app/Models/Material.php` - Material model
4. `app/Models/Section.php` - Section model
5. `app/Models/Lesson.php` - Lesson model
6. `app/Models/Question.php` - Question model
7. `app/Models/QuestionOption.php` - Question option model

### Middleware (2 files)
1. `app/Http/Middleware/RoleMiddleware.php` - Role-based access control
2. `app/Http/Middleware/TenantMiddleware.php` - Multi-tenant isolation

### Controllers (10 files)
1. `app/Http/Controllers/AuthController.php` - Login, logout, me
2. `app/Http/Controllers/Admin/DashboardController.php` - Admin dashboard
3. `app/Http/Controllers/Admin/SchoolController.php` - Schools CRUD
4. `app/Http/Controllers/Admin/StudentController.php` - Students CRUD
5. `app/Http/Controllers/Admin/MaterialController.php` - Materials CRUD
6. `app/Http/Controllers/Admin/SectionController.php` - Sections CRUD
7. `app/Http/Controllers/Admin/LessonController.php` - Lessons CRUD
8. `app/Http/Controllers/Admin/QuestionController.php` - Questions CRUD
9. `app/Http/Controllers/School/DashboardController.php` - School dashboard
10. `app/Http/Controllers/Student/DashboardController.php` - Student dashboard

### Views (20 files)
1. `resources/views/layouts/app.blade.php` - Main layout
2. `resources/views/auth/login.blade.php` - Login page
3. `resources/views/admin/dashboard.blade.php` - Admin dashboard
4. `resources/views/admin/schools/index.blade.php` - Schools list
5. `resources/views/admin/schools/create.blade.php` - Create school
6. `resources/views/admin/students/index.blade.php` - Students list
7. `resources/views/admin/students/create.blade.php` - Create student
8. `resources/views/admin/materials/index.blade.php` - Materials list
9. `resources/views/admin/materials/create.blade.php` - Create material
10. `resources/views/admin/materials/edit.blade.php` - Edit material
11. `resources/views/admin/sections/index.blade.php` - Sections list
12. `resources/views/admin/sections/create.blade.php` - Create section
13. `resources/views/admin/sections/edit.blade.php` - Edit section
14. `resources/views/admin/lessons/index.blade.php` - Lessons list
15. `resources/views/admin/lessons/create.blade.php` - Create lesson
16. `resources/views/admin/lessons/edit.blade.php` - Edit lesson
17. `resources/views/admin/questions/index.blade.php` - Questions list
18. `resources/views/admin/questions/create.blade.php` - Create question
19. `resources/views/school/dashboard.blade.php` - School dashboard
20. `resources/views/student/dashboard.blade.php` - Student dashboard

### Configuration (2 files)
1. `routes/web.php` - All routes
2. `bootstrap/app.php` - Middleware registration

### Seeders (1 file)
1. `database/seeders/DatabaseSeeder.php` - Sample data

### Documentation (3 files)
1. `SPRINT1_SETUP.md` - Setup instructions
2. `SPRINT1_DELIVERABLE.md` - Complete deliverable summary
3. `FILES_REFERENCE.md` - This file

---

**Total: 52 files created/modified for Sprint 1**
