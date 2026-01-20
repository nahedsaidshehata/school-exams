# SPRINT 2 – PHASE 1 PATCH: CORRECTED SCOPE
## Exam Builder & Assignment System (DESIGN ONLY - NO CODE)

---

## SCOPE CORRECTIONS

### What Was REMOVED from Original Design:
❌ Classes and Groups (NOT approved - future extension only)
❌ Exam Attempts implementation (deferred to later sprint)
❌ Class/Group assignment types
❌ Attempt tracking and grading
❌ Extra endpoints beyond minimum required

### What REMAINS in Sprint 2 Phase 2:
✅ Exam Builder (create exams, attach questions, set points)
✅ Assignments (school-wide OR specific students ONLY)
✅ Overrides (per-student lock mode and deadline extensions)
✅ State Resolver (LOCKED/UPCOMING/AVAILABLE/EXPIRED)
✅ Read-only lists for School and Student roles

---

## 1) DATABASE TABLES FOR SPRINT 2 PHASE 2 ONLY

### Table 1: `exams`
**Purpose:** Store exam metadata

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Exam identifier |
| title_en | VARCHAR(255) | NOT NULL | Exam title (English) |
| title_ar | VARCHAR(255) | NOT NULL | Exam title (Arabic) |
| duration_minutes | INTEGER | NOT NULL | Time limit in minutes |
| starts_at | TIMESTAMP | NOT NULL | Exam start date/time |
| ends_at | TIMESTAMP | NOT NULL | Exam end date/time |
| max_attempts | INTEGER | NOT NULL, DEFAULT 5 | Maximum attempts allowed |
| is_globally_locked | BOOLEAN | NOT NULL, DEFAULT false | Global lock status |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `starts_at`
- INDEX on `ends_at`

**Constraints:**
- CHECK: `ends_at > starts_at`
- CHECK: `duration_minutes > 0`
- CHECK: `max_attempts > 0`

---

### Table 2: `exam_questions`
**Purpose:** Link questions to exams with point values (fixed order)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Record identifier |
| exam_id | UUID | NOT NULL, FK -> exams.id | Exam reference |
| question_id | UUID | NOT NULL, FK -> questions.id | Question reference |
| points | DECIMAL(5,2) | NOT NULL | Points for this question |
| order_index | INTEGER | NOT NULL | Question order (1, 2, 3...) |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `(exam_id, question_id)` - No duplicate questions in same exam
- UNIQUE on `(exam_id, order_index)` - No duplicate order in same exam
- INDEX on `exam_id`
- INDEX on `question_id`

**Constraints:**
- FOREIGN KEY `exam_id` REFERENCES `exams(id)` ON DELETE CASCADE
- FOREIGN KEY `question_id` REFERENCES `questions(id)` ON DELETE RESTRICT
- CHECK: `points > 0`
- CHECK: `order_index > 0`

---

### Table 3: `exam_assignments`
**Purpose:** Assign exams to schools or specific students

**Justification for Unified Table:**
- Single table simplifies queries and maintains referential integrity
- `assignment_type` ENUM clearly distinguishes between SCHOOL and STUDENT assignments
- Tenant isolation enforced via school_id (always present, derived from authenticated user)
- No polymorphic ambiguity: exactly ONE of (school_id for type=SCHOOL, student_id for type=STUDENT) is used
- Future-proof: Can add CLASS/GROUP types later without schema changes

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Assignment identifier |
| exam_id | UUID | NOT NULL, FK -> exams.id | Exam reference |
| assignment_type | ENUM('SCHOOL', 'STUDENT') | NOT NULL | Assignment type |
| school_id | UUID | NULLABLE, FK -> schools.id | For SCHOOL type assignments |
| student_id | UUID | NULLABLE, FK -> users.id | For STUDENT type assignments |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `exam_id`
- INDEX on `school_id`
- INDEX on `student_id`
- INDEX on `(exam_id, assignment_type, school_id)` - For school assignments
- INDEX on `(exam_id, assignment_type, student_id)` - For student assignments

**Constraints:**
- FOREIGN KEY `exam_id` REFERENCES `exams(id)` ON DELETE CASCADE
- FOREIGN KEY `school_id` REFERENCES `schools(id)` ON DELETE CASCADE
- FOREIGN KEY `student_id` REFERENCES `users(id)` ON DELETE CASCADE

**Business Rules (Enforced in Application Layer):**
- If `assignment_type = 'SCHOOL'`, then `school_id` NOT NULL, `student_id` NULL
- If `assignment_type = 'STUDENT'`, then `student_id` NOT NULL, `school_id` NULL
- For SCHOOL assignments: school_id must match authenticated user's school_id (tenant isolation)
- For STUDENT assignments: student's school_id must match authenticated user's school_id (tenant isolation)

**Tenant Isolation:**
- Admin can assign to any school or student
- School role: Cannot create assignments (read-only)
- Student role: Cannot create assignments (read-only)
- All queries filter by authenticated user's school_id context

---

### Table 4: `exam_overrides`
**Purpose:** Per-student overrides for exam access and deadlines

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Override identifier |
| exam_id | UUID | NOT NULL, FK -> exams.id | Exam reference |
| student_id | UUID | NOT NULL, FK -> users.id | Student reference |
| lock_mode | ENUM('LOCK', 'UNLOCK', 'DEFAULT') | NOT NULL, DEFAULT 'DEFAULT' | Lock override mode |
| override_ends_at | TIMESTAMP | NULLABLE | Extended deadline (NULL = use exam.ends_at) |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `(exam_id, student_id)` - One override per student per exam
- INDEX on `exam_id`
- INDEX on `student_id`

**Constraints:**
- FOREIGN KEY `exam_id` REFERENCES `exams(id)` ON DELETE CASCADE
- FOREIGN KEY `student_id` REFERENCES `users(id)` ON DELETE CASCADE

**Lock Mode Values:**
- **LOCK** - Force exam to be locked for this student (overrides global setting)
- **UNLOCK** - Force exam to be unlocked for this student (overrides global setting)
- **DEFAULT** - Use exam's global `is_globally_locked` setting

**Tenant Isolation:**
- Admin can create overrides for any student
- Overrides only apply to students within their school_id context
- School/Student roles: Cannot create overrides (read-only)

---

### Tables NOT Included in Sprint 2 Phase 2:

**Deferred to Future Sprints:**
- `classes` - Class management (future extension)
- `groups` - Group management (future extension)
- `class_students` - Student-class memberships (future extension)
- `group_students` - Student-group memberships (future extension)
- `exam_attempts` - Attempt tracking (Sprint 3+)
- `attempt_answers` - Student answers (Sprint 3+)

---

## 2) STATE RESOLVER RULES (EXACT PRIORITY ORDER)

### State Resolution Algorithm (Bulletproof)

```
FUNCTION resolveExamState(exam, student, current_timestamp):
    
    // Step 1: Fetch override (if exists)
    override = getOverride(exam.id, student.id)
    
    // Step 2: Determine Lock Status
    is_locked = false
    
    IF override EXISTS:
        IF override.lock_mode = 'LOCK':
            is_locked = true
        ELSE IF override.lock_mode = 'UNLOCK':
            is_locked = false
        ELSE IF override.lock_mode = 'DEFAULT':
            is_locked = exam.is_globally_locked
    ELSE:
        is_locked = exam.is_globally_locked
    
    // Step 3: Return LOCKED if locked (HIGHEST PRIORITY)
    IF is_locked = true:
        RETURN 'LOCKED'
    
    // Step 4: Determine Effective End Time
    effective_ends_at = exam.ends_at
    
    IF override EXISTS AND override.override_ends_at IS NOT NULL:
        effective_ends_at = override.override_ends_at
    
    // Step 5: Check Timing (SECOND PRIORITY)
    IF current_timestamp < exam.starts_at:
        RETURN 'UPCOMING'
    
    // Step 6: Check Expiration (THIRD PRIORITY)
    IF current_timestamp > effective_ends_at:
        RETURN 'EXPIRED'
    
    // Step 7: Default State (LOWEST PRIORITY)
    RETURN 'AVAILABLE'
```

### State Definitions

- **LOCKED** - Student cannot access exam (admin locked it via global setting or override)
- **UPCOMING** - Exam not yet started (current time < starts_at)
- **AVAILABLE** - Student can view exam details (within time window, not locked)
- **EXPIRED** - Exam deadline passed (current time > ends_at or override_ends_at)

### Priority Order (Strict)

```
1. LOCKED (highest priority - overrides everything)
2. UPCOMING (if not locked and before starts_at)
3. EXPIRED (if not locked and after ends_at)
4. AVAILABLE (default if none of the above)
```

### Assignment Resolution (Who can see this exam?)

```
FUNCTION isExamAssignedToStudent(exam_id, student_id):
    
    student = getStudent(student_id)
    
    // Check direct student assignment
    IF EXISTS exam_assignment WHERE:
        exam_assignment.exam_id = exam_id
        AND exam_assignment.assignment_type = 'STUDENT'
        AND exam_assignment.student_id = student_id
    THEN:
        RETURN true
    
    // Check school-wide assignment
    IF EXISTS exam_assignment WHERE:
        exam_assignment.exam_id = exam_id
        AND exam_assignment.assignment_type = 'SCHOOL'
        AND exam_assignment.school_id = student.school_id
    THEN:
        RETURN true
    
    // No assignment found
    RETURN false
```

---

## 3) MINIMAL ENDPOINT LIST (12 ENDPOINTS MAXIMUM)

### Admin Endpoints (8 endpoints)

1. **POST /admin/exams** - Create new exam
2. **GET /admin/exams** - List all exams (paginated)
3. **GET /admin/exams/{id}** - Show exam details with questions
4. **PUT /admin/exams/{id}** - Update exam metadata
5. **POST /admin/exams/{id}/questions** - Add question to exam (with points and order)
6. **DELETE /admin/exams/{id}/questions/{question_id}** - Remove question from exam
7. **POST /admin/exams/{id}/assignments** - Create assignment (school or students)
8. **POST /admin/exams/{id}/overrides** - Create/update student override

### School Endpoints (2 endpoints)

9. **GET /school/exams** - List assigned exams (read-only)
10. **GET /school/exams/{id}** - Show exam details (read-only, no correct answers)

### Student Endpoints (2 endpoints)

11. **GET /student/exams** - List assigned exams with resolved state (read-only)
12. **GET /student/exams/{id}** - Show exam details with state (read-only, no correct answers)

### Endpoint Details

#### 1. POST /admin/exams
```json
Request:
{
  "title_en": "Mathematics Final Exam",
  "title_ar": "الامتحان النهائي للرياضيات",
  "duration_minutes": 120,
  "starts_at": "2024-06-01 09:00:00",
  "ends_at": "2024-06-01 12:00:00",
  "max_attempts": 5,
  "is_globally_locked": false
}

Response (201):
{
  "id": "uuid",
  "title_en": "Mathematics Final Exam",
  "title_ar": "الامتحان النهائي للرياضيات",
  "duration_minutes": 120,
  "starts_at": "2024-06-01T09:00:00Z",
  "ends_at": "2024-06-01T12:00:00Z",
  "max_attempts": 5,
  "is_globally_locked": false,
  "total_points": 0,
  "questions_count": 0
}
```

#### 2. GET /admin/exams
```json
Response (200):
{
  "data": [
    {
      "id": "uuid",
      "title_en": "Mathematics Final Exam",
      "duration_minutes": 120,
      "starts_at": "2024-06-01T09:00:00Z",
      "ends_at": "2024-06-01T12:00:00Z",
      "questions_count": 20,
      "total_points": 100
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50
  }
}
```

#### 3. GET /admin/exams/{id}
```json
Response (200):
{
  "id": "uuid",
  "title_en": "Mathematics Final Exam",
  "title_ar": "الامتحان النهائي للرياضيات",
  "duration_minutes": 120,
  "starts_at": "2024-06-01T09:00:00Z",
  "ends_at": "2024-06-01T12:00:00Z",
  "max_attempts": 5,
  "is_globally_locked": false,
  "total_points": 100,
  "questions": [
    {
      "id": "uuid",
      "order_index": 1,
      "points": 5,
      "question": {
        "type": "MCQ",
        "difficulty": "MEDIUM",
        "prompt_en": "What is 2+2?"
      }
    }
  ]
}
```

#### 4. PUT /admin/exams/{id}
```json
Request:
{
  "title_en": "Updated Title",
  "duration_minutes": 150,
  "is_globally_locked": true
}

Response (200):
{
  "id": "uuid",
  "title_en": "Updated Title",
  "duration_minutes": 150,
  "is_globally_locked": true
}
```

#### 5. POST /admin/exams/{id}/questions
```json
Request:
{
  "question_id": "uuid",
  "points": 5,
  "order_index": 1
}

Response (201):
{
  "id": "uuid",
  "exam_id": "uuid",
  "question_id": "uuid",
  "points": 5,
  "order_index": 1
}
```

#### 6. DELETE /admin/exams/{id}/questions/{question_id}
```json
Response (200):
{
  "message": "Question removed from exam"
}
```

#### 7. POST /admin/exams/{id}/assignments
```json
Request (School-wide):
{
  "assignment_type": "SCHOOL",
  "school_id": "uuid"
}

Request (Specific Students):
{
  "assignment_type": "STUDENT",
  "student_ids": ["uuid1", "uuid2", "uuid3"]
}

Response (201):
{
  "message": "Assignment created successfully",
  "assignments_count": 3
}
```

#### 8. POST /admin/exams/{id}/overrides
```json
Request:
{
  "student_id": "uuid",
  "lock_mode": "UNLOCK",
  "override_ends_at": "2024-06-02 12:00:00"
}

Response (201):
{
  "id": "uuid",
  "exam_id": "uuid",
  "student_id": "uuid",
  "lock_mode": "UNLOCK",
  "override_ends_at": "2024-06-02T12:00:00Z"
}
```

#### 9. GET /school/exams
```json
Response (200):
{
  "data": [
    {
      "id": "uuid",
      "title_en": "Mathematics Final Exam",
      "title_ar": "الامتحان النهائي للرياضيات",
      "duration_minutes": 120,
      "starts_at": "2024-06-01T09:00:00Z",
      "ends_at": "2024-06-01T12:00:00Z",
      "questions_count": 20
    }
  ]
}
```

#### 10. GET /school/exams/{id}
```json
Response (200):
{
  "id": "uuid",
  "title_en": "Mathematics Final Exam",
  "duration_minutes": 120,
  "starts_at": "2024-06-01T09:00:00Z",
  "ends_at": "2024-06-01T12:00:00Z",
  "questions_count": 20,
  "total_points": 100
}
```

#### 11. GET /student/exams
```json
Response (200):
{
  "data": [
    {
      "id": "uuid",
      "title_en": "Mathematics Final Exam",
      "title_ar": "الامتحان النهائي للرياضيات",
      "duration_minutes": 120,
      "starts_at": "2024-06-01T09:00:00Z",
      "ends_at": "2024-06-01T12:00:00Z",
      "state": "AVAILABLE",
      "questions_count": 20
    }
  ]
}
```

#### 12. GET /student/exams/{id}
```json
Response (200):
{
  "id": "uuid",
  "title_en": "Mathematics Final Exam",
  "title_ar": "الامتحان النهائي للرياضيات",
  "duration_minutes": 120,
  "starts_at": "2024-06-01T09:00:00Z",
  "ends_at": "2024-06-01T12:00:00Z",
  "state": "AVAILABLE",
  "questions_count": 20,
  "total_points": 100
}
```

---

## 4) MINIMAL ADMIN UI PAGES (BLADE)

### Page 1: Exams List (`/admin/exams`)
**Purpose:** List all exams with basic info

**Elements:**
- Table with columns: Title (EN), Duration, Start Date, End Date, Questions Count, Actions
- "Create Exam" button
- Edit and Delete buttons per row
- Pagination

### Page 2: Create Exam Form (`/admin/exams/create`)
**Purpose:** Create new exam

**Form Fields:**
- Title (English) - text input
- Title (Arabic) - text input
- Duration (minutes) - number input
- Start Date/Time - datetime input
- End Date/Time - datetime input
- Max Attempts - number input (default 5)
- Globally Locked - checkbox
- Submit and Cancel buttons

### Page 3: Edit Exam Form (`/admin/exams/{id}/edit`)
**Purpose:** Edit existing exam metadata

**Form Fields:**
- Same as Create Exam Form
- Pre-filled with existing values
- Submit and Cancel buttons

### Page 4: Exam Details with Question Management (`/admin/exams/{id}`)
**Purpose:** View exam details and manage questions, assignments, overrides

**Sections:**

**A) Exam Info Section:**
- Display: Title, Duration, Dates, Max Attempts, Lock Status
- Edit and Delete buttons

**B) Questions Section:**
- Table with columns: Order, Question Prompt (EN), Type, Difficulty, Points, Actions
- "Add Question" button (opens modal)
- Remove button per question
- Drag handles for reordering (optional)
- Display total points

**C) Assignments Section:**
- List of assignments with type (SCHOOL/STUDENT) and target
- "Create Assignment" button (opens modal)
- Remove button per assignment

**D) Overrides Section:**
- List of overrides with student name, lock mode, extended deadline
- "Add Override" button (opens modal)
- Edit/Remove buttons per override

### Modal 1: Add Question to Exam
**Purpose:** Select question from question bank and add to exam

**Elements:**
- Search/filter questions by type, difficulty, lesson
- List of questions with checkboxes
- Points input field
- Order index input field
- "Add Selected" and "Cancel" buttons

### Modal 2: Create Assignment
**Purpose:** Assign exam to school or students

**Elements:**
- Radio buttons: "Entire School" or "Specific Students"
- If "Entire School": School dropdown
- If "Specific Students": School dropdown + Student multi-select
- "Create Assignment" and "Cancel" buttons

### Modal 3: Add/Edit Override
**Purpose:** Set per-student override

**Elements:**
- Student search/select
- Radio buttons for lock mode: DEFAULT, LOCK, UNLOCK
- Optional: Extended deadline datetime input
- "Save Override" and "Cancel" buttons

---

## 5) SCHOOL & STUDENT UI PAGES (READ-ONLY)

### School Page 1: Exams List (`/school/exams`)
**Purpose:** View assigned exams (read-only)

**Elements:**
- Table with columns: Title (EN/AR), Duration, Start Date, End Date, Questions Count
- View Details button per row
- No create/edit/delete actions

### School Page 2: Exam Details (`/school/exams/{id}`)
**Purpose:** View exam details (read-only, no correct answers)

**Elements:**
- Display: Title, Duration, Dates, Questions Count, Total Points
- Note: "Correct answers are not visible"
- Back button

### Student Page 1: Exams List (`/student/exams`)
**Purpose:** View assigned exams with state (read-only)

**Elements:**
- Cards/List showing:
  - Exam title (EN/AR)
  - Duration
  - Dates
  - State badge (🟢 AVAILABLE, 🔵 UPCOMING, 🔴 EXPIRED, 🔒 LOCKED)
  - Questions count
  - View Details button
- No create/edit/delete actions

### Student Page 2: Exam Details (`/student/exams/{id}`)
**Purpose:** View exam details with state (read-only, no correct answers)

**Elements:**
- Display: Title, Duration, Dates, Questions Count, Total Points
- State badge with explanation
- If AVAILABLE: Instructions message
- If UPCOMING: "Available on [date]" message
- If EXPIRED: "Deadline passed" message
- If LOCKED: "Exam is locked" message
- Note: "Correct answers and scores are not shown"
- Back button

---

## 6) IMPLEMENTATION SCOPE FOR SPRINT 2 PHASE 2

### What WILL Be Implemented:

**Migrations (4 files):**
1. `create_exams_table.php`
2. `create_exam_questions_table.php`
3. `create_exam_assignments_table.php`
4. `create_exam_overrides_table.php`

**Models (4 files):**
1. `Exam.php` - with relationships to questions, assignments, overrides
2. `ExamQuestion.php` - pivot model with points and order
3. `ExamAssignment.php` - with type enum and relationships
4. `ExamOverride.php` - with lock mode enum

**Controllers (6 files):**
1. `Admin/ExamController.php` - CRUD for exams
2. `Admin/ExamQuestionController.php` - Manage exam questions
3. `Admin/ExamAssignmentController.php` - Create assignments
4. `Admin/ExamOverrideController.php` - Create overrides
5. `School/ExamController.php` - Read-only list and show
6. `Student/ExamController.php` - Read-only list and show with state

**Services (1 file):**
1. `ExamStateResolver.php` - State resolution logic

**Views (11 files):**
1. `admin/exams/index.blade.php`
2. `admin/exams/create.blade.php`
3. `admin/exams/edit.blade.php`
4. `admin/exams/show.blade.php` (with questions/assignments/overrides)
5. `admin/exams/partials/add-question-modal.blade.php`
6. `admin/exams/partials/create-assignment-modal.blade.php`
7. `admin/exams/partials/add-override-modal.blade.php`
8. `school/exams/index.blade.php`
9. `school/exams/show.blade.php`
10. `student/exams/index.blade.php`
11. `student/exams/show.blade.php`

**Routes:**
- 12 routes as specified in endpoint list

**Seeders:**
- Update `DatabaseSeeder.php` to include sample exam data

### What WILL NOT Be Implemented (Deferred):

❌ Classes and Groups tables/models/controllers
❌ Exam Attempts tracking
❌ Attempt submission and grading
❌ Answer storage
❌ Score calculation
❌ Anti-cheat features
❌ Reporting and analytics
❌ Class/Group assignment types

---

## 7) SECURITY & TENANT ISOLATION

### Tenant Isolation Rules:

1. **Admin Role:**
   - Can create exams (global, no school_id)
   - Can assign exams to any school or student
   - Can create overrides for any student
   - Bypasses tenant checks

2. **School Role:**
   - Can only view exams assigned to their school_id
   - school_id derived from `auth()->user()->school_id`
   - Cannot create/edit/delete exams
   - Cannot create assignments or overrides
   - Read-only access

3. **Student Role:**
   - Can only view exams assigned to them (via school or direct assignment)
   - school_id derived from `auth()->user()->school_id`
   - Cannot create/edit/delete exams
   - Cannot create assignments or overrides
   - Read-only access
   - Never sees correct answers
   - Never sees scores (not implemented in Sprint 2)

### Data Privacy:

- Students NEVER see `is_correct` flag from `question_options`
- Students NEVER see scores or grades (not implemented yet)
- School users NEVER see scores or grades (not implemented yet)
- Only Admin can see correct answers in question bank

---

## APPROVAL CHECKPOINT

This PATCH document corrects the scope to match strict requirements.

**What Has Been Corrected:**
✅ Removed Classes/Groups from Sprint 2 Phase 2
✅ Removed Exam Attempts from Sprint 2 Phase 2
✅ Reduced to 4 database tables only
✅ Reduced to 12 endpoints maximum
✅ Reduced to minimal admin UI pages
✅ Clarified tenant isolation rules
✅ Removed all scope creep and assumptions

**Sprint 2 Phase 2 Scope (Final):**
- 4 database tables (exams, exam_questions, exam_assignments, exam_overrides)
- 12 API endpoints (8 admin, 2 school, 2 student)
- 11 Blade views (7 admin, 2 school, 2 student)
- State resolver service
- Read-only lists for School and Student

**Future Extensions (NOT in Sprint 2):**
- Classes and Groups (Sprint 3+)
- Exam Attempts (Sprint 3+)
- Grading and Scoring (Sprint 3+)
- Anti-cheat (Sprint 4+)
- Reporting (Sprint 4+)

---

## QUESTION FOR APPROVAL

**Do you approve this corrected scope for Sprint 2 – Phase 2 (Database migrations + code implementation)?**

Please confirm before I proceed with writing any code.
