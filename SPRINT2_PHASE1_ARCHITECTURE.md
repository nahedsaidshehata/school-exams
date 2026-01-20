# SPRINT 2 – PHASE 1: ARCHITECTURE & DESIGN
## Exam Builder & Assignment System (DESIGN ONLY - NO CODE)

---

## 1) MODULE BREAKDOWN

### Module 1: Exam Builder (Admin Only)
**Purpose:** Create and manage exams with fixed question sets and point allocations

**Responsibilities:**
- Create exam metadata (title, duration, dates, attempts limit, lock status)
- Attach questions from central question bank to exam
- Assign points to each question
- Calculate total exam points
- Manage exam lifecycle (draft, published, archived)

**Key Operations:**
- Create exam
- Edit exam metadata
- Add/remove questions to/from exam
- Set points per question
- Publish/unpublish exam
- Delete exam (if no attempts exist)

---

### Module 2: Assignment Manager (Admin Only)
**Purpose:** Assign exams to schools, classes, groups, or individual students

**Responsibilities:**
- Create assignments linking exams to target entities
- Support multiple assignment types (school-wide, class, group, individual)
- Track assignment status
- Manage assignment lifecycle

**Key Operations:**
- Assign exam to school (all students in school)
- Assign exam to class (all students in class)
- Assign exam to group (all students in group)
- Assign exam to specific students
- View assignments per exam
- Remove assignments

**Assignment Types:**
1. **School Assignment** - All students in a school
2. **Class Assignment** - All students in a specific class
3. **Group Assignment** - All students in a specific group
4. **Individual Assignment** - Specific students only

---

### Module 3: Override Manager (Admin Only)
**Purpose:** Set per-student overrides for exam access and deadlines

**Responsibilities:**
- Override lock status for individual students
- Extend deadlines for individual students
- Track override history
- Resolve final exam state per student

**Key Operations:**
- Set student lock override (LOCK, UNLOCK, DEFAULT)
- Set student deadline extension
- Remove override (revert to default)
- View all overrides for an exam

**Override Types:**
1. **Lock Override** - Force lock/unlock regardless of global setting
2. **Deadline Extension** - Allow submission after global ends_at

---

### Module 4: Exam State Resolver (Server-Side Logic)
**Purpose:** Determine exact exam state for a student at any given moment

**Responsibilities:**
- Calculate current exam state per student
- Apply priority rules for state resolution
- Consider global settings and per-student overrides
- Return one of four states: LOCKED, UPCOMING, AVAILABLE, EXPIRED

**State Resolution Priority (Bulletproof Rules):**
```
INPUT: exam, student, current_timestamp, override (nullable)

STEP 1: Check Lock Status
  IF override EXISTS AND override.lock_mode = 'LOCK'
    RETURN 'LOCKED'
  
  IF override EXISTS AND override.lock_mode = 'DEFAULT' AND exam.is_globally_locked = true
    RETURN 'LOCKED'
  
  IF override DOES NOT EXIST AND exam.is_globally_locked = true
    RETURN 'LOCKED'

STEP 2: Check Timing (if not locked)
  effective_ends_at = override.override_ends_at ?? exam.ends_at
  
  IF current_timestamp < exam.starts_at
    RETURN 'UPCOMING'
  
  IF current_timestamp > effective_ends_at
    RETURN 'EXPIRED'

STEP 3: Default State
  RETURN 'AVAILABLE'
```

**State Definitions:**
- **LOCKED** - Student cannot access exam (admin locked it)
- **UPCOMING** - Exam not yet started (before starts_at)
- **AVAILABLE** - Student can take exam (within time window, not locked, attempts remaining)
- **EXPIRED** - Exam deadline passed (after ends_at or override_ends_at)

---

## 2) DATABASE TABLES TO ADD

### Table: `exams`
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

### Table: `exam_questions`
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

### Table: `classes`
**Purpose:** Store class information (if not already exists)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Class identifier |
| school_id | UUID | NOT NULL, FK -> schools.id | School reference |
| name_en | VARCHAR(255) | NOT NULL | Class name (English) |
| name_ar | VARCHAR(255) | NOT NULL | Class name (Arabic) |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `school_id`
- UNIQUE on `(school_id, name_en)`

**Constraints:**
- FOREIGN KEY `school_id` REFERENCES `schools(id)` ON DELETE CASCADE

---

### Table: `groups`
**Purpose:** Store group information (if not already exists)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Group identifier |
| school_id | UUID | NOT NULL, FK -> schools.id | School reference |
| name_en | VARCHAR(255) | NOT NULL | Group name (English) |
| name_ar | VARCHAR(255) | NOT NULL | Group name (Arabic) |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `school_id`
- UNIQUE on `(school_id, name_en)`

**Constraints:**
- FOREIGN KEY `school_id` REFERENCES `schools(id)` ON DELETE CASCADE

---

### Table: `class_students`
**Purpose:** Link students to classes (if not already exists)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Record identifier |
| class_id | UUID | NOT NULL, FK -> classes.id | Class reference |
| student_id | UUID | NOT NULL, FK -> users.id | Student reference |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `(class_id, student_id)` - Student can't be in same class twice
- INDEX on `class_id`
- INDEX on `student_id`

**Constraints:**
- FOREIGN KEY `class_id` REFERENCES `classes(id)` ON DELETE CASCADE
- FOREIGN KEY `student_id` REFERENCES `users(id)` ON DELETE CASCADE

---

### Table: `group_students`
**Purpose:** Link students to groups (if not already exists)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Record identifier |
| group_id | UUID | NOT NULL, FK -> groups.id | Group reference |
| student_id | UUID | NOT NULL, FK -> users.id | Student reference |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `(group_id, student_id)` - Student can't be in same group twice
- INDEX on `group_id`
- INDEX on `student_id`

**Constraints:**
- FOREIGN KEY `group_id` REFERENCES `groups(id)` ON DELETE CASCADE
- FOREIGN KEY `student_id` REFERENCES `users(id)` ON DELETE CASCADE

---

### Table: `exam_assignments`
**Purpose:** Assign exams to schools, classes, groups, or students

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Assignment identifier |
| exam_id | UUID | NOT NULL, FK -> exams.id | Exam reference |
| assignment_type | ENUM | NOT NULL | 'SCHOOL', 'CLASS', 'GROUP', 'STUDENT' |
| school_id | UUID | NULLABLE, FK -> schools.id | For SCHOOL type |
| class_id | UUID | NULLABLE, FK -> classes.id | For CLASS type |
| group_id | UUID | NULLABLE, FK -> groups.id | For GROUP type |
| student_id | UUID | NULLABLE, FK -> users.id | For STUDENT type |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- INDEX on `exam_id`
- INDEX on `school_id`
- INDEX on `class_id`
- INDEX on `group_id`
- INDEX on `student_id`
- INDEX on `(exam_id, assignment_type, school_id)` - For school assignments
- INDEX on `(exam_id, assignment_type, class_id)` - For class assignments
- INDEX on `(exam_id, assignment_type, group_id)` - For group assignments
- INDEX on `(exam_id, assignment_type, student_id)` - For student assignments

**Constraints:**
- FOREIGN KEY `exam_id` REFERENCES `exams(id)` ON DELETE CASCADE
- FOREIGN KEY `school_id` REFERENCES `schools(id)` ON DELETE CASCADE
- FOREIGN KEY `class_id` REFERENCES `classes(id)` ON DELETE CASCADE
- FOREIGN KEY `group_id` REFERENCES `groups(id)` ON DELETE CASCADE
- FOREIGN KEY `student_id` REFERENCES `users(id)` ON DELETE CASCADE
- CHECK: Exactly ONE of (school_id, class_id, group_id, student_id) must be NOT NULL based on assignment_type

**Business Rules:**
- If `assignment_type = 'SCHOOL'`, then `school_id` NOT NULL, others NULL
- If `assignment_type = 'CLASS'`, then `class_id` NOT NULL, others NULL
- If `assignment_type = 'GROUP'`, then `group_id` NOT NULL, others NULL
- If `assignment_type = 'STUDENT'`, then `student_id` NOT NULL, others NULL

---

### Table: `exam_overrides`
**Purpose:** Per-student overrides for exam access and deadlines

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Override identifier |
| exam_id | UUID | NOT NULL, FK -> exams.id | Exam reference |
| student_id | UUID | NOT NULL, FK -> users.id | Student reference |
| lock_mode | ENUM | NOT NULL, DEFAULT 'DEFAULT' | 'LOCK', 'UNLOCK', 'DEFAULT' |
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
- CHECK: If `override_ends_at` IS NOT NULL, then `override_ends_at > exam.starts_at`

**Lock Mode Values:**
- **LOCK** - Force exam to be locked for this student (overrides global setting)
- **UNLOCK** - Force exam to be unlocked for this student (overrides global setting)
- **DEFAULT** - Use exam's global `is_globally_locked` setting

---

### Table: `exam_attempts`
**Purpose:** Track student exam attempts (for future implementation)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PRIMARY KEY | Attempt identifier |
| exam_id | UUID | NOT NULL, FK -> exams.id | Exam reference |
| student_id | UUID | NOT NULL, FK -> users.id | Student reference |
| attempt_number | INTEGER | NOT NULL | Attempt sequence (1, 2, 3...) |
| started_at | TIMESTAMP | NOT NULL | When attempt started |
| submitted_at | TIMESTAMP | NULLABLE | When attempt submitted (NULL = in progress) |
| score_percentage | DECIMAL(5,2) | NULLABLE | Final score 0-100 (NULL = not graded) |
| total_points_earned | DECIMAL(8,2) | NULLABLE | Points earned |
| total_points_possible | DECIMAL(8,2) | NOT NULL | Total exam points |
| status | ENUM | NOT NULL | 'IN_PROGRESS', 'SUBMITTED', 'GRADED' |
| created_at | TIMESTAMP | NOT NULL | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Last update timestamp |

**Indexes:**
- PRIMARY KEY on `id`
- UNIQUE on `(exam_id, student_id, attempt_number)` - No duplicate attempt numbers
- INDEX on `exam_id`
- INDEX on `student_id`
- INDEX on `(student_id, exam_id, score_percentage)` - For finding best attempt

**Constraints:**
- FOREIGN KEY `exam_id` REFERENCES `exams(id)` ON DELETE CASCADE
- FOREIGN KEY `student_id` REFERENCES `users(id)` ON DELETE CASCADE
- CHECK: `attempt_number > 0`
- CHECK: `score_percentage >= 0 AND score_percentage <= 100` (if not NULL)
- CHECK: `submitted_at >= started_at` (if not NULL)

---

## 3) STATE MACHINE & DECISION RULES

### Exam State Resolution Algorithm (Bulletproof)

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
    
    // Step 3: Return LOCKED if locked
    IF is_locked = true:
        RETURN 'LOCKED'
    
    // Step 4: Determine Effective End Time
    effective_ends_at = exam.ends_at
    
    IF override EXISTS AND override.override_ends_at IS NOT NULL:
        effective_ends_at = override.override_ends_at
    
    // Step 5: Check Timing
    IF current_timestamp < exam.starts_at:
        RETURN 'UPCOMING'
    
    IF current_timestamp > effective_ends_at:
        RETURN 'EXPIRED'
    
    // Step 6: Default State
    RETURN 'AVAILABLE'
```

### State Transition Rules

```
LOCKED → Cannot transition (admin must unlock)
UPCOMING → AVAILABLE (when current_time >= starts_at)
AVAILABLE → EXPIRED (when current_time > ends_at)
EXPIRED → Cannot transition (exam ended)
```

### Attempt Validation Rules

```
FUNCTION canStartAttempt(exam, student, current_timestamp):
    
    // Step 1: Check if student has assignment
    IF NOT hasAssignment(exam.id, student.id):
        RETURN false, "Exam not assigned to you"
    
    // Step 2: Resolve exam state
    state = resolveExamState(exam, student, current_timestamp)
    
    IF state != 'AVAILABLE':
        RETURN false, "Exam is " + state
    
    // Step 3: Check attempt count
    attempts_count = countAttempts(exam.id, student.id)
    
    IF attempts_count >= exam.max_attempts:
        RETURN false, "Maximum attempts reached"
    
    // Step 4: Check for in-progress attempt
    IF hasInProgressAttempt(exam.id, student.id):
        RETURN false, "You have an in-progress attempt"
    
    // Step 5: All checks passed
    RETURN true, "Can start attempt"
```

### Assignment Resolution (Who can see this exam?)

```
FUNCTION isExamAssignedToStudent(exam_id, student_id):
    
    student = getStudent(student_id)
    
    // Check direct student assignment
    IF EXISTS assignment WHERE:
        assignment.exam_id = exam_id
        AND assignment.assignment_type = 'STUDENT'
        AND assignment.student_id = student_id
    THEN:
        RETURN true
    
    // Check school-wide assignment
    IF EXISTS assignment WHERE:
        assignment.exam_id = exam_id
        AND assignment.assignment_type = 'SCHOOL'
        AND assignment.school_id = student.school_id
    THEN:
        RETURN true
    
    // Check class assignments
    student_classes = getStudentClasses(student_id)
    FOR EACH class IN student_classes:
        IF EXISTS assignment WHERE:
            assignment.exam_id = exam_id
            AND assignment.assignment_type = 'CLASS'
            AND assignment.class_id = class.id
        THEN:
            RETURN true
    
    // Check group assignments
    student_groups = getStudentGroups(student_id)
    FOR EACH group IN student_groups:
        IF EXISTS assignment WHERE:
            assignment.exam_id = exam_id
            AND assignment.assignment_type = 'GROUP'
            AND assignment.group_id = group.id
        THEN:
            RETURN true
    
    // No assignment found
    RETURN false
```

---

## 4) API CONTRACT SKETCH (Endpoints + Request/Response)

### Admin Endpoints

#### Exams Management

**POST /admin/exams**
Create new exam
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
  "questions_count": 0,
  "created_at": "2024-05-15T10:00:00Z"
}
```

**GET /admin/exams**
List all exams
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
      "max_attempts": 5,
      "is_globally_locked": false,
      "total_points": 100,
      "questions_count": 20,
      "assignments_count": 5,
      "attempts_count": 150
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50
  }
}
```

**GET /admin/exams/{id}**
Get exam details
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
      "type": "MCQ",
      "difficulty": "MEDIUM",
      "prompt_en": "What is 2+2?",
      "prompt_ar": "ما هو 2+2؟"
    }
  ]
}
```

**PUT /admin/exams/{id}**
Update exam metadata
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
  // ... updated fields
}
```

**DELETE /admin/exams/{id}**
Delete exam (only if no attempts)
```json
Response (200):
{
  "message": "Exam deleted successfully"
}

Response (400) if attempts exist:
{
  "error": "Cannot delete exam with existing attempts"
}
```

#### Exam Questions Management

**POST /admin/exams/{exam_id}/questions**
Add question to exam
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
  "order_index": 1,
  "question": {
    "type": "MCQ",
    "difficulty": "MEDIUM",
    "prompt_en": "What is 2+2?"
  }
}
```

**PUT /admin/exams/{exam_id}/questions/{id}**
Update question points or order
```json
Request:
{
  "points": 10,
  "order_index": 2
}

Response (200):
{
  "id": "uuid",
  "points": 10,
  "order_index": 2
}
```

**DELETE /admin/exams/{exam_id}/questions/{id}**
Remove question from exam
```json
Response (200):
{
  "message": "Question removed from exam"
}
```

**POST /admin/exams/{exam_id}/questions/reorder**
Reorder all questions
```json
Request:
{
  "questions": [
    {"id": "uuid1", "order_index": 1},
    {"id": "uuid2", "order_index": 2},
    {"id": "uuid3", "order_index": 3}
  ]
}

Response (200):
{
  "message": "Questions reordered successfully"
}
```

#### Assignments Management

**POST /admin/exams/{exam_id}/assignments**
Create assignment
```json
Request (School-wide):
{
  "assignment_type": "SCHOOL",
  "school_id": "uuid"
}

Request (Class):
{
  "assignment_type": "CLASS",
  "class_id": "uuid"
}

Request (Group):
{
  "assignment_type": "GROUP",
  "group_id": "uuid"
}

Request (Individual):
{
  "assignment_type": "STUDENT",
  "student_ids": ["uuid1", "uuid2", "uuid3"]
}

Response (201):
{
  "assignments": [
    {
      "id": "uuid",
      "exam_id": "uuid",
      "assignment_type": "SCHOOL",
      "school_id": "uuid",
      "school_name": "Al-Noor School",
      "affected_students_count": 500
    }
  ]
}
```

**GET /admin/exams/{exam_id}/assignments**
List exam assignments
```json
Response (200):
{
  "data": [
    {
      "id": "uuid",
      "assignment_type": "SCHOOL",
      "school_id": "uuid",
      "school_name": "Al-Noor School",
      "affected_students_count": 500
    },
    {
      "id": "uuid",
      "assignment_type": "CLASS",
      "class_id": "uuid",
      "class_name": "Grade 10-A",
      "affected_students_count": 30
    }
  ]
}
```

**DELETE /admin/exams/{exam_id}/assignments/{id}**
Remove assignment
```json
Response (200):
{
  "message": "Assignment removed successfully"
}
```

#### Overrides Management

**POST /admin/exams/{exam_id}/overrides**
Create/update student override
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
  "student_name": "Ahmed Ali",
  "lock_mode": "UNLOCK",
  "override_ends_at": "2024-06-02T12:00:00Z"
}
```

**GET /admin/exams/{exam_id}/overrides**
List exam overrides
```json
Response (200):
{
  "data": [
    {
      "id": "uuid",
      "student_id": "uuid",
      "student_name": "Ahmed Ali",
      "lock_mode": "UNLOCK",
      "override_ends_at": "2024-06-02T12:00:00Z"
    }
  ]
}
```

**DELETE /admin/exams/{exam_id}/overrides/{id}**
Remove override
```json
Response (200):
{
  "message": "Override removed successfully"
}
```

#### Classes & Groups Management

**POST /admin/schools/{school_id}/classes**
Create class
```json
Request:
{
  "name_en": "Grade 10-A",
  "name_ar": "الصف العاشر - أ"
}

Response (201):
{
  "id": "uuid",
  "school_id": "uuid",
  "name_en": "Grade 10-A",
  "name_ar": "الصف العاشر - أ",
  "students_count": 0
}
```

**POST /admin/classes/{class_id}/students**
Add students to class
```json
Request:
{
  "student_ids": ["uuid1", "uuid2", "uuid3"]
}

Response (200):
{
  "message": "3 students added to class"
}
```

**POST /admin/schools/{school_id}/groups**
Create group
```json
Request:
{
  "name_en": "Advanced Math Group",
  "name_ar": "مجموعة الرياضيات المتقدمة"
}

Response (201):
{
  "id": "uuid",
  "school_id": "uuid",
  "name_en": "Advanced Math Group",
  "name_ar": "مجموعة الرياضيات المتقدمة",
  "students_count": 0
}
```

**POST /admin/groups/{group_id}/students**
Add students to group
```json
Request:
{
  "student_ids": ["uuid1", "uuid2", "uuid3"]
}

Response (200):
{
  "message": "3 students added to group"
}
```

---

### School Endpoints (Read-Only)

**GET /school/exams**
List assigned exams for school
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
      "questions_count": 20,
      "assigned_students_count": 500,
      "completed_attempts_count": 350
    }
  ]
}
```

**GET /school/exams/{id}**
View exam details (no correct answers)
```json
Response (200):
{
  "id": "uuid",
  "title_en": "Mathematics Final Exam",
  "duration_minutes": 120,
  "starts_at": "2024-06-01T09:00:00Z",
  "ends_at": "2024-06-01T12:00:00Z",
  "questions_count": 20,
  "total_points": 100,
  "assigned_students": [
    {
      "student_id": "uuid",
      "student_name": "Ahmed Ali",
      "attempts_count": 2,
      "status": "COMPLETED"
    }
  ]
}
```

---

### Student Endpoints (Read-Only)

**GET /student/exams**
List assigned exams
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
      "attempts_used": 2,
      "max_attempts": 5,
      "questions_count": 20
    }
  ]
}
```

**GET /student/exams/{id}**
View exam details (no correct answers, no scores)
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
  "attempts_used": 2,
  "max_attempts": 5,
  "questions_count": 20,
  "total_points": 100,
  "can_start_attempt": true,
  "message": null
}

Response when LOCKED:
{
  "id": "uuid",
  "title_en": "Mathematics Final Exam",
  "state": "LOCKED",
  "can_start_attempt": false,
  "message": "This exam is currently locked"
}

Response when UPCOMING:
{
  "id": "uuid",
  "title_en": "Mathematics Final Exam",
  "state": "UPCOMING",
  "starts_at": "2024-06-01T09:00:00Z",
  "can_start_attempt": false,
  "message": "Exam will be available on June 1, 2024 at 9:00 AM"
}

Response when EXPIRED:
{
  "id": "uuid",
  "title_en": "Mathematics Final Exam",
  "state": "EXPIRED",
  "ends_at": "2024-06-01T12:00:00Z",
  "can_start_attempt": false,
  "message": "Exam deadline has passed"
}
```

---

## 5) UI FLOW SKETCH

### Admin UI Flow

#### A) Exam Creation Flow

**Step 1: Exams List Page** (`/admin/exams`)
```
┌─────────────────────────────────────────────────────────┐
│ Exams Management                    [+ Create Exam]     │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Title              │ Duration │ Dates    │ Actions │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ Math Final Exam    │ 120 min  │ Jun 1-1  │ [Edit]  │ │
│ │ 20 questions       │          │          │ [View]  │ │
│ │ 5 assignments      │          │          │ [Del]   │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ Physics Midterm    │ 90 min   │ Jun 5-5  │ [Edit]  │ │
│ │ 15 questions       │          │          │ [View]  │ │
│ │ 3 assignments      │          │          │ [Del]   │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ [Pagination: 1 2 3 ... 10]                              │
└─────────────────────────────────────────────────────────┘
```

**Step 2: Create Exam Form** (`/admin/exams/create`)
```
┌─────────────────────────────────────────────────────────┐
│ Create New Exam                                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Title (English): [_____________________________]        │
│ Title (Arabic):  [_____________________________]        │
│                                                          │
│ Duration (minutes): [___] minutes                       │
│                                                          │
│ Start Date/Time: [2024-06-01] [09:00]                  │
│ End Date/Time:   [2024-06-01] [12:00]                  │
│                                                          │
│ Max Attempts: [5]                                       │
│                                                          │
│ ☐ Globally Locked (students cannot access)             │
│                                                          │
│ [Create Exam] [Cancel]                                  │
└─────────────────────────────────────────────────────────┘
```

**Step 3: Exam Details & Question Builder** (`/admin/exams/{id}`)
```
┌─────────────────────────────────────────────────────────┐
│ Mathematics Final Exam                    [Edit] [Delete]│
├─────────────────────────────────────────────────────────┤
│ Duration: 120 minutes                                    │
│ Period: Jun 1, 2024 9:00 AM - 12:00 PM                 │
│ Max Attempts: 5                                          │
│ Status: ☐ Globally Locked                               │
│                                                          │
│ ┌─ Questions (20) ──────────────────────────────────┐  │
│ │                              [+ Add Question]      │  │
│ │ ┌──────────────────────────────────────────────┐  │  │
│ │ │ #1 │ What is 2+2?              │ 5 pts │ ↑↓ │  │  │
│ │ │    │ Type: MCQ, Difficulty: EASY│      │ ✎ ✕│  │  │
│ │ ├──────────────────────────────────────────────┤  │  │
│ │ │ #2 │ Solve: x² = 16            │ 10 pts│ ↑↓ │  │  │
│ │ │    │ Type: MCQ, Difficulty: MED │      │ ✎ ✕│  │  │
│ │ └──────────────────────────────────────────────┘  │  │
│ │ Total Points: 100                                 │  │
│ └───────────────────────────────────────────────────┘  │
│                                                          │
│ ┌─ Assignments (5) ─────────────────────────────────┐  │
│ │                              [+ Create Assignment] │  │
│ │ • School: Al-Noor (500 students)          [Remove]│  │
│ │ • Class: Grade 10-A (30 students)         [Remove]│  │
│ │ • Group: Advanced Math (15 students)      [Remove]│  │
│ └───────────────────────────────────────────────────┘  │
│                                                          │
│ ┌─ Overrides (3) ───────────────────────────────────┐  │
│ │                              [+ Add Override]      │  │
│ │ • Ahmed Ali - UNLOCK, Extended to Jun 2    [Edit] │  │
│ │ • Sara Hassan - LOCK                       [Edit] │  │
│ └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

**Step 4: Add Question to Exam Modal**
```
┌─────────────────────────────────────────────────────────┐
│ Add Question to Exam                            [✕]     │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Search Questions:                                        │
│ [Search by prompt, type, difficulty...]                 │
│                                                          │
│ Filter:                                                  │
│ Type: [All ▼] Difficulty: [All ▼] Lesson: [All ▼]     │
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ ☐ What is 2+2?                                     │ │
│ │   Type: MCQ, Difficulty: EASY, Lesson: Arithmetic │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ ☐ Solve: x² = 16                                   │ │
│ │   Type: MCQ, Difficulty: MEDIUM, Lesson: Algebra  │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ ☐ True or False: π ≈ 3.14                         │ │
│ │   Type: TF, Difficulty: EASY, Lesson: Constants   │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ Points per question: [5]                                │
│                                                          │
│ [Add Selected Questions] [Cancel]                       │
└─────────────────────────────────────────────────────────┘
```

**Step 5: Create Assignment Modal**
```
┌─────────────────────────────────────────────────────────┐
│ Assign Exam                                     [✕]     │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Assignment Type:                                         │
│ ○ Entire School                                         │
│ ○ Specific Class                                        │
│ ○ Specific Group                                        │
│ ● Specific Students                                     │
│                                                          │
│ Select School: [Al-Noor School ▼]                      │
│                                                          │
│ Select Students:                                         │
│ [Search students...]                                     │
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ ☑ Ahmed Ali Mohammed (ahmed_ali)                   │ │
│ │ ☑ Fatima Hassan Ibrahim (fatima_hassan)            │ │
│ │ ☐ Omar Khalid Ahmed (omar_khalid)                  │ │
│ │ ☐ Layla Mahmoud Ali (layla_mahmoud)                │ │
│ └────────────────────────────────────────────────────┘ │
│                                                          │
│ Selected: 2 students                                     │
│                                                          │
│ [Create Assignment] [Cancel]                            │
└─────────────────────────────────────────────────────────┘
```

**Step 6: Add Override Modal**
```
┌─────────────────────────────────────────────────────────┐
│ Add Student Override                            [✕]     │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Select Student:                                          │
│ [Search students...]                                     │
│ Selected: Ahmed Ali Mohammed                             │
│                                                          │
│ Lock Mode:                                               │
│ ○ DEFAULT (use exam's global lock setting)             │
│ ● UNLOCK (force unlock for this student)               │
│ ○ LOCK (force lock for this student)                   │
│                                                          │
│ Extended Deadline (optional):                            │
│ ☑ Extend deadline                                       │
│ New End Date/Time: [2024-06-02] [12:00]                │
│                                                          │
│ Reason (optional):                                       │
│ [Student has special accommodation...]                  │
│                                                          │
│ [Save Override] [Cancel]                                │
└─────────────────────────────────────────────────────────┘
```

---

#### B) Classes & Groups Management Flow

**Classes Management** (`/admin/schools/{id}/classes`)
```
┌─────────────────────────────────────────────────────────┐
│ Al-Noor School - Classes                [+ Create Class]│
├─────────────────────────────────────────────────────────┤
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Class Name       │ Students │ Actions              │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ Grade 10-A       │ 30       │ [View] [Edit] [Del]  │ │
│ │ الصف العاشر - أ  │          │                      │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ Grade 10-B       │ 28       │ [View] [Edit] [Del]  │ │
│ │ الصف العاشر - ب  │          │                      │ │
│ └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

**Groups Management** (`/admin/schools/{id}/groups`)
```
┌─────────────────────────────────────────────────────────┐
│ Al-Noor School - Groups                 [+ Create Group]│
├─────────────────────────────────────────────────────────┤
│                                                          │
│ ┌────────────────────────────────────────────────────┐ │
│ │ Group Name           │ Students │ Actions          │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ Advanced Math        │ 15       │ [View] [Edit]    │ │
│ │ مجموعة الرياضيات    │          │ [Del]            │ │
│ ├────────────────────────────────────────────────────┤ │
│ │ Science Club         │ 20       │ [View] [Edit]    │ │
│ │ نادي العلوم          │          │ [Del]            │ │
│ └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

---

### School UI Flow (Read-Only)

**School Dashboard** (`/school/dashboard`)
```
┌─────────────────────────────────────────────────────────┐
│ Al-Noor School Dashboard                                 │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ ┌─ Assigned Exams ──────────────────────────────────┐  │
│ │                                                     │  │
│ │ ┌─────────────────────────────────────────────┐   │  │
│ │ │ Mathematics Final Exam                      │   │  │
│ │ │ Duration: 120 min │ Jun 1, 9:00 AM - 12:00 PM│  │  │
│ │ │ Assigned: 500 students                      │   │  │
│ │ │ Completed: 350 attempts                     │   │  │
│ │ │                              [View Details] │   │  │
│ │ ├─────────────────────────────────────────────┤   │  │
│ │ │ Physics Midterm                             │   │  │
│ │ │ Duration: 90 min │ Jun 5, 10:00 AM - 12:00 PM│  │  │
│ │ │ Assigned: 250 students                      │   │  │
│ │ │ Completed: 180 attempts                     │   │  │
│ │ │                              [View Details] │   │  │
│ │ └─────────────────────────────────────────────┘   │  │
│ └─────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

**Exam Details (School View)** (`/school/exams/{id}`)
```
┌─────────────────────────────────────────────────────────┐
│ Mathematics Final Exam                                   │
├─────────────────────────────────────────────────────────┤
│ Duration: 120 minutes                                    │
│ Period: Jun 1, 2024 9:00 AM - 12:00 PM                 │
│ Questions: 20 │ Total Points: 100                       │
│                                                          │
│ ┌─ Student Progress ────────────────────────────────┐  │
│ │ [Search students...]                               │  │
│ │                                                     │  │
│ │ ┌───────────────────────────────────────────────┐ │  │
│ │ │ Student Name    │ Attempts │ Status           │ │  │
│ │ ├───────────────────────────────────────────────┤ │  │
│ │ │ Ahmed Ali       │ 2/5      │ ● Completed      │ │  │
│ │ │ Fatima Hassan   │ 1/5      │ ● Completed      │ │  │
│ │ │ Omar Khalid     │ 0/5      │ ○ Not Started    │ │  │
│ │ │ Layla Mahmoud   │ 3/5      │ ● Completed      │ │  │
│ │ └───────────────────────────────────────────────┘ │  │
│ │                                                     │  │
│ │ Total Assigned: 500 students                        │  │
│ │ Completed: 350 (70%)                                │  │
│ │ In Progress: 50 (10%)                               │  │
│ │ Not Started: 100 (20%)                              │  │
│ └─────────────────────────────────────────────────────┘  │
│                                                          │
│ Note: Scores are not visible to school users            │
└─────────────────────────────────────────────────────────┘
```

---

### Student UI Flow (Read-Only)

**Student Dashboard** (`/student/dashboard`)
```
┌─────────────────────────────────────────────────────────┐
│ Welcome, Ahmed Ali Mohammed                              │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ ┌─ My Exams ────────────────────────────────────────┐  │
│ │                                                     │  │
│ │ ┌─────────────────────────────────────────────┐   │  │
│ │ │ 🟢 AVAILABLE                                │   │  │
│ │ │ Mathematics Final Exam                      │   │  │
│ │ │ Duration: 120 minutes                       │   │  │
│ │ │ Available: Jun 1, 9:00 AM - 12:00 PM       │   │  │
│ │ │ Attempts: 2/5 used                          │   │  │
│ │ │ Questions: 20                               │   │  │
│ │ │                              [View Details] │   │  │
│ │ ├─────────────────────────────────────────────┤   │  │
│ │ │ 🔵 UPCOMING                                 │   │  │
│ │ │ Physics Midterm                             │   │  │
│ │ │ Duration: 90 minutes                        │   │  │
│ │ │ Starts: Jun 5, 10:00 AM                    │   │  │
│ │ │ Attempts: 0/5 used                          │   │  │
│ │ │                              [View Details] │   │  │
│ │ ├─────────────────────────────────────────────┤   │  │
│ │ │ 🔴 EXPIRED                                  │   │  │
│ │ │ Chemistry Quiz                              │   │  │
│ │ │ Ended: May 28, 12:00 PM                    │   │  │
│ │ │ Attempts: 1/3 used                          │   │  │
│ │ │                              [View Details] │   │  │
│ │ ├─────────────────────────────────────────────┤   │  │
│ │ │ 🔒 LOCKED                                   │   │  │
│ │ │ Biology Final                               │   │  │
│ │ │ This exam is currently locked               │   │  │
│ │ │                              [View Details] │   │  │
│ │ └─────────────────────────────────────────────┘   │  │
│ └─────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

**Exam Details (Student View - AVAILABLE)** (`/student/exams/{id}`)
```
┌─────────────────────────────────────────────────────────┐
│ Mathematics Final Exam                                   │
├─────────────────────────────────────────────────────────┤
│ Status: 🟢 AVAILABLE                                    │
│                                                          │
│ Duration: 120 minutes                                    │
│ Available: Jun 1, 2024 9:00 AM - 12:00 PM              │
│ Questions: 20                                            │
│ Total Points: 100                                        │
│                                                          │
│ Your Attempts: 2/5 used                                  │
│                                                          │
│ ┌─ Instructions ────────────────────────────────────┐  │
│ │ • You have 120 minutes to complete this exam      │  │
│ │ • You can take this exam up to 5 times           │  │
│ │ • Your best attempt will count                    │  │
│ │ • Once started, you must complete the exam        │  │
│ │ • Make sure you have stable internet connection   │  │
│ └───────────────────────────────────────────────────┘  │
│                                                          │
│ [Start Attempt] [Back to Dashboard]                     │
│                                                          │
│ Note: Scores and correct answers are not shown          │
└─────────────────────────────────────────────────────────┘
```

**Exam Details (Student View - UPCOMING)**
```
┌─────────────────────────────────────────────────────────┐
│ Physics Midterm                                          │
├─────────────────────────────────────────────────────────┤
│ Status: 🔵 UPCOMING                                     │
│                                                          │
│ This exam will be available on:                          │
│ June 5, 2024 at 10:00 AM                                │
│                                                          │
│ Duration: 90 minutes                                     │
│ Available until: Jun 5, 2024 12:00 PM                   │
│ Questions: 15                                            │
│ Max Attempts: 3                                          │
│                                                          │
│ [Back to Dashboard]                                      │
└─────────────────────────────────────────────────────────┘
```

**Exam Details (Student View - EXPIRED)**
```
┌─────────────────────────────────────────────────────────┐
│ Chemistry Quiz                                           │
├─────────────────────────────────────────────────────────┤
│ Status: 🔴 EXPIRED                                      │
│                                                          │
│ This exam ended on:                                      │
│ May 28, 2024 at 12:00 PM                                │
│                                                          │
│ Your Attempts: 1/3 used                                  │
│                                                          │
│ You can no longer take this exam.                        │
│                                                          │
│ [Back to Dashboard]                                      │
└─────────────────────────────────────────────────────────┘
```

**Exam Details (Student View - LOCKED)**
```
┌─────────────────────────────────────────────────────────┐
│ Biology Final                                            │
├─────────────────────────────────────────────────────────┤
│ Status: 🔒 LOCKED                                       │
│                                                          │
│ This exam is currently locked and cannot be accessed.    │
│                                                          │
│ Please contact your instructor for more information.     │
│                                                          │
│ [Back to Dashboard]                                      │
└─────────────────────────────────────────────────────────┘
```

---

## 6) IMPLEMENTATION NOTES

### Security Considerations

1. **Tenant Isolation**
   - All queries for classes, groups, students must filter by school_id
   - school_id derived from authenticated user ONLY
   - Never accept school_id from request parameters

2. **Data Privacy**
   - Students NEVER see correct answers
   - Students NEVER see scores or grades
   - School users NEVER see scores or grades
   - Only Admin can see correct answers and manage grading

3. **Authorization**
   - Admin: Full CRUD on all exam-related entities
   - School: Read-only access to assigned exams and student progress (no scores)
   - Student: Read-only access to assigned exams, can view own attempts (no scores)

4. **Validation**
   - Exam dates: ends_at > starts_at
   - Override dates: override_ends_at > exam.starts_at
   - Points: Must be positive numbers
   - Max attempts: Must be positive integer
   - Question order: Must be unique within exam

### Performance Considerations

1. **Indexes**
   - All foreign keys should have indexes
   - Composite indexes for common queries (exam_id + student_id)
   - Index on timestamps for date range queries

2. **Caching**
   - Cache exam state resolution for 5 minutes
   - Cache assignment resolution for 10 minutes
   - Invalidate cache on override changes

3. **Query Optimization**
   - Use eager loading for relationships
   - Paginate large result sets
   - Use database transactions for multi-table operations

### Business Rules Summary

1. **Exam Creation**
   - Admin creates exam with metadata
   - Questions added from central question bank
   - Points assigned per question
   - Total points calculated automatically

2. **Assignment**
   - Exams assigned to schools, classes, groups, or students
   - Multiple assignment types can coexist
   - Student sees exam if ANY assignment matches

3. **Overrides**
   - Per-student overrides for lock status and deadline
   - Overrides take precedence over global settings
   - Only one override per student per exam

4. **State Resolution**
   - Priority: LOCKED > UPCOMING > EXPIRED > AVAILABLE
   - Considers global settings and per-student overrides
   - Calculated server-side, never client-side

5. **Attempts**
   - Student can start attempt only if state = AVAILABLE
   - Must have attempts remaining (< max_attempts)
   - Cannot have in-progress attempt
   - Best attempt counts (highest percentage)

---

## APPROVAL CHECKPOINT

This completes the **Sprint 2 – Phase 1: Architecture & Design** document.

**What has been delivered:**
✅ Module breakdown (4 modules)
✅ Database tables specification (9 new tables)
✅ State machine and decision rules (bulletproof algorithm)
✅ API contract sketch (30+ endpoints with request/response)
✅ UI flow sketch (Admin, School, Student views)

**What has NOT been done (as requested):**
❌ No code written
❌ No files created/modified
❌ No migrations written
❌ No controllers implemented
❌ No views created

**Next Phase:**
Sprint 2 – Phase 2 will include:
- Database migrations for all 9 tables
- Eloquent models with relationships
- Controllers for all endpoints
- Blade views for all UI flows
- Middleware updates
- Route definitions
- Seeders for test data

---

## QUESTION FOR APPROVAL

**Do you approve moving to Sprint 2 – Phase 2 (Database migrations + code implementation)?**

Please confirm before I proceed with writing any code.
