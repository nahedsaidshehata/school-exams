# SPRINT 3 – PHASE 1: ATTEMPTS ARCHITECTURE & RULES (DESIGN ONLY)

**Status:** DESIGN PHASE - NO CODE IMPLEMENTATION
**Date:** 2024-01-02
**Author:** BLACKBOXAI Senior Laravel Engineer

---

## OVERVIEW

Sprint 3 implements the Attempt Engine allowing students to:
- Start exam attempts
- Save answers with autosave
- Maintain active session with heartbeat
- Reset all answers (increments version)
- Submit attempts for grading

**Key Principles:**
- Single active session per attempt
- Tenant isolation (school_id from auth)
- Students NEVER see scores or correct answers
- Consistent with Sprint 1 & Sprint 2 architecture

---

## 1. DATABASE SCHEMA

### A) exam_attempts Table

```sql
CREATE TABLE exam_attempts (
    id UUID PRIMARY KEY,
    school_id UUID NOT NULL,
    student_id UUID NOT NULL,
    exam_id UUID NOT NULL,
    attempt_number INT NOT NULL,
    status ENUM('IN_PROGRESS', 'SUBMITTED', 'PENDING_MANUAL', 'GRADED') NOT NULL DEFAULT 'IN_PROGRESS',
    reset_version INT NOT NULL DEFAULT 0,
    active_session_token VARCHAR(255) NULL,
    last_heartbeat DATETIME NULL,
    started_at DATETIME NOT NULL,
    submitted_at DATETIME NULL,
    max_possible_score DECIMAL(8,2) NOT NULL,
    raw_score DECIMAL(8,2) NULL,
    percentage DECIMAL(5,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_attempt (student_id, exam_id, attempt_number),
    INDEX idx_student_exam (student_id, exam_id),
    INDEX idx_status (status),
    INDEX idx_session (active_session_token),
    INDEX idx_heartbeat (last_heartbeat)
);
```

**Field Descriptions:**
- `id`: UUID primary key
- `school_id`: Tenant isolation (from auth()->user()->school_id)
- `student_id`: Student who owns this attempt
- `exam_id`: Reference to exam
- `attempt_number`: Sequential number (1, 2, 3...) per student per exam
- `status`: Current attempt state
  - `IN_PROGRESS`: Student is taking the exam
  - `SUBMITTED`: Student submitted, awaiting grading
  - `PENDING_MANUAL`: Auto-graded, has essays needing manual grading
  - `GRADED`: Fully graded (student still sees no score)
- `reset_version`: Increments each time student resets all answers
- `active_session_token`: Token for single active session enforcement
- `last_heartbeat`: Last heartbeat timestamp (5-minute timeout)
- `started_at`: When attempt started (for duration calculation)
- `submitted_at`: When attempt was submitted
- `max_possible_score`: Cached sum of all question points
- `raw_score`: Total points earned (NULL until graded)
- `percentage`: Score as percentage 0-100 (NULL until graded)

**Constraints:**
- UNIQUE(student_id, exam_id, attempt_number) - No duplicate attempts
- school_id must match student's school_id (enforced in application)

---

### B) attempt_answers Table

```sql
CREATE TABLE attempt_answers (
    id UUID PRIMARY KEY,
    attempt_id UUID NOT NULL,
    question_id UUID NOT NULL,
    reset_version INT NOT NULL DEFAULT 0,
    student_response JSON NOT NULL,
    points_awarded DECIMAL(8,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_answer (attempt_id, question_id, reset_version),
    INDEX idx_attempt (attempt_id),
    INDEX idx_reset_version (reset_version)
);
```

**Field Descriptions:**
- `id`: UUID primary key
- `attempt_id`: Reference to exam attempt
- `question_id`: Reference to question
- `reset_version`: Version number (increments on reset)
- `student_response`: JSON containing student's answer
  - MCQ: `{"selected_option_id": "uuid"}`
  - TF: `{"selected_option_id": "uuid"}`
  - ESSAY: `{"text": "student essay text"}`
- `points_awarded`: Points earned (NULL until graded)

**Constraints:**
- UNIQUE(attempt_id, question_id, reset_version) - One answer per question per version
- Last-write-wins: UPDATE if exists, INSERT if not

**Reset Behavior:**
- When reset_version increments, old answers remain in DB
- UI only shows answers matching current reset_version
- Grading only considers answers matching final reset_version

---

### C) answer_time_logs Table

```sql
CREATE TABLE answer_time_logs (
    id UUID PRIMARY KEY,
    attempt_id UUID NOT NULL,
    question_id UUID NOT NULL,
    reset_version INT NOT NULL DEFAULT 0,
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    duration_seconds INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    
    INDEX idx_attempt (attempt_id),
    INDEX idx_question (question_id),
    INDEX idx_reset_version (reset_version),
    INDEX idx_duration (duration_seconds)
);
```

**Field Descriptions:**
- `id`: UUID primary key
- `attempt_id`: Reference to exam attempt
- `question_id`: Reference to question
- `reset_version`: Version number
- `start_time`: When student started viewing/answering question
- `end_time`: When student moved away or submitted
- `duration_seconds`: Calculated duration (for analytics)

**Purpose:**
- Track time spent per question
- Analytics for admin/school
- NOT used for grading
- Multiple logs per question allowed (student can revisit)

---

## 2. BUSINESS RULES & STATE MACHINES

### A) Start Attempt Validation

**Preconditions (ALL must be true):**

1. **Assignment Check:**
   - Exam must be assigned to student (SCHOOL or STUDENT assignment)
   - Use existing assignment logic from Sprint 2

2. **State Check:**
   - Exam state must be `AVAILABLE` (use ExamStateResolver)
   - Respects overrides (lock mode, deadline)
   - NOT LOCKED, NOT UPCOMING, NOT EXPIRED

3. **Attempt Limit Check:**
   - Count existing attempts: `SELECT COUNT(*) FROM exam_attempts WHERE student_id = ? AND exam_id = ?`
   - Must be < exam.max_attempts
   - Example: If max_attempts = 5, student can have attempts 1-5

4. **No Active Attempt:**
   - No existing attempt with status = 'IN_PROGRESS'
   - Student must submit or abandon previous attempt first

**On Success:**
- Create new exam_attempt record
- Set attempt_number = (max existing attempt_number + 1)
- Set status = 'IN_PROGRESS'
- Generate unique active_session_token (UUID)
- Set started_at = NOW()
- Calculate max_possible_score = SUM(exam_questions.points)
- Return session_token to client

**On Failure:**
- Return appropriate error:
  - 403: Not assigned
  - 423: Exam locked/expired/upcoming
  - 429: Max attempts exceeded
  - 409: Active attempt exists

---

### B) Single Active Session

**Purpose:**
- Prevent multiple browser tabs/devices from interfering
- Allow session takeover after timeout

**Mechanism:**

1. **Session Token:**
   - Generated on start attempt
   - Stored in `active_session_token` field
   - Required for all operations (autosave, heartbeat, reset, submit)

2. **Heartbeat:**
   - Client sends heartbeat every 60 seconds
   - Updates `last_heartbeat` timestamp
   - Validates session_token matches

3. **Timeout:**
   - If last_heartbeat > 5 minutes ago, session is stale
   - New session can takeover (update active_session_token)
   - Old session receives 409 Conflict on next request

4. **Validation:**
   ```
   IF active_session_token != request_token THEN
       IF last_heartbeat < NOW() - 5 minutes THEN
           Allow takeover (update token)
       ELSE
           Return 409 Conflict
       END IF
   END IF
   ```

---

### C) Autosave

**Trigger:**
- Client sends PATCH request every 30 seconds (or on answer change)
- Includes question_id, student_response, current reset_version

**Validation:**
1. Attempt exists and belongs to student (tenant check)
2. Attempt status = 'IN_PROGRESS'
3. Session token valid (not stale)
4. Question belongs to exam

**Behavior:**
- **Last-Write-Wins:** UPSERT into attempt_answers
  ```sql
  INSERT INTO attempt_answers (attempt_id, question_id, reset_version, student_response)
  VALUES (?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
      student_response = VALUES(student_response),
      updated_at = NOW()
  ```
- Update last_heartbeat timestamp
- Return 200 OK (no sensitive data)

**Concurrency:**
- Database UNIQUE constraint prevents race conditions
- Last write wins (acceptable for autosave)

---

### D) Reset All Answers

**Purpose:**
- Student wants to clear all answers and start fresh
- Does NOT reset timer (started_at remains unchanged)

**Behavior:**
1. Validate attempt ownership and status = 'IN_PROGRESS'
2. Validate session token
3. Increment reset_version:
   ```sql
   UPDATE exam_attempts
   SET reset_version = reset_version + 1
   WHERE id = ?
   ```
4. Old answers remain in DB (for audit)
5. UI ignores answers with old reset_version
6. Return new reset_version to client

**Important:**
- Timer continues (started_at + exam.duration_minutes)
- Student loses all previous answers
- Cannot undo reset

---

### E) Submit Attempt

**Validation:**
1. Attempt exists and belongs to student
2. Attempt status = 'IN_PROGRESS'
3. Session token valid
4. Within time limit (NOW() <= started_at + exam.duration_minutes)
   - If override exists, use override_ends_at
   - If expired, return 423 Locked

**Behavior:**

1. **Update Attempt:**
   ```sql
   UPDATE exam_attempts
   SET status = 'SUBMITTED',
       submitted_at = NOW(),
       active_session_token = NULL
   WHERE id = ?
   ```

2. **Auto-Grade Objective Questions:**
   - For each MCQ/TF question:
     - Get student's answer (matching current reset_version)
     - Compare selected_option_id with correct option (is_correct = true)
     - If match: points_awarded = question.points
     - If no match: points_awarded = 0
     - Update attempt_answers.points_awarded

3. **Determine Final Status:**
   ```
   IF exam has any ESSAY questions THEN
       status = 'PENDING_MANUAL'
   ELSE
       status = 'GRADED'
       Calculate raw_score = SUM(points_awarded)
       Calculate percentage = (raw_score / max_possible_score) * 100
   END IF
   ```

4. **Return Response:**
   - 200 OK
   - Message: "Exam submitted successfully"
   - **DO NOT return score, points, or correct answers**

**Important:**
- Student sees confirmation but NO score
- Scores only visible to admin
- Session token cleared (attempt locked)

---

### F) Manual Grading (Admin Only)

**Purpose:**
- Admin grades ESSAY questions
- Updates attempt status to GRADED

**Validation:**
1. Admin role required
2. Attempt status = 'PENDING_MANUAL'
3. All ESSAY questions have points_awarded

**Behavior:**
1. Admin views attempt with all answers
2. Admin assigns points to each ESSAY (0 to question.points)
3. Update attempt_answers.points_awarded for essays
4. Calculate totals:
   ```sql
   UPDATE exam_attempts
   SET raw_score = (SELECT SUM(points_awarded) FROM attempt_answers WHERE attempt_id = ?),
       percentage = (raw_score / max_possible_score) * 100,
       status = 'GRADED'
   WHERE id = ?
   ```

---

## 3. API CONTRACTS (DESIGN ONLY)

### Student Endpoints

#### POST /student/exams/{exam}/start

**Purpose:** Start a new exam attempt

**Request:**
```json
{
  // No body required
}
```

**Response (200 OK):**
```json
{
  "attempt_id": "uuid",
  "session_token": "uuid",
  "attempt_number": 1,
  "started_at": "2024-01-02T10:00:00Z",
  "expires_at": "2024-01-02T11:00:00Z",
  "duration_minutes": 60,
  "reset_version": 0,
  "questions": [
    {
      "id": "uuid",
      "order_index": 1,
      "type": "MCQ",
      "difficulty": "MEDIUM",
      "prompt_en": "Question text",
      "prompt_ar": "نص السؤال",
      "options": [
        {
          "id": "uuid",
          "content_en": "Option A",
          "content_ar": "الخيار أ",
          "order_index": 1
        }
      ]
    }
  ]
}
```

**SECURITY:** 
- NO points per question
- NO is_correct field
- NO max_possible_score

**Errors:**
- 403: Not assigned
- 423: Exam locked/expired/upcoming
- 429: Max attempts exceeded
- 409: Active attempt exists

---

#### POST /student/attempts/{attempt}/heartbeat

**Purpose:** Keep session alive

**Request:**
```json
{
  "session_token": "uuid"
}
```

**Response (200 OK):**
```json
{
  "status": "active",
  "time_remaining_seconds": 3000
}
```

**Errors:**
- 409: Session conflict (another session active)
- 423: Attempt expired

---

#### PATCH /student/attempts/{attempt}/save

**Purpose:** Autosave answer

**Request:**
```json
{
  "session_token": "uuid",
  "question_id": "uuid",
  "reset_version": 0,
  "student_response": {
    "selected_option_id": "uuid"  // MCQ/TF
    // OR
    "text": "Essay answer"  // ESSAY
  }
}
```

**Response (200 OK):**
```json
{
  "saved": true,
  "timestamp": "2024-01-02T10:15:00Z"
}
```

**SECURITY:**
- NO points_awarded returned
- NO correct answer indication

**Errors:**
- 409: Session conflict
- 423: Attempt expired or submitted
- 422: Invalid question_id or reset_version

---

#### POST /student/attempts/{attempt}/reset

**Purpose:** Reset all answers

**Request:**
```json
{
  "session_token": "uuid"
}
```

**Response (200 OK):**
```json
{
  "reset_version": 1,
  "message": "All answers have been cleared"
}
```

**Errors:**
- 409: Session conflict
- 423: Attempt expired or submitted

---

#### POST /student/attempts/{attempt}/submit

**Purpose:** Submit attempt for grading

**Request:**
```json
{
  "session_token": "uuid"
}
```

**Response (200 OK):**
```json
{
  "submitted": true,
  "submitted_at": "2024-01-02T10:45:00Z",
  "message": "Exam submitted successfully. Results will be available after grading."
}
```

**SECURITY:**
- NO score returned
- NO points returned
- NO correct answers returned

**Errors:**
- 409: Session conflict
- 423: Time expired
- 422: Already submitted

---

### Admin Endpoint

#### PATCH /admin/attempts/{attempt}/grade-essay

**Purpose:** Grade essay questions

**Request:**
```json
{
  "grades": [
    {
      "question_id": "uuid",
      "points_awarded": 8.5
    }
  ]
}
```

**Response (200 OK):**
```json
{
  "attempt_id": "uuid",
  "status": "GRADED",
  "raw_score": 85.5,
  "percentage": 85.5,
  "max_possible_score": 100
}
```

**Validation:**
- All essay questions must be graded
- points_awarded <= question.points
- points_awarded >= 0

---

## 4. SECURITY CHECKLIST

### ✅ Tenant Isolation

**Rule:** school_id ALWAYS derived from auth()->user()->school_id

**Enforcement Points:**
1. **Start Attempt:**
   ```php
   $schoolId = auth()->user()->school_id;
   ExamAttempt::create([
       'school_id' => $schoolId,  // From auth, never from request
       'student_id' => auth()->id(),
       // ...
   ]);
   ```

2. **All Operations:**
   ```php
   $attempt = ExamAttempt::where('id', $attemptId)
       ->where('student_id', auth()->id())
       ->where('school_id', auth()->user()->school_id)
       ->firstOrFail();
   ```

3. **Admin Grading:**
   - Admin can grade any attempt (system-level)
   - But still validates attempt exists

---

### ✅ Attempt Ownership

**Rule:** Student can ONLY access their own attempts

**Validation:**
```php
$attempt = ExamAttempt::where('id', $attemptId)
    ->where('student_id', auth()->id())
    ->where('school_id', auth()->user()->school_id)
    ->firstOrFail();
```

**Errors:**
- 404: Attempt not found (don't reveal existence)
- 403: Forbidden (if revealing existence is acceptable)

---

### ✅ Prevent Points/Correct Answers Exposure

**Student Responses NEVER Include:**
- `points_awarded` field
- `is_correct` field from options
- `raw_score` from attempt
- `percentage` from attempt
- `max_possible_score` from attempt

**Query Protection:**
```php
// When loading questions for student
$questions = Question::select('id', 'type', 'difficulty', 'prompt_en', 'prompt_ar')
    ->with(['options' => function($q) {
        $q->select('id', 'question_id', 'content_en', 'content_ar', 'order_index');
        // NEVER select is_correct
    }])
    ->get();

// When loading attempt for student
$attempt = ExamAttempt::select('id', 'attempt_number', 'status', 'started_at', 'submitted_at', 'reset_version')
    // NEVER select raw_score, percentage, max_possible_score
    ->find($attemptId);
```

---

### ✅ Concurrency Handling

**Scenario:** Multiple requests from same student

**Solution:**

1. **Session Token Validation:**
   ```php
   if ($attempt->active_session_token !== $requestToken) {
       if ($attempt->last_heartbeat < now()->subMinutes(5)) {
           // Takeover allowed
           $attempt->update(['active_session_token' => $requestToken]);
       } else {
           // Conflict
           return response()->json(['error' => 'Another session is active'], 409);
       }
   }
   ```

2. **Database Constraints:**
   - UNIQUE(attempt_id, question_id, reset_version) prevents duplicate answers
   - Last-write-wins for autosave (acceptable)

3. **Optimistic Locking:**
   - Use reset_version to detect conflicts
   - If client's reset_version != server's, reject save

---

### ✅ Locked/Expired Handling

**Validation on Every Operation:**

```php
// Check exam state
$state = $stateResolver->resolveState($exam, $student);
if ($state !== 'AVAILABLE') {
    return response()->json(['error' => 'Exam is not available'], 423);
}

// Check time limit
$expiresAt = $attempt->started_at->addMinutes($exam->duration_minutes);
if (now()->gt($expiresAt)) {
    // Auto-submit if not already submitted
    if ($attempt->status === 'IN_PROGRESS') {
        $this->autoSubmitAttempt($attempt);
    }
    return response()->json(['error' => 'Time expired'], 423);
}
```

**HTTP Status Codes:**
- 423 Locked: Exam locked, expired, or time limit exceeded
- 409 Conflict: Session conflict or concurrent modification
- 403 Forbidden: Not assigned or no permission
- 429 Too Many Requests: Max attempts exceeded

---

## 5. STATE MACHINE DIAGRAM

```
┌─────────────┐
│   START     │
│  (Student   │
│   clicks    │
│   Start)    │
└──────┬──────┘
       │
       │ Validate: assigned, AVAILABLE, < max_attempts
       │
       ▼
┌─────────────────┐
│  IN_PROGRESS    │◄──────┐
│                 │       │
│ - Autosave      │       │ Reset All
│ - Heartbeat     │       │ (increment reset_version)
│ - Reset         │───────┘
│ - Submit        │
└────────┬────────┘
         │
         │ Submit
         │
         ▼
┌─────────────────┐
│   SUBMITTED     │
│                 │
│ Auto-grade      │
│ MCQ/TF          │
└────────┬────────┘
         │
         │ Has ESSAY?
         │
    ┌────┴────┐
    │         │
    │ YES     │ NO
    │         │
    ▼         ▼
┌──────────┐  ┌──────────┐
│ PENDING_ │  │  GRADED  │
│ MANUAL   │  │          │
│          │  │ (Student │
│ (Admin   │  │  still   │
│  grades  │  │  sees no │
│  essays) │  │  score)  │
└────┬─────┘  └──────────┘
     │
     │ Admin grades all essays
     │
     ▼
┌──────────┐
│  GRADED  │
│          │
│ (Student │
│  still   │
│  sees no │
│  score)  │
└──────────┘
```

---

## 6. TIMER & DURATION LOGIC

### Global Timer

**Start:** `started_at` timestamp set when attempt created

**Duration:** `exam.duration_minutes` (e.g., 60 minutes)

**Expiration:** `started_at + duration_minutes`

**Override:** If student has override with `override_ends_at`, use that instead

**Calculation:**
```php
$expiresAt = $attempt->started_at->addMinutes($exam->duration_minutes);

// Check for override
$override = ExamOverride::where('exam_id', $exam->id)
    ->where('student_id', $student->id)
    ->where('school_id', $student->school_id)
    ->first();

if ($override && $override->override_ends_at) {
    $expiresAt = $override->override_ends_at;
}

$timeRemaining = now()->diffInSeconds($expiresAt, false);
if ($timeRemaining <= 0) {
    // Time expired
}
```

### Reset Behavior

**Important:** Reset does NOT reset timer

- `started_at` remains unchanged
- `reset_version` increments
- Timer continues counting down
- Student loses answers but not time

---

## 7. GRADING LOGIC

### Auto-Grading (MCQ/TF)

**On Submit:**

```php
foreach ($exam->examQuestions as $examQuestion) {
    if ($examQuestion->question->type === 'ESSAY') {
        continue; // Skip essays
    }
    
    // Get student's answer
    $answer = AttemptAnswer::where('attempt_id', $attempt->id)
        ->where('question_id', $examQuestion->question_id)
        ->where('reset_version', $attempt->reset_version)
        ->first();
    
    if (!$answer) {
        // No answer = 0 points
        AttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $examQuestion->question_id,
            'reset_version' => $attempt->reset_version,
            'student_response' => json_encode(['selected_option_id' => null]),
            'points_awarded' => 0
        ]);
        continue;
    }
    
    // Get correct option
    $correctOption = QuestionOption::where('question_id', $examQuestion->question_id)
        ->where('is_correct', true)
        ->first();
    
    // Compare
    $selectedOptionId = $answer->student_response['selected_option_id'] ?? null;
    
    if ($selectedOptionId === $correctOption->id) {
        $answer->points_awarded = $examQuestion->points;
    } else {
        $answer->points_awarded = 0;
    }
    
    $answer->save();
}
```

### Manual Grading (ESSAY)

**Admin Interface:**
1. View attempt with all answers
2. For each ESSAY question:
   - Show student's text response
   - Show max points available
   - Input field for points_awarded (0 to max)
3. Submit grades
4. Calculate totals and update attempt

---

## 8. CONSISTENCY WITH EXISTING ARCHITECTURE

### Sprint 1 Consistency

✅ **UUID Primary Keys:** All new tables use UUID
✅ **Tenant Isolation:** school_id required, derived from auth
✅ **Role-Based Access:** Student/Admin endpoints separated
✅ **Session Auth:** Uses existing session-based authentication
✅ **CSRF Protection:** All POST/PATCH/DELETE require CSRF token

### Sprint 2 Consistency

✅ **Assignments:** Uses existing exam_assignments table
✅ **Overrides:** Uses existing exam_overrides table
✅ **State Resolver:** Uses existing ExamStateResolver service
✅ **Data Privacy:** Students never see points/correct answers
✅ **Middleware:** Uses existing RoleMiddleware and TenantMiddleware

### No Class/Group Assignments

✅ **Deferred:** Class/group functionality NOT implemented
✅ **Assignments:** Only SCHOOL and STUDENT types supported
✅ **Future-Proof:** Schema allows for future class/group addition

---

## 9. EDGE CASES & ERROR HANDLING

### Edge Case 1: Time Expires During Attempt

**Scenario:** Student is taking exam, time runs out

**Handling:**
- Next autosave/heartbeat detects expiration
- Auto-submit attempt with current answers
- Return 423 Locked with message
- Client shows "Time expired, exam auto-submitted"

### Edge Case 2: Session Takeover

**Scenario:** Student opens exam in two browsers

**Handling:**
- Browser A has session token X
- Browser B starts, gets session token Y
- Browser A's next request fails with 409 Conflict
- Browser A shows "Exam opened in another window"

### Edge Case 3: Reset During Autosave

**Scenario:** Autosave in progress, student clicks reset

**Handling:**
- Reset increments reset_version
- Autosave completes with old reset_version
- Next autosave uses new reset_version
- Old answer ignored by UI

### Edge Case 4: Submit Without Answering

**Scenario:** Student submits with no answers

**Handling:**
- Allowed (student choice)
- Auto-grading assigns 0 points to unanswered questions
- Creates attempt_answer records with points_awarded = 0

### Edge Case 5: Network Failure During Submit

**Scenario:** Submit request fails due to network

**Handling:**
- Client retries submit
- Server checks if already submitted (idempotent)
- If status = 'SUBMITTED', return success
- If status = 'IN_PROGRESS', process submit

---

## 10. PERFORMANCE CONSIDERATIONS

### Database Indexes

**Critical Indexes:**
- `exam_attempts(student_id, exam_id)` - Fast attempt lookup
- `exam_attempts(active_session_token)` - Session validation
- `exam_attempts(last_heartbeat)` - Stale session cleanup
- `attempt_answers(attempt_id, reset_version)` - Answer retrieval
- `answer_time_logs(attempt_id)` - Time aggregation

### Query Optimization

**Avoid N+1:**
- Eager load questions with options when starting attempt
- Eager load answers when loading attempt
- Use `withCount()` for attempt counts

**Caching:**
- Cache exam questions (rarely change)
- Cache max_possible_score (calculated once)
- Don't cache attempt data (changes frequently)

### Cleanup Jobs

**Stale Sessions:**
- Cron job to clear sessions with last_heartbeat > 1 hour
- Auto-submit attempts with expired time

**Old Attempts:**
- Archive attempts older than 1 year
- Keep for audit/compliance

---

## 11. TESTING STRATEGY (FOR PHASE 2)

### Unit Tests

- ExamAttempt model methods
- AttemptAnswer model methods
- Auto-grading logic
- Session validation logic
- Timer calculation logic

### Feature Tests

- Start attempt (success/failure scenarios)
- Autosave (concurrency, validation)
- Heartbeat (timeout, takeover)
- Reset (version increment)
- Submit (auto-grade, status transitions)
- Manual grading (admin only)

### Integration Tests

- Full attempt flow (start → answer → submit → grade)
- Session takeover scenario
- Time expiration scenario
- Multi-attempt scenario

---

## 12. MIGRATION ORDER

**Phase 2 Implementation Order:**

1. Create migrations (exam_attempts, attempt_answers, answer_time_logs)
2. Create models (ExamAttempt, AttemptAnswer, AnswerTimeLog)
3. Create AttemptService (business logic)
4. Create Student/AttemptController (endpoints)
5. Create Admin/AttemptGradingController (manual grading)
6. Create Blade views (attempt UI)
7. Add routes
8. Write tests
9. Manual testing

---

## APPROVAL CHECKPOINT

**This is a DESIGN DOCUMENT ONLY. No code has been written.**

**Deliverables Completed:**
✅ Database schema (3 tables with constraints)
✅ Business rules & state machines
✅ API contracts (request/response shapes)
✅ Security checklist
✅ Consistency with Sprint 1 & 2
✅ Edge cases & error handling
✅ Performance considerations

**Ready for Review:**
- Database design
- Business logic
- API design
- Security approach

---

## QUESTION FOR APPROVAL

**Do you approve moving to Sprint 3 Phase 2 (code implementation)?**

Please review:
1. Database schema (tables, fields, constraints)
2. Business rules (start, autosave, reset, submit, grade)
3. API contracts (endpoints, request/response)
4. Security measures (tenant isolation, data privacy)
5. Consistency with existing architecture

If approved, Phase 2 will implement:
- Migrations
- Models
- Controllers
- Services
- Views
- Routes
