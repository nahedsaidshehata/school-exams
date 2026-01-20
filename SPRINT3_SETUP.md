# SPRINT 3 - PHASE 2: SETUP & COMMANDS

## OVERVIEW

Sprint 3 implements the Attempt Engine for the Multi-School Exams Platform. This includes:
- Starting exam attempts
- Autosaving answers with heartbeat
- Resetting all answers
- Submitting attempts for grading
- Manual grading of essay questions by admin

---

## FILES CREATED/MODIFIED

### Database Migrations (3 files)
1. `database/migrations/2024_01_03_000000_create_exam_attempts_table.php`
2. `database/migrations/2024_01_03_000001_create_attempt_answers_table.php`
3. `database/migrations/2024_01_03_000002_create_answer_time_logs_table.php`

### Models (3 files)
1. `app/Models/ExamAttempt.php`
2. `app/Models/AttemptAnswer.php`
3. `app/Models/AnswerTimeLog.php`

### Services (1 file)
1. `app/Services/AttemptService.php`

### Controllers (2 files)
1. `app/Http/Controllers/Student/AttemptController.php`
2. `app/Http/Controllers/Admin/AttemptGradingController.php`

### Routes (1 file modified)
1. `routes/web.php` - Added 7 new routes

### Documentation (2 files)
1. `SPRINT3_PHASE1_DESIGN.md` - Complete design document
2. `SPRINT3_PHASE2_REMAINING_FILES.md` - Controller code reference
3. `SPRINT3_SETUP.md` - This file

---

## ARTISAN COMMANDS

### 1. Run Migrations

```bash
php artisan migrate
```

This will create the three new tables:
- `exam_attempts`
- `attempt_answers`
- `answer_time_logs`

### 2. Clear Cache (Optional)

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

---

## API ENDPOINTS

### Student Endpoints

#### 1. Start Exam Attempt
```
POST /student/exams/{exam}/start
```

**Response:**
```json
{
  "attempt_id": "uuid",
  "session_token": "uuid",
  "attempt_number": 1,
  "started_at": "2024-01-03T10:00:00Z",
  "expires_at": "2024-01-03T11:00:00Z",
  "duration_minutes": 60,
  "reset_version": 0,
  "questions": [...]
}
```

#### 2. Send Heartbeat
```
POST /student/attempts/{attempt}/heartbeat
Content-Type: application/json

{
  "session_token": "uuid"
}
```

**Response:**
```json
{
  "status": "active",
  "time_remaining_seconds": 3000
}
```

#### 3. Autosave Answer
```
PATCH /student/attempts/{attempt}/save
Content-Type: application/json

{
  "session_token": "uuid",
  "question_id": "uuid",
  "reset_version": 0,
  "student_response": {
    "selected_option_id": "uuid"
  },
  "focus_log": {
    "start_time": "2024-01-03T10:05:00Z",
    "end_time": "2024-01-03T10:06:00Z",
    "duration_seconds": 60
  }
}
```

**Response:**
```json
{
  "saved": true,
  "timestamp": "2024-01-03T10:06:00Z"
}
```

#### 4. Reset All Answers
```
POST /student/attempts/{attempt}/reset
Content-Type: application/json

{
  "session_token": "uuid"
}
```

**Response:**
```json
{
  "reset_version": 1,
  "message": "All answers have been cleared"
}
```

#### 5. Submit Attempt
```
POST /student/attempts/{attempt}/submit
Content-Type: application/json

{
  "session_token": "uuid"
}
```

**Response:**
```json
{
  "submitted": true,
  "submitted_at": "2024-01-03T10:45:00Z",
  "message": "Exam submitted successfully. Results will be available after grading."
}
```

### Admin Endpoints

#### 6. View Attempt Details
```
GET /admin/attempts/{attempt}
```

**Response:**
```json
{
  "attempt": {
    "id": "uuid",
    "attempt_number": 1,
    "status": "PENDING_MANUAL",
    "started_at": "2024-01-03T10:00:00Z",
    "submitted_at": "2024-01-03T10:45:00Z",
    "max_possible_score": 100,
    "raw_score": null,
    "percentage": null,
    "student": {...},
    "exam": {...},
    "answers": [...]
  }
}
```

#### 7. Grade Essay Questions
```
PATCH /admin/attempts/{attempt}/grade-essay
Content-Type: application/json

{
  "grades": [
    {
      "question_id": "uuid",
      "points_awarded": 8.5
    }
  ]
}
```

**Response:**
```json
{
  "attempt_id": "uuid",
  "status": "GRADED",
  "raw_score": 85.5,
  "percentage": 85.5,
  "max_possible_score": 100
}
```

---

## TESTING MANUALLY

### Test Flow 1: Student Takes Exam

1. **Login as Student**
   ```bash
   # Use existing student credentials from Sprint 1 seeder
   ```

2. **Start Exam Attempt**
   ```bash
   curl -X POST http://localhost:8000/student/exams/{exam-uuid}/start \
     -H "Cookie: laravel_session=..." \
     -H "X-CSRF-TOKEN: ..."
   ```

3. **Send Heartbeat (every 60 seconds)**
   ```bash
   curl -X POST http://localhost:8000/student/attempts/{attempt-uuid}/heartbeat \
     -H "Content-Type: application/json" \
     -H "Cookie: laravel_session=..." \
     -H "X-CSRF-TOKEN: ..." \
     -d '{"session_token": "uuid"}'
   ```

4. **Save Answer**
   ```bash
   curl -X PATCH http://localhost:8000/student/attempts/{attempt-uuid}/save \
     -H "Content-Type: application/json" \
     -H "Cookie: laravel_session=..." \
     -H "X-CSRF-TOKEN: ..." \
     -d '{
       "session_token": "uuid",
       "question_id": "uuid",
       "reset_version": 0,
       "student_response": {"selected_option_id": "uuid"}
     }'
   ```

5. **Submit Attempt**
   ```bash
   curl -X POST http://localhost:8000/student/attempts/{attempt-uuid}/submit \
     -H "Content-Type: application/json" \
     -H "Cookie: laravel_session=..." \
     -H "X-CSRF-TOKEN: ..." \
     -d '{"session_token": "uuid"}'
   ```

### Test Flow 2: Admin Grades Essay

1. **Login as Admin**
   ```bash
   # Use admin credentials from Sprint 1 seeder
   ```

2. **View Attempt**
   ```bash
   curl -X GET http://localhost:8000/admin/attempts/{attempt-uuid} \
     -H "Cookie: laravel_session=..." \
     -H "X-CSRF-TOKEN: ..."
   ```

3. **Grade Essay Questions**
   ```bash
   curl -X PATCH http://localhost:8000/admin/attempts/{attempt-uuid}/grade-essay \
     -H "Content-Type: application/json" \
     -H "Cookie: laravel_session=..." \
     -H "X-CSRF-TOKEN: ..." \
     -d '{
       "grades": [
         {"question_id": "uuid", "points_awarded": 8.5}
       ]
     }'
   ```

---

## DATABASE SCHEMA

### exam_attempts Table
```sql
- id (UUID, PK)
- school_id (UUID, FK schools.id)
- student_id (UUID, FK users.id)
- exam_id (UUID, FK exams.id)
- attempt_number (INT)
- status (ENUM: IN_PROGRESS, SUBMITTED, PENDING_MANUAL, GRADED)
- reset_version (INT, default 0)
- active_session_token (VARCHAR, nullable)
- last_heartbeat (DATETIME, nullable)
- started_at (DATETIME)
- submitted_at (DATETIME, nullable)
- max_possible_score (DECIMAL 8,2)
- raw_score (DECIMAL 8,2, nullable)
- percentage (DECIMAL 5,2, nullable)
- UNIQUE(student_id, exam_id, attempt_number)
```

### attempt_answers Table
```sql
- id (UUID, PK)
- attempt_id (UUID, FK exam_attempts.id)
- question_id (UUID, FK questions.id)
- reset_version (INT, default 0)
- student_response (JSON)
- points_awarded (DECIMAL 8,2, nullable)
- UNIQUE(attempt_id, question_id, reset_version)
```

### answer_time_logs Table
```sql
- id (UUID, PK)
- attempt_id (UUID, FK exam_attempts.id)
- question_id (UUID, FK questions.id)
- reset_version (INT, default 0)
- start_time (DATETIME)
- end_time (DATETIME, nullable)
- duration_seconds (INT, nullable)
```

---

## SECURITY FEATURES

### 1. Tenant Isolation
- All queries scoped by `school_id` from `auth()->user()->school_id`
- Never accept `school_id` from request parameters

### 2. Session Management
- Single active session per attempt
- Session token required for all operations
- 5-minute timeout allows session takeover
- Heartbeat updates `last_heartbeat` timestamp

### 3. Data Privacy
- Students NEVER receive:
  - `points_awarded` field
  - `is_correct` field from options
  - `raw_score` from attempt
  - `percentage` from attempt
  - `max_possible_score` from attempt

### 4. Concurrency Control
- Session token validation prevents conflicts
- HTTP 409 Conflict returned for session mismatch
- Last-write-wins for autosave (acceptable)

### 5. Time Enforcement
- Server-side time calculation only
- Auto-submit on time expiration
- Respects exam overrides for deadlines

---

## ERROR CODES

- **403 Forbidden**: Not assigned or unauthorized
- **409 Conflict**: Session conflict or active attempt exists
- **422 Unprocessable**: Invalid data or already submitted
- **423 Locked**: Exam locked, expired, or time limit exceeded
- **429 Too Many Requests**: Max attempts exceeded

---

## BUSINESS RULES

### Start Attempt
1. Must be assigned (SCHOOL or STUDENT assignment)
2. Exam state must be AVAILABLE
3. Must not exceed max_attempts
4. No existing IN_PROGRESS attempt

### Autosave
1. Attempt must be IN_PROGRESS
2. Session token must be valid
3. Reset version must match
4. Question must belong to exam

### Reset All
1. Increments reset_version
2. Does NOT reset timer (started_at unchanged)
3. Old answers preserved for audit

### Submit
1. Auto-grades MCQ/TF questions
2. Status becomes SUBMITTED
3. If has ESSAY → PENDING_MANUAL
4. If no ESSAY → GRADED
5. Students see NO scores

### Manual Grading
1. Admin only
2. Attempt must be PENDING_MANUAL
3. All essays must be graded
4. Points cannot exceed max per question
5. Status becomes GRADED after completion

---

## NOTES

- No UI views created (API-only implementation)
- Frontend can be built using the API endpoints
- Session tokens should be stored securely (not in localStorage)
- Heartbeat should be sent every 60 seconds
- Time remaining calculated server-side only

---

## NEXT STEPS

After running migrations, you can:
1. Test the API endpoints using curl or Postman
2. Build a frontend UI for the attempt flow
3. Add real-time features (WebSockets for heartbeat)
4. Implement attempt history views
5. Add analytics dashboards

---

## TROUBLESHOOTING

### Migration Errors
```bash
# If migrations fail, rollback and retry
php artisan migrate:rollback
php artisan migrate
```

### Route Not Found
```bash
# Clear route cache
php artisan route:clear
php artisan route:list | grep attempt
```

### Session Token Issues
- Ensure session token is sent in every request
- Check if session is stale (> 5 minutes)
- Verify session token matches active_session_token

### Time Expiration
- Server time is authoritative
- Check for exam overrides
- Verify started_at + duration_minutes calculation

---

## COMPLETION CHECKLIST

✅ Database migrations created (3 tables)
✅ Models created (3 models)
✅ Service created (AttemptService)
✅ Controllers created (2 controllers)
✅ Routes added (7 routes)
✅ Security implemented (tenant isolation, session management)
✅ Auto-grading implemented (MCQ/TF)
✅ Manual grading implemented (ESSAY)
✅ Documentation complete

---

**Sprint 3 Phase 2 is complete and ready for testing!**
