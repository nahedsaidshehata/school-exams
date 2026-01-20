# SPRINT 4: EXAM SYSTEM COMPLETION - TESTING GUIDE

## Overview
This guide provides comprehensive testing procedures for all newly implemented features in Sprint 4.

---

## 🎯 IMPLEMENTED FEATURES

### 1. Auto-Submit When Time Ends
### 2. Reset Attempt (New Version)
### 3. Admin Grading System (Complete)
### 4. Security & Integrity Enhancements

---

## 📋 TESTING CHECKLIST

## FEATURE 1: AUTO-SUBMIT WHEN TIME ENDS

### Setup
```bash
# Test the command manually
php artisan attempts:auto-submit
```

### Test Scenarios

#### Test 1.1: Auto-submit expired attempt
**Prerequisites:**
- Create an exam with duration_minutes = 1
- Start an attempt as a student
- Wait 2 minutes (or manually update started_at in database)

**Steps:**
```bash
# Run the command
php artisan attempts:auto-submit
```

**Expected Result:**
- Attempt status changes from IN_PROGRESS → SUBMITTED (or PENDING_MANUAL if has essays)
- submitted_at is set to current timestamp
- active_session_token is cleared (null)
- Objective questions are auto-graded
- Console shows: "Found 1 expired attempt(s)" and "Auto-submit completed: 1 successful"

#### Test 1.2: No expired attempts
**Prerequisites:**
- All attempts are either not started or within time limit

**Steps:**
```bash
php artisan attempts:auto-submit
```

**Expected Result:**
- Console shows: "No expired attempts found."

#### Test 1.3: Idempotent behavior
**Prerequisites:**
- Have an already-submitted attempt

**Steps:**
```bash
# Run command twice
php artisan attempts:auto-submit
php artisan attempts:auto-submit
```

**Expected Result:**
- Second run skips already-processed attempts
- No errors or duplicate submissions

#### Test 1.4: Scheduled execution
**Prerequisites:**
- Ensure Laravel scheduler is running

**Steps:**
```bash
# Run scheduler (in production, this runs via cron)
php artisan schedule:work
```

**Expected Result:**
- Command runs every minute automatically
- Check logs for execution

---

## FEATURE 2: RESET ATTEMPT

### Test Scenarios

#### Test 2.1: Successful reset
**Prerequisites:**
- Student has an IN_PROGRESS attempt
- Has saved some answers

**Request:**
```bash
POST /student/attempts/{attempt_id}/reset
Headers:
  Authorization: Bearer {student_token}
  X-ATTEMPT-SESSION: {current_session_token}
```

**Expected Response (200):**
```json
{
  "ok": true,
  "reset_version": 1,
  "session": "new_session_token_here",
  "started_at": "2024-01-01T12:00:00.000000Z",
  "message": "تم إعادة تعيين جميع الإجابات بنجاح"
}
```

**Verify:**
- reset_version incremented
- New session token generated
- started_at reset to now
- last_heartbeat reset to now
- Previous answers still in database but with old reset_version
- Status remains IN_PROGRESS

#### Test 2.2: Reset with invalid session
**Request:**
```bash
POST /student/attempts/{attempt_id}/reset
Headers:
  Authorization: Bearer {student_token}
  X-ATTEMPT-SESSION: invalid_token
```

**Expected Response (403):**
```json
{
  "message": "جلسة غير صالحة",
  "code": "INVALID_SESSION"
}
```

#### Test 2.3: Reset already submitted attempt
**Prerequisites:**
- Attempt status is SUBMITTED

**Expected Response (423):**
```json
{
  "message": "يمكن إعادة تعيين المحاولات النشطة فقط",
  "code": "ATTEMPT_NOT_ACTIVE"
}
```

#### Test 2.4: Reset by different student
**Prerequisites:**
- Attempt belongs to Student A
- Request from Student B

**Expected Response (404):**
- Attempt not found (due to student_id filter)

---

## FEATURE 3: ADMIN GRADING SYSTEM

### Test Scenarios

#### Test 3.1: View attempt for grading
**Request:**
```bash
GET /admin/attempts/{attempt_id}
Headers:
  Authorization: Bearer {admin_token}
```

**Expected Response (200):**
```json
{
  "attempt": {
    "id": "uuid",
    "attempt_number": 1,
    "status": "PENDING_MANUAL",
    "started_at": "...",
    "submitted_at": "...",
    "max_possible_score": 100,
    "raw_score": null,
    "percentage": null,
    "student": {
      "id": "uuid",
      "full_name": "Student Name",
      "username": "student123"
    },
    "exam": {
      "id": "uuid",
      "title_en": "Exam Title",
      "title_ar": "عنوان الامتحان"
    },
    "answers": [
      {
        "question_id": "uuid",
        "question_type": "ESSAY",
        "question_prompt_en": "...",
        "question_prompt_ar": "...",
        "student_response": {"text": "..."},
        "points_awarded": null,
        "options": []
      }
    ]
  }
}
```

#### Test 3.2: Grade essay questions
**Request:**
```bash
PATCH /admin/attempts/{attempt_id}/grade-essay
Headers:
  Authorization: Bearer {admin_token}
Content-Type: application/json

Body:
{
  "grades": [
    {
      "question_id": "essay_question_uuid",
      "points_awarded": 8.5
    }
  ]
}
```

**Expected Response (200):**
```json
{
  "attempt_id": "uuid",
  "status": "GRADED",
  "raw_score": 85.5,
  "percentage": 85.5,
  "max_possible_score": 100
}
```

**Verify:**
- points_awarded updated in attempt_answers table
- Attempt status changed to GRADED
- raw_score and percentage calculated correctly

#### Test 3.3: Grade essay with points exceeding max
**Request:**
```bash
PATCH /admin/attempts/{attempt_id}/grade-essay
Body:
{
  "grades": [
    {
      "question_id": "essay_question_uuid",
      "points_awarded": 999
    }
  ]
}
```

**Expected Response (422):**
```json
{
  "error": "Points awarded exceed maximum for question: {question_id}"
}
```

#### Test 3.4: Finalize grading (all questions graded)
**Prerequisites:**
- All essay questions have points_awarded set
- Objective questions already auto-graded

**Request:**
```bash
POST /admin/attempts/{attempt_id}/finalize-grading
Headers:
  Authorization: Bearer {admin_token}
```

**Expected Response (200):**
```json
{
  "attempt_id": "uuid",
  "status": "GRADED",
  "raw_score": 85.5,
  "percentage": 85.5,
  "max_possible_score": 100,
  "message": "Grading finalized successfully"
}
```

#### Test 3.5: Finalize with ungraded essays
**Prerequisites:**
- Some essay questions still have null points_awarded

**Expected Response (422):**
```json
{
  "error": "Cannot finalize: 2 essay question(s) still need grading"
}
```

#### Test 3.6: Finalize already graded attempt
**Prerequisites:**
- Attempt status is already GRADED

**Expected Response (422):**
```json
{
  "error": "Attempt must be SUBMITTED or PENDING_MANUAL to finalize grading"
}
```

---

## FEATURE 4: SECURITY & INTEGRITY

### Test Scenarios

#### Test 4.1: Save answer for question not in exam
**Request:**
```bash
POST /student/attempts/{attempt_id}/save
Headers:
  Authorization: Bearer {student_token}
  X-ATTEMPT-SESSION: {session_token}
Body:
{
  "question_id": "random_question_not_in_exam",
  "response": {"selected_option_id": "some_id"}
}
```

**Expected Response (422):**
```json
{
  "message": "هذا السؤال غير تابع لهذا الامتحان",
  "code": "QUESTION_NOT_IN_EXAM"
}
```

#### Test 4.2: Save answer after submit
**Prerequisites:**
- Attempt status is SUBMITTED

**Request:**
```bash
POST /student/attempts/{attempt_id}/save
Headers:
  Authorization: Bearer {student_token}
  X-ATTEMPT-SESSION: {old_session_token}
Body:
{
  "question_id": "valid_question_id",
  "response": {"selected_option_id": "some_id"}
}
```

**Expected Response (409):**
```json
{
  "message": "Attempt is not active"
}
```

#### Test 4.3: Invalid session token
**Request:**
```bash
POST /student/attempts/{attempt_id}/heartbeat
Headers:
  Authorization: Bearer {student_token}
  X-ATTEMPT-SESSION: invalid_token
```

**Expected Response (403):**
```json
{
  "message": "Invalid session"
}
```

#### Test 4.4: Student accessing another student's attempt
**Prerequisites:**
- Attempt belongs to Student A
- Request from Student B (same school)

**Request:**
```bash
POST /student/attempts/{student_a_attempt_id}/heartbeat
Headers:
  Authorization: Bearer {student_b_token}
  X-ATTEMPT-SESSION: {any_token}
```

**Expected Response (404):**
- Attempt not found (filtered by student_id)

---

## 🔄 COMPLETE WORKFLOW TEST

### End-to-End Scenario

#### Step 1: Student starts exam
```bash
POST /student/exams/{exam_id}/start
```
- Verify: attempt created, session token returned

#### Step 2: Student saves answers
```bash
POST /student/attempts/{attempt_id}/save
Body: {"question_id": "q1", "response": {"selected_option_id": "opt1"}}
```
- Verify: answer saved

#### Step 3: Student sends heartbeat
```bash
POST /student/attempts/{attempt_id}/heartbeat
```
- Verify: last_heartbeat updated

#### Step 4: Student resets attempt
```bash
POST /student/attempts/{attempt_id}/reset
```
- Verify: reset_version incremented, new session token

#### Step 5: Student saves new answers
```bash
POST /student/attempts/{attempt_id}/save
Body: {"question_id": "q1", "response": {"selected_option_id": "opt2"}}
```
- Verify: new answer with new reset_version

#### Step 6: Time expires (or manual submit)
```bash
# Either wait for auto-submit or:
POST /student/attempts/{attempt_id}/submit
```
- Verify: status = SUBMITTED or PENDING_MANUAL

#### Step 7: Admin views attempt
```bash
GET /admin/attempts/{attempt_id}
```
- Verify: all details visible, objective questions auto-graded

#### Step 8: Admin grades essays
```bash
PATCH /admin/attempts/{attempt_id}/grade-essay
Body: {"grades": [{"question_id": "essay1", "points_awarded": 8}]}
```
- Verify: essay graded

#### Step 9: Admin finalizes grading
```bash
POST /admin/attempts/{attempt_id}/finalize-grading
```
- Verify: status = GRADED, scores calculated

#### Step 10: Verify student cannot see scores
```bash
GET /student/exams/{exam_id}
```
- Verify: no raw_score or percentage in response

---

## 🚀 PRODUCTION READINESS CHECKLIST

### Deployment Steps

1. **Database**
   - [ ] Run migrations: `php artisan migrate`
   - [ ] Verify indexes exist on exam_attempts (status, started_at)

2. **Scheduler**
   - [ ] Add to crontab: `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`
   - [ ] Verify scheduler is running: `php artisan schedule:list`

3. **Testing**
   - [ ] Run all test scenarios above
   - [ ] Test with multiple concurrent attempts
   - [ ] Test with different timezones

4. **Monitoring**
   - [ ] Check Laravel logs for auto-submit errors
   - [ ] Monitor database for orphaned IN_PROGRESS attempts
   - [ ] Set up alerts for failed auto-submissions

5. **Performance**
   - [ ] Test auto-submit with 100+ expired attempts
   - [ ] Verify query performance on large datasets
   - [ ] Check memory usage during auto-grading

---

## 📊 EXPECTED BEHAVIOR SUMMARY

| Feature | Status | Notes |
|---------|--------|-------|
| Auto-submit expired attempts | ✅ | Runs every minute via scheduler |
| Reset attempt | ✅ | Increments version, generates new session |
| Admin view attempt | ✅ | Shows all details including correct answers |
| Admin grade essays | ✅ | Updates points_awarded |
| Admin finalize grading | ✅ | Calculates final scores |
| Security validations | ✅ | 422, 409, 403 responses as specified |
| Student score privacy | ✅ | Students never see scores |

---

## 🐛 TROUBLESHOOTING

### Issue: Auto-submit not running
**Solution:** 
- Check if scheduler is configured in crontab
- Run manually: `php artisan attempts:auto-submit`
- Check Laravel logs

### Issue: Reset not working
**Solution:**
- Verify session token is correct
- Check attempt status is IN_PROGRESS
- Verify student owns the attempt

### Issue: Grading fails
**Solution:**
- Check attempt status is SUBMITTED or PENDING_MANUAL
- Verify all essay questions have points_awarded
- Check max_possible_score is not zero

---

## 📝 API ENDPOINTS SUMMARY

### Student Endpoints
- `POST /student/exams/{exam}/start` - Start attempt
- `POST /student/attempts/{attempt}/heartbeat` - Send heartbeat
- `POST /student/attempts/{attempt}/save` - Save answer
- `POST /student/attempts/{attempt}/submit` - Submit attempt
- `POST /student/attempts/{attempt}/reset` - Reset attempt ✨ NEW

### Admin Endpoints
- `GET /admin/attempts/{attempt}` - View attempt details
- `PATCH /admin/attempts/{attempt}/grade-essay` - Grade essay questions
- `POST /admin/attempts/{attempt}/finalize-grading` - Finalize grading ✨ NEW

### Scheduled Commands
- `php artisan attempts:auto-submit` - Auto-submit expired attempts ✨ NEW

---

## ✅ PRODUCTION READY

All features have been implemented and are ready for production deployment. Follow the testing guide above to verify functionality in your environment.
