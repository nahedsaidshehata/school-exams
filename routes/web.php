<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Admin Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ExamQuestionApiController;
use App\Http\Controllers\Admin\AttemptGradingController;
use App\Http\Controllers\Admin\LearningOutcomeController;
use App\Http\Controllers\Admin\LessonAttachmentController;

// ✅ NEW: Student Cards
use App\Http\Controllers\Admin\StudentCardController;
use App\Http\Controllers\CardLoginController;

/*
|--------------------------------------------------------------------------
| School Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\School\DashboardController as SchoolDashboardController;
use App\Http\Controllers\School\ExamController as SchoolExamController;

/*
|--------------------------------------------------------------------------
| Student Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use App\Http\Controllers\Student\ExamRoomController;
use App\Http\Controllers\Student\ExamQuestionApiController as StudentExamQuestionApiController;
use App\Http\Controllers\Student\AttemptController;

/*
|--------------------------------------------------------------------------
| Root
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('login'));

/*
|--------------------------------------------------------------------------
| Card Login (QR) - Public
|--------------------------------------------------------------------------
*/
Route::get('/card-login/{token}', CardLoginController::class)->name('card.login');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth');

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Schools
        Route::resource('schools', SchoolController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy','show']);

        // Students (Manual create)
        Route::resource('students', StudentController::class)->only(['index', 'create', 'store']);

        // ✅ Bulk Import XLSX
        Route::get('students/import', [StudentController::class, 'importForm'])
            ->name('students.import.form');

        Route::post('students/import', [StudentController::class, 'importStore'])
            ->name('students.import.store');

        Route::get('students/import/template', [StudentController::class, 'downloadTemplate'])
            ->name('students.import.template');

        // ✅ NEW: Student Cards (PDF + Rotate)
        Route::post('students/cards/pdf', [StudentCardController::class, 'pdf'])
            ->name('students.cards.pdf');

        Route::post('students/cards/rotate', [StudentCardController::class, 'rotate'])
            ->name('students.cards.rotate');

        // ✅ NEW: dependent sections endpoint (material -> sections)
        Route::get('materials/{material}/sections', function (\App\Models\Material $material) {
            return response()->json(
                $material->sections()
                    ->orderBy('title_en')
                    ->get(['id', 'title_en', 'title_ar', 'material_id'])
            );
        })->name('materials.sections');

        // Content Bank
        Route::resource('materials', MaterialController::class);
        Route::resource('sections', SectionController::class);
        Route::resource('lessons', LessonController::class);

        // ✅ IMPORTANT: Put filters BEFORE resource('questions')
        Route::get('questions/filters', [QuestionController::class, 'filters'])
            ->name('questions.filters');

        // ✅ Question Bank
        Route::resource('questions', QuestionController::class);

        // ✅ Lesson Attachments
        Route::post('lessons/{lesson}/attachments', [LessonAttachmentController::class, 'store'])
            ->name('lessons.attachments.store');

        Route::delete('lessons/{lesson}/attachments/{attachment}', [LessonAttachmentController::class, 'destroy'])
            ->name('lessons.attachments.destroy');

        Route::post('lessons/{lesson}/attachments/{attachment}/reextract', [LessonAttachmentController::class, 'reextract'])
            ->name('lessons.attachments.reextract');

        // AI Question Generator for a lesson
        Route::get('lessons/{lesson}/ai/questions', [\App\Http\Controllers\Admin\LessonAiQuestionController::class, 'create'])
            ->name('lessons.ai.questions.create');

        Route::post('lessons/{lesson}/ai/questions/generate', [\App\Http\Controllers\Admin\LessonAiQuestionController::class, 'generate'])
            ->name('lessons.ai.questions.generate');

        Route::post('lessons/{lesson}/ai/questions/save', [\App\Http\Controllers\Admin\LessonAiQuestionController::class, 'store'])
            ->name('lessons.ai.questions.store');

        /*
        |--------------------------------------------------------------------------
        | Learning Outcomes
        |--------------------------------------------------------------------------
        */
        Route::resource('learning_outcomes', LearningOutcomeController::class)
            ->only(['index','create','store','edit','update']);

        /*
        |--------------------------------------------------------------------------
        | Exams
        |--------------------------------------------------------------------------
        */
        Route::resource('exams', ExamController::class)->except(['destroy']);

        // Add EXISTING question to exam (Modal)
        Route::post('/exams/{exam}/questions', [ExamController::class, 'addQuestion'])
            ->name('exams.questions.add');

        // Remove question from exam
        Route::delete('/exams/{exam}/questions/{question}', [ExamController::class, 'removeQuestion'])
            ->name('exams.questions.remove');

        // Assignments
        Route::post('/exams/{exam}/assignments', [ExamController::class, 'createAssignment'])
            ->name('exams.assignments.create');

        // Overrides
        Route::post('/exams/{exam}/overrides', [ExamController::class, 'createOverride'])
            ->name('exams.overrides.create');

        // AJAX Picker for Exam Add Question Modal (cascading filters + questions)
        Route::get('/exams/{exam}/questions/picker', [ExamController::class, 'examQuestionsPicker'])
            ->name('exams.questions.picker');

        // ✅ FIX: Grades picker route used by exam show/edit UI
        Route::get('/exams/grades/picker', [ExamController::class, 'gradesPicker'])
            ->name('exams.grades.picker');

        /*
        |--------------------------------------------------------------------------
        | Exam Questions API (Admin – JSON)
        |--------------------------------------------------------------------------
        */
        Route::post('/exams/{exam}/questions/api', [ExamQuestionApiController::class, 'store'])
            ->name('exams.questions.api.store');

        /*
        |--------------------------------------------------------------------------
        | Attempts Grading
        |--------------------------------------------------------------------------
        */
        Route::get('/attempts/{attempt}', [AttemptGradingController::class, 'show'])
            ->name('attempts.show');

        Route::patch('/attempts/{attempt}/grade-essay', [AttemptGradingController::class, 'gradeEssay'])
            ->name('attempts.grade-essay');

        Route::post('/attempts/{attempt}/finalize-grading', [AttemptGradingController::class, 'finalizeGrading'])
            ->name('attempts.finalize-grading');
    });

/*
|--------------------------------------------------------------------------
| SCHOOL ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('school')
    ->middleware(['auth', 'role:school', 'tenant'])
    ->name('school.')
    ->group(function () {

        Route::get('/dashboard', [SchoolDashboardController::class, 'index'])->name('dashboard');

        Route::get('/exams', [SchoolExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/{exam}', [SchoolExamController::class, 'show'])->name('exams.show');
    });

/*
|--------------------------------------------------------------------------
| STUDENT ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('student')
    ->middleware(['auth', 'role:student', 'tenant'])
    ->name('student.')
    ->group(function () {

        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        // Help page
        Route::get('/help', fn () => view('student.help'))->name('help');

        // Exams
        Route::get('/exams', [StudentExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');

        Route::get('/exams/{exam}/intro', [ExamRoomController::class, 'showIntro'])->name('exams.intro');
        Route::get('/attempts/{attempt}/room', [ExamRoomController::class, 'room'])->name('attempts.room');

        // Questions API (Student)
        Route::get('/exams/{exam}/questions', [StudentExamQuestionApiController::class, 'index'])
            ->name('exams.questions');

        // Attempts (CSRF disabled intentionally)

        // ✅ FIX: start must accept GET + POST under the SAME route name (controllers may redirect to route('student.exams.start'))
        Route::match(['GET', 'POST'], 'exams/{exam}/start', [AttemptController::class, 'start'])
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
            ->name('exams.start');

        Route::post('attempts/{attempt}/heartbeat', [AttemptController::class, 'heartbeat'])
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
            ->name('attempts.heartbeat');

        Route::post('attempts/{attempt}/save', [AttemptController::class, 'save'])
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
            ->name('attempts.save');

        Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submit'])
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
            ->name('attempts.submit');

        Route::post('attempts/{attempt}/reset', [AttemptController::class, 'reset'])
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
            ->name('attempts.reset');
    });
