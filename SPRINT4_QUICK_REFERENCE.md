# SPRINT 4: QUICK REFERENCE CARD

## 🚀 NEW FEATURES AT A GLANCE

### 1️⃣ Auto-Submit Command
```bash
# Run manually
php artisan attempts:auto-submit

# Runs automatically every minute via scheduler
# Add to crontab: * * * * * cd /path && php artisan schedule:run
```

### 2️⃣ Reset Attempt
```bash
POST /student/attempts/{attempt}/reset
Headers: X-ATTEMPT-SESSION: {token}

Response:
{
  "ok": true,
  "reset_version": 1,
  "session": "new_token",
  "started_at": "2024-01-01T12:00:00Z"
}
```

### 3️⃣ Finalize Grading
```bash
POST /admin/attempts/{attempt}/finalize-grading

Response:
{
  "attempt_id": "uuid",
  "status": "GRADED",
  "raw_score": 85.5,
  "percentage": 85.5,
  "message": "Grading finalized successfully"
}
```

---

## 📋 COMPLETE API ENDPOINTS

### Student Routes
| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/student/exams/{exam}/start` | Start attempt |
| POST | `/student/attempts/{attempt}/heartbeat` | Send heartbeat |
| POST | `/student/attempts/{attempt}/save` | Save answer |
| POST | `/student/attempts/{attempt}/submit` | Submit attempt |
| POST | `/student/attempts/{attempt}/reset` | ✨ Reset attempt |

### Admin Routes
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/admin/attempts/{attempt}` | View attempt |
| PATCH | `/admin/attempts/{attempt}/grade-essay` | Grade essays |
| POST | `/admin/attempts/{attempt}/finalize-grading` | ✨ Finalize |

---

## 🔐 ERROR CODES

| Code | Meaning | When |
|------|---------|------|
| 200 | Success | Operation completed |
| 201 | Created | Attempt started |
| 403 | Forbidden | Invalid session or unauthorized |
| 404 | Not Found | Attempt doesn't exist or not owned |
| 409 | Conflict | Attempt not active (already submitted) |
| 422 | Unprocessable | Invalid data (e.g., question not in exam) |
| 423 | Locked | Invalid state (e.g., reset on submitted) |

---

## 🔄 ATTEMPT STATUS FLOW

```
IN_PROGRESS → SUBMITTED → PENDING_MANUAL → GRADED
            ↓
            GRADED (if no essays)
```

---

## 🧪 QUICK TEST COMMANDS

```bash
# Test auto-submit
php artisan attempts:auto-submit

# Check scheduler
php artisan schedule:list

# View logs
tail -f storage/logs/laravel.log

# Test reset (curl)
curl -X POST http://localhost/student/attempts/{id}/reset \
  -H "Authorization: Bearer {token}" \
  -H "X-ATTEMPT-SESSION: {session}"

# Test finalize (curl)
curl -X POST http://localhost/admin/attempts/{id}/finalize-grading \
  -H "Authorization: Bearer {admin_token}"
```

---

## 📊 DATABASE FIELDS

### exam_attempts
- `reset_version` - Increments on reset
- `active_session_token` - Current session (null after submit)
- `started_at` - When attempt started (resets on reset)
- `submitted_at` - When submitted
- `status` - IN_PROGRESS, SUBMITTED, PENDING_MANUAL, GRADED
- `raw_score` - Total points earned
- `percentage` - Score percentage

### attempt_answers
- `reset_version` - Links to attempt's reset_version
- `student_response` - JSON answer data
- `points_awarded` - Points earned (null until graded)

---

## 🎯 KEY BEHAVIORS

### Auto-Submit
- Runs every minute
- Checks: `started_at + duration_minutes < now()`
- Updates: status, submitted_at, clears session
- Auto-grades objective questions

### Reset
- Increments reset_version
- Generates new session token
- Resets started_at to now
- Previous answers kept (filtered by version)

### Grading
- Objective (MCQ/TF): Auto-graded on submit
- Essay: Manual grading required
- Finalize: Calculates totals, sets GRADED status

### Security
- Students never see scores
- Session tokens prevent tampering
- Question validation prevents cheating
- Tenant isolation enforced

---

## 🚨 TROUBLESHOOTING

### Auto-submit not working?
1. Check crontab: `crontab -l`
2. Run manually: `php artisan attempts:auto-submit`
3. Check logs: `tail -f storage/logs/laravel.log`

### Reset failing?
1. Verify session token is correct
2. Check attempt status is IN_PROGRESS
3. Ensure student owns the attempt

### Grading errors?
1. Check attempt status (SUBMITTED or PENDING_MANUAL)
2. Verify all essays have points_awarded
3. Ensure max_possible_score > 0

---

## 📁 FILES MODIFIED

### New Files
- `app/Console/Commands/AutoSubmitExpiredAttempts.php`
- `SPRINT4_TESTING_GUIDE.md`
- `SPRINT4_DELIVERABLE.md`
- `SPRINT4_QUICK_REFERENCE.md`

### Modified Files
- `routes/console.php` - Scheduler registration
- `app/Http/Controllers/Student/AttemptController.php` - Reset method
- `app/Http/Controllers/Admin/AttemptGradingController.php` - Finalize method
- `routes/web.php` - New route

---

## ✅ DEPLOYMENT CHECKLIST

- [ ] Pull latest code
- [ ] Run `composer install`
- [ ] Run `php artisan migrate` (no new migrations)
- [ ] Add cron job for scheduler
- [ ] Test auto-submit: `php artisan attempts:auto-submit`
- [ ] Verify scheduler: `php artisan schedule:list`
- [ ] Test reset endpoint
- [ ] Test finalize endpoint
- [ ] Monitor logs for errors

---

## 📞 SUPPORT

**Documentation:**
- Full Testing Guide: `SPRINT4_TESTING_GUIDE.md`
- Complete Deliverable: `SPRINT4_DELIVERABLE.md`
- This Quick Reference: `SPRINT4_QUICK_REFERENCE.md`

**Key Concepts:**
- Reset Version: Allows clearing answers without creating new attempt
- Session Tokens: Prevent concurrent access and tampering
- Auto-Submit: Ensures exams end on time even if student disconnects
- Finalize Grading: Separates essay grading from score calculation

---

**Version:** Sprint 4  
**Status:** ✅ Production Ready  
**Last Updated:** 2024
