# SPRINT 4: AUTOMATED CRITICAL PATH TESTING

## 🎯 Overview

This document describes the automated testing command that validates the complete student exam attempt workflow without requiring browser interaction or CSRF tokens.

---

## 📋 Command Details

### Command Name
```bash
php artisan attempts:test-critical
```

### Options
- `--student=ahmed_ali` - Student username (default: ahmed_ali)
- `--exam=019b6cd5-63ed-704f-b3f4-fb47bc1ef9ae` - Exam ID (default: provided ID)

### Example Usage
```bash
# Use default values
php artisan attempts:test-critical

# Specify custom student and exam
php artisan attempts:test-critical --student=john_doe --exam=your-exam-id
```

---

## 🧪 Test Coverage

The command automatically tests the following critical path scenarios:

### Test A: Save with Valid Question
- **Action:** Save an answer for a question that belongs to the exam
- **Expected:** 200 OK, answer saved successfully
- **Validates:** Basic save functionality

### Test B: Heartbeat
- **Action:** Update last_heartbeat timestamp
- **Expected:** 200 OK, heartbeat updated
- **Validates:** Session keep-alive mechanism

### Test C: Reset Attempt
- **Action:** Reset all answers and increment reset_version
- **Expected:** 200 OK, new session token, reset_version incremented
- **Validates:** Reset functionality with version control

### Test D: Save After Reset
- **Action:** Save answer with new session token and reset_version
- **Expected:** 200 OK, answer saved with new reset_version
- **Validates:** Answers are properly versioned

### Test E: Submit Attempt
- **Action:** Submit the attempt
- **Expected:** 200 OK, status = SUBMITTED, session token cleared
- **Validates:** Submission process

### Test F: Save After Submit (Security)
- **Action:** Attempt to save after submission
- **Expected:** 409 Conflict, "Attempt is not active"
- **Validates:** Post-submission protection

### Test G: Invalid Session Token (Security)
- **Action:** Attempt to save with wrong session token
- **Expected:** 403 Forbidden, "Invalid session"
- **Validates:** Session token validation

### Test H: Invalid Question (Security)
- **Action:** Attempt to save answer for question not in exam
- **Expected:** 422 Unprocessable, code: QUESTION_NOT_IN_EXAM
- **Validates:** Question validation

---

## 🔧 Setup Phase

The command automatically handles setup:

1. **Find Student:** Locates student by username
2. **Find Exam:** Locates exam by ID
3. **Verify School:** Ensures student and exam belong to same school
4. **Time Window Adjustment (Local Only):**
   - If exam is outside time window, temporarily adjusts starts_at and ends_at
   - Only in local environment
   - Prints what was changed

5. **Assignment Check:**
   - Ensures student is assigned to exam
   - Creates assignment if missing (local only)

6. **Max Attempts Check:**
   - If max attempts reached, temporarily increases limit (local only)
   - Prevents test failures due to attempt limits

---

## 📊 Output Format

### During Execution
```
=================================================
  CRITICAL PATH TEST: Student Exam Attempt Flow
=================================================

📋 SETUP PHASE
✓ Found student: Ahmed Ali (ID: xxx)
✓ Found exam: Math Final Exam (ID: xxx)
✓ Valid question ID: xxx
✓ Setup complete

🚀 STARTING ATTEMPT
✓ Attempt started successfully
  Attempt ID: xxx
  Session Token: xxx...
  Attempt Number: 1
  Reset Version: 0

🧪 RUNNING TESTS
✓ A) SAVE with valid question: 200 OK - Answer saved successfully
✓ B) HEARTBEAT: 200 OK - Heartbeat updated
✓ C) RESET: 200 OK - Reset version: 0 → 1, New session token generated
✓ D) SAVE after reset with new session: 200 OK - Answer saved with new reset_version
✓ E) SUBMIT: 200 OK - Status: SUBMITTED, Session token cleared
✓ F) SAVE after submit (expect 409): 409 Conflict - Attempt is not active - correctly blocked
✓ G) SAVE with invalid session (expect 403): 403 Forbidden - Invalid session correctly rejected
✓ H) SAVE with question not in exam (expect 422): 422 Unprocessable - Question not in exam - correctly rejected
```

### Results Summary
```
=================================================
  TEST RESULTS SUMMARY
=================================================

┌────────────────────────────────────────┬────────┬─────────────────┬──────────────────────┐
│ Test                                   │ Status │ Code            │ Message              │
├────────────────────────────────────────┼────────┼─────────────────┼──────────────────────┤
│ A) SAVE with valid question           │ PASS   │ 200 OK          │ Answer saved...      │
│ B) HEARTBEAT                           │ PASS   │ 200 OK          │ Heartbeat updated    │
│ C) RESET                               │ PASS   │ 200 OK          │ Reset version: 0 → 1 │
│ D) SAVE after reset                    │ PASS   │ 200 OK          │ Answer saved...      │
│ E) SUBMIT                              │ PASS   │ 200 OK          │ Status: SUBMITTED    │
│ F) SAVE after submit (expect 409)     │ PASS   │ 409 Conflict    │ Correctly blocked    │
│ G) SAVE with invalid session (403)    │ PASS   │ 403 Forbidden   │ Correctly rejected   │
│ H) SAVE with invalid question (422)   │ PASS   │ 422 Unprocessable│ Correctly rejected   │
└────────────────────────────────────────┴────────┴─────────────────┴──────────────────────┘

📊 STATISTICS
  Total Tests: 8
  Passed: 8
  Failed: 0
  Success Rate: 100%

📋 FINAL ATTEMPT STATE
  Attempt ID: xxx
  Status: SUBMITTED
  Reset Version: 1
  Active Session Token: NULL (cleared after submit)
  Started At: 2024-01-01 12:00:00
  Submitted At: 2024-01-01 12:05:00

✅ ALL TESTS PASSED!
=================================================
```

---

## ✅ Success Criteria

### All Tests Pass
- All 8 tests show **PASS** status
- Success Rate: **100%**
- Final message: **✅ ALL TESTS PASSED!**

### Final Attempt State
- **Status:** SUBMITTED (or PENDING_MANUAL if has essays)
- **Reset Version:** 1 (incremented from 0)
- **Active Session Token:** NULL (cleared after submit)
- **Submitted At:** Set to timestamp

---

## ❌ Failure Scenarios

### Test Failures
If any test shows **FAIL** status:
1. Check the error message in the results table
2. Review the specific test logic
3. Verify database state
4. Check Laravel logs

### Common Issues
- **Student not found:** Verify username exists and role is 'student'
- **Exam not found:** Verify exam ID is correct
- **No questions:** Exam must have at least one question
- **School mismatch:** Student and exam must belong to same school

---

## 🔒 Safety Features

### Local Environment Only
The following adjustments only happen in local environment:
- Time window adjustments
- Assignment creation
- Max attempts increase

### Production Safety
- No test-only modifications in production
- All changes guarded by `app()->environment('local')`
- Reversible changes only

### Database Safety
- Uses transactions where appropriate
- No destructive operations
- Creates new attempt (doesn't modify existing)

---

## 🚀 Running the Tests

### Prerequisites
1. Database must be migrated
2. Student 'ahmed_ali' must exist with role='student'
3. Exam with ID '019b6cd5-63ed-704f-b3f4-fb47bc1ef9ae' must exist
4. Exam must have at least one question

### Step-by-Step
```bash
# 1. Navigate to project directory
cd c:/laragon/www/school-exams

# 2. Run the test command
php artisan attempts:test-critical

# 3. Review output for PASS/FAIL status

# 4. Check final statistics
# Look for "✅ ALL TESTS PASSED!" or "❌ SOME TESTS FAILED"
```

### With Custom Parameters
```bash
# Test with different student
php artisan attempts:test-critical --student=jane_smith

# Test with different exam
php artisan attempts:test-critical --exam=your-exam-uuid

# Test with both custom
php artisan attempts:test-critical --student=jane_smith --exam=your-exam-uuid
```

---

## 📝 Verification Commands

### Check Command is Registered
```bash
php artisan list | findstr "attempts:test-critical"
```

Expected output:
```
attempts:test-critical    Run automated critical-path testing for student exam attempt workflow
```

### Check Attempt in Database
```bash
php artisan tinker
```

Then in tinker:
```php
// Get latest attempt
$attempt = \App\Models\ExamAttempt::latest()->first();

// Check details
$attempt->status;           // Should be 'SUBMITTED'
$attempt->reset_version;    // Should be 1
$attempt->active_session_token; // Should be null
$attempt->submitted_at;     // Should have timestamp
```

---

## 🐛 Troubleshooting

### Command Not Found
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Re-run
php artisan attempts:test-critical
```

### Student Not Found
```bash
# Check if student exists
php artisan tinker
\App\Models\User::where('username', 'ahmed_ali')->where('role', 'student')->first();

# If not found, create or use different username
php artisan attempts:test-critical --student=existing_username
```

### Exam Not Found
```bash
# Check if exam exists
php artisan tinker
\App\Models\Exam::find('019b6cd5-63ed-704f-b3f4-fb47bc1ef9ae');

# If not found, use different exam ID
php artisan attempts:test-critical --exam=existing_exam_id
```

### All Tests Fail
1. Check database connection
2. Verify migrations are up to date
3. Check Laravel logs: `storage/logs/laravel.log`
4. Ensure environment is set to 'local' for test adjustments

---

## 📈 Integration with CI/CD

### Add to Test Suite
```bash
# In your CI/CD pipeline
php artisan attempts:test-critical

# Check exit code
if [ $? -eq 0 ]; then
    echo "Tests passed"
else
    echo "Tests failed"
    exit 1
fi
```

### GitHub Actions Example
```yaml
- name: Run Critical Path Tests
  run: php artisan attempts:test-critical
```

---

## 🎉 Benefits

1. **No Browser Required:** Tests run entirely in CLI
2. **No CSRF Tokens:** Direct database/service interaction
3. **Fast Execution:** Completes in seconds
4. **Comprehensive:** Tests all critical scenarios
5. **Safe:** Local-only adjustments, no production impact
6. **Automated:** One command tests entire workflow
7. **Clear Output:** Easy to read PASS/FAIL results
8. **Detailed:** Shows exact error messages on failure

---

**Command:** `php artisan attempts:test-critical`  
**Status:** ✅ Ready to Use  
**Environment:** Local Development Only
