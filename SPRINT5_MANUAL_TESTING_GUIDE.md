# SPRINT 5: MANUAL TESTING GUIDE

## 🎯 Critical-Path Testing Checklist

This guide provides step-by-step instructions for testing the Student Exam Room UI.

---

## ⚙️ PRE-TESTING SETUP

### 1. Ensure Laragon is Running
- Start Laragon
- Verify Apache and MySQL are running
- Access: `http://school-exams.test` or `http://localhost/school-exams`

### 2. Verify Database
```bash
php artisan migrate:status
```
All migrations should show "Ran".

### 3. Clear Caches
```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### 4. Verify Routes
```bash
php artisan route:list | findstr "student"
```

**Expected to see:**
```
GET|HEAD  student/exams/{exam}/intro ............... student.exams.intro
GET|HEAD  student/attempts/{attempt}/room .......... student.attempts.room
```

---

## 🧪 CRITICAL-PATH TEST FLOW

### TEST 1: Login as Student ✅

**Steps:**
1. Open browser: `http://school-exams.test/login`
2. Enter credentials:
   - Username: `ahmed_ali`
   - Password: `password`
3. Click "Login"

**Expected Result:**
- ✅ Redirects to student dashboard
- ✅ Shows student name in navbar
- ✅ No errors

**If Failed:**
- Check database has student user
- Verify credentials are correct
- Check session configuration

---

### TEST 2: Navigate to Exam Intro Page ✅

**Steps:**
1. From student dashboard, click on an exam
2. Or directly navigate to: `http://school-exams.test/student/exams/{exam_id}/intro`
   - Use exam ID from database (e.g., `019b6cd5-63ed-704f-b3f4-fb47bc1ef9ae`)

**Expected Result:**
- ✅ Page loads without errors
- ✅ Exam title displayed (Arabic + English)
- ✅ Duration shown (e.g., "60 دقيقة")
- ✅ Question count shown
- ✅ Max attempts shown
- ✅ Current attempt count shown (e.g., "0 / 3")
- ✅ Instructions in Arabic visible
- ✅ "بدء الامتحان" (Start Exam) button visible
- ✅ Back button to exam list visible

**If Failed:**
- Check exam exists in database
- Check exam is assigned to student
- Check exam time window (starts_at/ends_at)
- View browser console for errors

---

### TEST 3: Start Exam ✅

**Steps:**
1. On intro page, click "بدء الامتحان" button
2. Watch for loading indicator

**Expected Result:**
- ✅ Button shows "جاري البدء..." (Starting...)
- ✅ Button is disabled during request
- ✅ Redirects to: `/student/attempts/{attempt_id}/room`
- ✅ No JavaScript errors in console

**If Failed:**
- Open browser DevTools (F12)
- Check Network tab for failed requests
- Check Console for JavaScript errors
- Verify CSRF token is present in page
- Check backend logs

---

### TEST 4: Exam Room Loads ✅

**Steps:**
1. After redirect, verify exam room interface

**Expected Result:**

**Header Section:**
- ✅ Exam title displayed
- ✅ Timer showing countdown (e.g., "59:45")
- ✅ Timer updates every second
- ✅ Autosave indicator visible

**Sidebar:**
- ✅ Question numbers displayed (1, 2, 3...)
- ✅ Questions are gray (not answered)
- ✅ Current question highlighted in blue

**Main Area:**
- ✅ Question prompt displayed (Arabic + English)
- ✅ Question type badge shown (MCQ/TF/ESSAY)
- ✅ Difficulty badge shown
- ✅ Options displayed (for MCQ/TF) OR textarea (for ESSAY)

**Footer:**
- ✅ Previous button (disabled on first question)
- ✅ Next button
- ✅ Submit button
- ✅ Reset button (if local environment)

**If Failed:**
- Check browser console for errors
- Verify Alpine.js loaded (check Network tab)
- Check attempt exists in database
- Verify questions loaded from backend

---

### TEST 5: Answer a Question ✅

**Steps:**
1. Select an answer (click radio button for MCQ/TF)
2. Watch for autosave indicator

**Expected Result:**
- ✅ Option is selected (highlighted)
- ✅ "جاري الحفظ..." (Saving...) appears briefly
- ✅ "تم الحفظ" (Saved) appears after ~1 second
- ✅ Question number in sidebar turns green
- ✅ No errors in console

**Network Tab Check:**
- ✅ POST request to `/student/attempts/{attempt_id}/save`
- ✅ Status: 200 OK
- ✅ Headers include `X-CSRF-TOKEN` and `X-ATTEMPT-SESSION`

**If Failed:**
- Check network request details
- Verify session token is sent
- Check backend logs for errors
- Verify question belongs to exam

---

### TEST 6: Navigate Between Questions ✅

**Steps:**
1. Click "التالي" (Next) button
2. Verify next question loads
3. Click question number in sidebar
4. Verify jumps to that question

**Expected Result:**
- ✅ Question changes
- ✅ Previous answer is preserved
- ✅ Sidebar highlights current question
- ✅ Previous/Next buttons enable/disable correctly

**If Failed:**
- Check Alpine.js state management
- Verify answers object in browser console

---

### TEST 7: Timer Functionality ✅

**Steps:**
1. Watch timer for 10 seconds
2. Verify it counts down

**Expected Result:**
- ✅ Timer updates every second
- ✅ Format: MM:SS (e.g., "59:30")
- ✅ Timer color is normal (not red yet)

**Optional (if time allows):**
- Wait until < 5 minutes remaining
- ✅ Timer turns red
- ✅ Alert shown: "تبقى 5 دقائق فقط!"

**If Failed:**
- Check JavaScript console for errors
- Verify countdown interval is running

---

### TEST 8: Heartbeat ✅

**Steps:**
1. Keep exam room open for 20 seconds
2. Check Network tab in DevTools

**Expected Result:**
- ✅ POST request to `/student/attempts/{attempt_id}/heartbeat` every 15 seconds
- ✅ Status: 200 OK
- ✅ No errors

**If Failed:**
- Check heartbeat interval in JavaScript
- Verify backend endpoint works

---

### TEST 9: Submit Exam ✅

**Steps:**
1. Click "إرسال الامتحان" (Submit Exam) button
2. Confirm in dialog

**Expected Result:**
- ✅ Confirmation dialog appears
- ✅ After confirm, submit request sent
- ✅ Success message appears: "✅ تم إرسال الامتحان بنجاح!"
- ✅ Exam interface hidden
- ✅ **NO SCORES DISPLAYED** (critical!)
- ✅ Link to exam list shown
- ✅ Timer stopped

**Network Tab Check:**
- ✅ POST request to `/student/attempts/{attempt_id}/submit`
- ✅ Status: 200 OK
- ✅ Response does NOT include scores

**If Failed:**
- Check network request
- Verify backend submit logic
- Check Alpine.js submitted state

---

### TEST 10: Verify No Scores Shown ✅

**Steps:**
1. After submit, inspect page carefully
2. Check browser DevTools → Elements
3. Search for: "score", "points", "percentage", "grade"

**Expected Result:**
- ✅ NO scores visible anywhere
- ✅ NO raw_score displayed
- ✅ NO percentage displayed
- ✅ Only success message shown

**If Failed:**
- This is a CRITICAL security issue
- Check controller response
- Check view template

---

## 🔄 OPTIONAL TESTS (If Time Allows)

### TEST 11: Reset Attempt (Local Only) ⚠️

**Prerequisites:**
- Must be in local environment (`APP_ENV=local`)

**Steps:**
1. Answer some questions
2. Click "إعادة تعيين" (Reset) button
3. Confirm dialog

**Expected Result:**
- ✅ All answers cleared
- ✅ Questions turn gray again
- ✅ New session token received
- ✅ Can answer again

---

### TEST 12: Browser Refresh ⚠️

**Steps:**
1. Answer some questions
2. Press F5 to refresh page

**Expected Result:**
- ✅ Page reloads
- ✅ Timer continues from correct time
- ✅ Previous answers still visible
- ✅ Can continue exam

---

### TEST 13: Error Handling ⚠️

**Test 13A: Invalid Session (403)**
1. Open exam room in two browser tabs
2. Reset in tab 1
3. Try to save in tab 2

**Expected:**
- ✅ Error banner: "انتهت الجلسة..."
- ✅ Status code: 403

**Test 13B: After Submit (409)**
1. Submit exam
2. Try to save via browser console:
```javascript
fetch('/student/attempts/{attempt_id}/save', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'X-ATTEMPT-SESSION': 'any-token',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({question_id: 'any-id', response: {}})
})
```

**Expected:**
- ✅ Status code: 409
- ✅ Error message about attempt not active

---

## 📊 TEST RESULTS TEMPLATE

Copy this template and fill in results:

```
=================================================
SPRINT 5: CRITICAL-PATH TEST RESULTS
=================================================
Date: _______________
Tester: _______________
Environment: Local / Staging / Production

TEST 1: Login as Student .................. [ PASS / FAIL ]
TEST 2: Navigate to Exam Intro ............ [ PASS / FAIL ]
TEST 3: Start Exam ........................ [ PASS / FAIL ]
TEST 4: Exam Room Loads ................... [ PASS / FAIL ]
TEST 5: Answer a Question ................. [ PASS / FAIL ]
TEST 6: Navigate Between Questions ........ [ PASS / FAIL ]
TEST 7: Timer Functionality ............... [ PASS / FAIL ]
TEST 8: Heartbeat ......................... [ PASS / FAIL ]
TEST 9: Submit Exam ....................... [ PASS / FAIL ]
TEST 10: Verify No Scores Shown ........... [ PASS / FAIL ]

OPTIONAL TESTS:
TEST 11: Reset Attempt .................... [ PASS / FAIL / SKIP ]
TEST 12: Browser Refresh .................. [ PASS / FAIL / SKIP ]
TEST 13: Error Handling ................... [ PASS / FAIL / SKIP ]

=================================================
SUMMARY
=================================================
Total Tests: 10 (critical) + 3 (optional)
Passed: _____ / 10
Failed: _____ / 10
Success Rate: _____%

CRITICAL ISSUES FOUND:
1. _______________________________________
2. _______________________________________

MINOR ISSUES FOUND:
1. _______________________________________
2. _______________________________________

NOTES:
_____________________________________________
_____________________________________________

RECOMMENDATION:
[ ] Ready for Production
[ ] Needs Fixes
[ ] Needs More Testing
```

---

## 🐛 COMMON ISSUES & SOLUTIONS

### Issue: Page Not Loading
**Solution:**
- Check Laragon is running
- Verify URL is correct
- Clear browser cache
- Check Apache error logs

### Issue: CSRF Token Mismatch
**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
```
- Refresh page
- Check meta tag in HTML

### Issue: Alpine.js Not Working
**Solution:**
- Check browser console for errors
- Verify CDN loaded (Network tab)
- Check for JavaScript syntax errors

### Issue: Timer Not Counting
**Solution:**
- Check JavaScript console
- Verify `timeRemaining` is set correctly
- Check `startCountdown()` is called

### Issue: Autosave Not Working
**Solution:**
- Check Network tab for failed requests
- Verify session token is sent
- Check backend logs
- Verify question ID is correct

---

## ✅ ACCEPTANCE CRITERIA

Sprint 5 is considered **COMPLETE** when:

- [x] All 10 critical tests PASS
- [x] No JavaScript errors in console
- [x] No PHP errors in logs
- [x] Timer works correctly
- [x] Autosave works correctly
- [x] Submit works correctly
- [x] **NO SCORES shown to students** (CRITICAL)
- [x] UI is responsive and usable
- [x] Error handling works

---

## 📝 NEXT STEPS AFTER TESTING

### If All Tests Pass ✅
1. Mark Sprint 5 as complete
2. Deploy to staging/production
3. Notify stakeholders
4. Update documentation

### If Tests Fail ❌
1. Document all failures
2. Create bug tickets
3. Fix issues
4. Re-test
5. Repeat until all pass

---

**Testing Time Estimate:** 15-20 minutes for critical path

**Good luck with testing! 🚀**
