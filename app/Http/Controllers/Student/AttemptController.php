<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttemptController extends Controller
{
    /**
     * POST /student/exams/{exam}/start
     * GET  /student/exams/{exam}/start
     */
    public function start(Request $request, string $exam)
    {
        $student = $request->user();

        // ✅ IMPORTANT: do not hard-filter by school_id in query
        $examModel = Exam::query()
            ->where('id', $exam)
            ->firstOrFail();

        // ✅ Security: if exam has school_id, enforce it
        if (isset($examModel->school_id) && $examModel->school_id !== $student->school_id) {
            abort(404);
        }

        // ✅ If active attempt exists, continue it
        $activeAttempt = ExamAttempt::query()
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->where('exam_id', $examModel->id)
            ->where('status', 'IN_PROGRESS')
            ->first();

        if ($activeAttempt) {
            if (!$request->expectsJson()) {
                return redirect()->route('student.attempts.room', ['attempt' => $activeAttempt->id]);
            }

            return response()->json([
                'attempt_id' => $activeAttempt->id,
                'exam_id'    => $examModel->id,
                'status'     => $activeAttempt->status,
                'session'    => $activeAttempt->active_session_token,
                'started_at' => $activeAttempt->started_at,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // attempt_number = previous attempts + 1
        $attemptNumber = ExamAttempt::query()
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->where('exam_id', $examModel->id)
            ->count() + 1;

        // Optional: enforce max_attempts if exists on exams
        if (isset($examModel->max_attempts) && $examModel->max_attempts) {
            if ($attemptNumber > (int) $examModel->max_attempts) {
                if (!$request->expectsJson()) {
                    return redirect()
                        ->route('student.exams.intro', $examModel->id)
                        ->with('error', 'لقد وصلت إلى الحد الأقصى لعدد المحاولات لهذا الامتحان');
                }

                return response()->json(['message' => 'Maximum attempts reached'], 403, [], JSON_UNESCAPED_UNICODE);
            }
        }

        $sessionToken = Str::random(64);

        /**
         * ✅ Calculate max_possible_score from exam questions:
         * - Prefer question.points if present
         * - Else use pivot.points (if exists)
         * - Else 0
         * - If no questions => 0
         */
        $maxPossibleScore = 0;

        try {
            if (method_exists($examModel, 'questions')) {
                $examModel->load('questions');

                foreach ($examModel->questions as $question) {
                    $qPoints = $question->points ?? null;
                    $pPoints = $question->pivot->points ?? null;

                    $points = ($qPoints !== null)
                        ? $qPoints
                        : (($pPoints !== null) ? $pPoints : 0);

                    $maxPossibleScore += (int) $points;
                }
            } elseif (method_exists($examModel, 'examQuestions')) {
                $rows = $examModel->examQuestions()->get();

                foreach ($rows as $question) {
                    $qPoints = $question->points ?? null;
                    $pPoints = $question->pivot->points ?? ($question->points ?? null);

                    $points = ($qPoints !== null)
                        ? $qPoints
                        : (($pPoints !== null) ? $pPoints : 0);

                    $maxPossibleScore += (int) $points;
                }
            } else {
                $maxPossibleScore = 0;
            }
        } catch (\Throwable $e) {
            $maxPossibleScore = 0;
        }

        $maxPossibleScore = max(0, (int) $maxPossibleScore);

        $attempt = new ExamAttempt();
        $attempt->forceFill([
            'school_id'            => $student->school_id,
            'student_id'           => $student->id,
            'exam_id'              => $examModel->id,
            'attempt_number'       => $attemptNumber,
            'status'               => 'IN_PROGRESS',
            'reset_version'        => 0,
            'active_session_token' => $sessionToken,
            'last_heartbeat'       => now(),
            'started_at'           => now(),
            'submitted_at'         => null,

            // ✅ Set required NOT NULL fields
            'max_possible_score'   => $maxPossibleScore,
            'raw_score'            => 0,
            'percentage'           => 0,
        ]);
        $attempt->save();

        // ✅ Browser: open room immediately
        if (!$request->expectsJson()) {
            return redirect()->route('student.attempts.room', ['attempt' => $attempt->id]);
        }

        // Student must not receive scores
        return response()->json([
            'attempt_id' => $attempt->id,
            'exam_id'    => $examModel->id,
            'status'     => $attempt->status,
            'session'    => $sessionToken,
            'started_at' => $attempt->started_at,
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST /student/attempts/{attempt}/heartbeat
     * Header: X-ATTEMPT-SESSION: <token>
     */
    public function heartbeat(Request $request, string $attempt)
    {
        $attemptModel = $this->loadAttemptForStudent($request, $attempt);

        $attemptModel->last_heartbeat = now();
        $attemptModel->save();

        return response()->json(['ok' => true, 'server_time' => now()], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST /student/attempts/{attempt}/save
     * Header: X-ATTEMPT-SESSION: <token>
     * JSON:
     *  {
     *    "question_id": "uuid",
     *    "response": {...}
     *  }
     */
    public function save(Request $request, string $attempt)
    {
        $attemptModel = $this->loadAttemptForStudent($request, $attempt);

        $data = $request->validate([
            'question_id' => ['required', 'string'],
            'response'    => ['required', 'array'],
        ]);

        $questionId = $data['question_id'];
        $response   = $data['response'];

        $question = DB::table('questions')
            ->select('id', 'type')
            ->where('id', $questionId)
            ->first();

        if (!$question) {
            return response()->json([
                'message' => 'السؤال غير موجود',
                'code'    => 'QUESTION_NOT_FOUND',
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        if ($question->type === 'REORDER') {
            $order = $response['order'] ?? null;

            if (!is_array($order) || count($order) < 2) {
                return response()->json([
                    'message' => 'صيغة إجابة إعادة الترتيب غير صحيحة',
                    'code'    => 'INVALID_REORDER_RESPONSE',
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            $validOptionIds = DB::table('question_options')
                ->where('question_id', $questionId)
                ->pluck('id')
                ->all();

            $validSet = array_fill_keys($validOptionIds, true);

            foreach ($order as $optId) {
                if (!is_string($optId) || !isset($validSet[$optId])) {
                    return response()->json([
                        'message' => 'تم إرسال خيار غير تابع لهذا السؤال',
                        'code'    => 'INVALID_OPTION_FOR_QUESTION',
                    ], 422, [], JSON_UNESCAPED_UNICODE);
                }
            }

            if (count(array_unique($order)) !== count($order)) {
                return response()->json([
                    'message' => 'لا يمكن تكرار نفس العنصر في الترتيب',
                    'code'    => 'DUPLICATE_REORDER_OPTIONS',
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }
        }

        $belongsToExam = DB::table('exam_questions')
            ->where('exam_id', $attemptModel->exam_id)
            ->where('question_id', $questionId)
            ->exists();

        if (!$belongsToExam) {
            return response()->json([
                'message' => 'هذا السؤال غير تابع لهذا الامتحان',
                'code'    => 'QUESTION_NOT_IN_EXAM',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        DB::transaction(function () use ($attemptModel, $questionId, $response) {

            $existing = DB::table('attempt_answers')
                ->where('attempt_id', $attemptModel->id)
                ->where('question_id', $questionId)
                ->where('reset_version', $attemptModel->reset_version)
                ->first();

            $payload = [
                'student_response' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'updated_at'       => now(),
            ];

            if ($existing) {
                DB::table('attempt_answers')
                    ->where('id', $existing->id)
                    ->update($payload);
            } else {
                DB::table('attempt_answers')->insert([
                    'id'               => (string) Str::uuid(),
                    'attempt_id'       => $attemptModel->id,
                    'question_id'      => $questionId,
                    'reset_version'    => $attemptModel->reset_version,
                    'student_response' => json_encode($response, JSON_UNESCAPED_UNICODE),
                    'points_awarded'   => null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            DB::table('exam_attempts')
                ->where('id', $attemptModel->id)
                ->update(['last_heartbeat' => now(), 'updated_at' => now()]);
        });

        return response()->json(['ok' => true], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST /student/attempts/{attempt}/submit
     * Header: X-ATTEMPT-SESSION: <token>
     */
    public function submit(Request $request, string $attempt)
    {
        $attemptModel = $this->loadAttemptForStudent($request, $attempt);

        DB::transaction(function () use ($attemptModel) {

            $reorderAnswers = DB::table('attempt_answers as aa')
                ->join('questions as q', 'q.id', '=', 'aa.question_id')
                ->where('aa.attempt_id', $attemptModel->id)
                ->where('aa.reset_version', $attemptModel->reset_version)
                ->where('q.type', 'REORDER')
                ->select('aa.id as answer_id', 'aa.question_id', 'aa.student_response')
                ->get();

            foreach ($reorderAnswers as $row) {
                $awarded = $this->gradeReorderAnswer(
                    $attemptModel->exam_id,
                    $row->question_id,
                    $row->student_response
                );

                DB::table('attempt_answers')
                    ->where('id', $row->answer_id)
                    ->update([
                        'points_awarded' => $awarded,
                        'updated_at'     => now(),
                    ]);
            }

            $rawScore = (float) DB::table('attempt_answers')
                ->where('attempt_id', $attemptModel->id)
                ->where('reset_version', $attemptModel->reset_version)
                ->selectRaw('COALESCE(SUM(COALESCE(points_awarded, 0)), 0) as s')
                ->value('s');

            $maxPossible = (float) ($attemptModel->max_possible_score ?? 0);
            $percentage = $maxPossible > 0 ? round(($rawScore / $maxPossible) * 100, 2) : 0;

            DB::table('exam_attempts')
                ->where('id', $attemptModel->id)
                ->update([
                    'status'               => 'SUBMITTED',
                    'submitted_at'         => now(),
                    'active_session_token' => null,
                    'raw_score'            => $rawScore,
                    'percentage'           => $percentage,
                    'updated_at'           => now(),
                ]);
        });

        $attemptModel->refresh();

        return response()->json(['ok' => true, 'status' => $attemptModel->status], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function gradeReorderAnswer(string $examId, string $questionId, $studentResponseRaw): float
    {
        $points = (float) (DB::table('exam_questions')
            ->where('exam_id', $examId)
            ->where('question_id', $questionId)
            ->value('points') ?? 0);

        if ($points <= 0) {
            return 0.0;
        }

        $correct = DB::table('question_options')
            ->where('question_id', $questionId)
            ->orderBy('order_index')
            ->pluck('id')
            ->all();

        if (count($correct) < 2) {
            return 0.0;
        }

        $decoded = is_string($studentResponseRaw) ? json_decode($studentResponseRaw, true) : $studentResponseRaw;
        if (!is_array($decoded)) {
            return 0.0;
        }

        $order = $decoded['order'] ?? null;
        if (!is_array($order) || count($order) !== count($correct)) {
            return 0.0;
        }

        for ($i = 0; $i < count($correct); $i++) {
            if (($order[$i] ?? null) !== $correct[$i]) {
                return 0.0;
            }
        }

        return $points;
    }

    /**
     * POST /student/attempts/{attempt}/reset
     */
    public function reset(Request $request, string $attempt)
    {
        $student = $request->user();
        $session = (string) $request->header('X-ATTEMPT-SESSION', '');

        $attemptModel = ExamAttempt::query()
            ->where('id', $attempt)
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        if ($attemptModel->status !== 'IN_PROGRESS') {
            return response()->json([
                'message' => 'يمكن إعادة تعيين المحاولات النشطة فقط',
                'code' => 'ATTEMPT_NOT_ACTIVE',
            ], 423, [], JSON_UNESCAPED_UNICODE);
        }

        if (!$session || $session !== $attemptModel->active_session_token) {
            return response()->json([
                'message' => 'جلسة غير صالحة',
                'code' => 'INVALID_SESSION',
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }

        $examModel = $attemptModel->exam;
        $totalAttempts = ExamAttempt::query()
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->where('exam_id', $examModel->id)
            ->count();

        if (isset($examModel->max_attempts) && $examModel->max_attempts && $totalAttempts > (int) $examModel->max_attempts) {
            return response()->json([
                'message' => 'تم تجاوز الحد الأقصى للمحاولات',
                'code' => 'MAX_ATTEMPTS_EXCEEDED',
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }

        DB::beginTransaction();
        try {
            $attemptModel->reset_version += 1;

            $newSessionToken = Str::random(64);
            $attemptModel->active_session_token = $newSessionToken;

            $attemptModel->started_at = now();
            $attemptModel->last_heartbeat = now();

            $attemptModel->save();

            DB::commit();

            return response()->json([
                'ok' => true,
                'reset_version' => $attemptModel->reset_version,
                'session' => $newSessionToken,
                'started_at' => $attemptModel->started_at,
                'message' => 'تم إعادة تعيين جميع الإجابات بنجاح',
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل في إعادة تعيين المحاولة',
                'error' => $e->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    private function loadAttemptForStudent(Request $request, string $attemptId): ExamAttempt
    {
        $student = $request->user();
        $session = (string) $request->header('X-ATTEMPT-SESSION', '');

        $attempt = ExamAttempt::query()
            ->where('id', $attemptId)
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        if ($attempt->status !== 'IN_PROGRESS') {
            abort(response()->json(['message' => 'Attempt is not active'], 409, [], JSON_UNESCAPED_UNICODE));
        }

        if (!$session || $session !== $attempt->active_session_token) {
            abort(response()->json(['message' => 'Invalid session'], 403, [], JSON_UNESCAPED_UNICODE));
        }

        return $attempt;
    }
}
