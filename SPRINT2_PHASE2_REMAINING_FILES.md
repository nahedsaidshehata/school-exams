# SPRINT 2 PHASE 2 - REMAINING VIEW FILES

This document contains the full content of all remaining Blade view files that need to be created.

---

## FILE: resources/views/admin/exams/show.blade.php

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $exam->title_en }}</h2>
        <div>
            <a href="{{ route('admin.exams.edit', $exam->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Exam Info -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Exam Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Title (Arabic):</strong> {{ $exam->title_ar }}</p>
                    <p><strong>Duration:</strong> {{ $exam->duration_minutes }} minutes</p>
                    <p><strong>Max Attempts:</strong> {{ $exam->max_attempts }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Start:</strong> {{ $exam->starts_at->format('M d, Y H:i') }}</p>
                    <p><strong>End:</strong> {{ $exam->ends_at->format('M d, Y H:i') }}</p>
                    <p><strong>Status:</strong> 
                        @if($exam->is_globally_locked)
                            <span class="badge badge-danger">🔒 Locked</span>
                        @else
                            <span class="badge badge-success">🔓 Unlocked</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Questions ({{ $exam->examQuestions->count() }})</h5>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addQuestionModal">
                Add Question
            </button>
        </div>
        <div class="card-body">
            @if($exam->examQuestions->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Question</th>
                            <th>Type</th>
                            <th>Difficulty</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exam->examQuestions as $examQuestion)
                            <tr>
                                <td>{{ $examQuestion->order_index }}</td>
                                <td>{{ \Str::limit($examQuestion->question->prompt_en, 50) }}</td>
                                <td><span class="badge badge-info">{{ $examQuestion->question->type }}</span></td>
                                <td><span class="badge badge-secondary">{{ $examQuestion->question->difficulty }}</span></td>
                                <td>{{ $examQuestion->points }}</td>
                                <td>
                                    <form action="{{ route('admin.exams.questions.remove', [$exam->id, $examQuestion->question_id]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this question?')">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right"><strong>Total Points:</strong></td>
                            <td colspan="2"><strong>{{ $exam->examQuestions->sum('points') }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <p class="text-muted">No questions added yet.</p>
            @endif
        </div>
    </div>

    <!-- Assignments Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Assignments ({{ $exam->assignments->count() }})</h5>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createAssignmentModal">
                Create Assignment
            </button>
        </div>
        <div class="card-body">
            @if($exam->assignments->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Target</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exam->assignments as $assignment)
                            <tr>
                                <td><span class="badge badge-primary">{{ $assignment->assignment_type }}</span></td>
                                <td>
                                    @if($assignment->assignment_type === 'SCHOOL')
                                        {{ $assignment->school->name_en }}
                                    @else
                                        {{ $assignment->student->full_name ?? $assignment->student->username }}
                                    @endif
                                </td>
                                <td>{{ $assignment->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No assignments created yet.</p>
            @endif
        </div>
    </div>

    <!-- Overrides Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Student Overrides ({{ $exam->overrides->count() }})</h5>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createOverrideModal">
                Add Override
            </button>
        </div>
        <div class="card-body">
            @if($exam->overrides->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Lock Mode</th>
                            <th>Extended Deadline</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exam->overrides as $override)
                            <tr>
                                <td>{{ $override->student->full_name ?? $override->student->username }}</td>
                                <td>
                                    @if($override->lock_mode === 'LOCK')
                                        <span class="badge badge-danger">LOCK</span>
                                    @elseif($override->lock_mode === 'UNLOCK')
                                        <span class="badge badge-success">UNLOCK</span>
                                    @else
                                        <span class="badge badge-secondary">DEFAULT</span>
                                    @endif
                                </td>
                                <td>{{ $override->override_ends_at ? $override->override_ends_at->format('M d, Y H:i') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No overrides created yet.</p>
            @endif
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.exams.questions.add', $exam->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Question to Exam</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="question_id">Select Question *</label>
                        <select class="form-control" id="question_id" name="question_id" required>
                            <option value="">-- Select Question --</option>
                            @foreach($availableQuestions as $question)
                                <option value="{{ $question->id }}">
                                    [{{ $question->type }}] {{ \Str::limit($question->prompt_en, 80) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="points">Points *</label>
                                <input type="number" class="form-control" id="points" name="points" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="order_index">Order Index *</label>
                                <input type="number" class="form-control" id="order_index" name="order_index" min="1" value="{{ $exam->examQuestions->count() + 1 }}" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Assignment Modal -->
<div class="modal fade" id="createAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.exams.assignments.create', $exam->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Assignment</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Assignment Type *</label>
                        <div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="type_school" name="assignment_type" value="SCHOOL" class="custom-control-input" checked>
                                <label class="custom-control-label" for="type_school">Entire School</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="type_student" name="assignment_type" value="STUDENT" class="custom-control-input">
                                <label class="custom-control-label" for="type_student">Specific Students</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="school_select">
                        <label for="school_id">Select School *</label>
                        <select class="form-control" id="school_id" name="school_id">
                            <option value="">-- Select School --</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name_en }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="student_select" style="display:none;">
                        <label for="student_ids">Select Students *</label>
                        <select class="form-control" id="student_ids" name="student_ids[]" multiple size="5">
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->full_name ?? $student->username }} ({{ $student->school->name_en ?? 'No School' }})</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple students</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Override Modal -->
<div class="modal fade" id="createOverrideModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.exams.overrides.create', $exam->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Student Override</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="student_id">Select Student *</label>
                        <select class="form-control" id="student_id" name="student_id" required>
                            <option value="">-- Select Student --</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->full_name ?? $student->username }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lock Mode *</label>
                        <div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="lock_default" name="lock_mode" value="DEFAULT" class="custom-control-input" checked>
                                <label class="custom-control-label" for="lock_default">DEFAULT (use exam's global setting)</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="lock_unlock" name="lock_mode" value="UNLOCK" class="custom-control-input">
                                <label class="custom-control-label" for="lock_unlock">UNLOCK (force unlock for this student)</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="lock_lock" name="lock_mode" value="LOCK" class="custom-control-input">
                                <label class="custom-control-label" for="lock_lock">LOCK (force lock for this student)</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="override_ends_at">Extended Deadline (optional)</label>
                        <input type="datetime-local" class="form-control" id="override_ends_at" name="override_ends_at">
                        <small class="form-text text-muted">Leave empty to use exam's end date</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Override</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeRadios = document.querySelectorAll('input[name="assignment_type"]');
    const schoolSelect = document.getElementById('school_select');
    const studentSelect = document.getElementById('student_select');

    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'SCHOOL') {
                schoolSelect.style.display = 'block';
                studentSelect.style.display = 'none';
                document.getElementById('school_id').required = true;
                document.getElementById('student_ids').required = false;
            } else {
                schoolSelect.style.display = 'none';
                studentSelect.style.display = 'block';
                document.getElementById('school_id').required = false;
                document.getElementById('student_ids').required = true;
            }
        });
    });
});
</script>
@endsection
```

---

## FILE: resources/views/school/exams/index.blade.php

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Assigned Exams</h2>

    @if($exams->count() > 0)
        <div class="row">
            @foreach($exams as $exam)
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $exam->title_en }}</h5>
                            <p class="card-text text-muted">{{ $exam->title_ar }}</p>
                            <hr>
                            <p><strong>Duration:</strong> {{ $exam->duration_minutes }} minutes</p>
                            <p><strong>Period:</strong> {{ $exam->starts_at->format('M d, Y H:i') }} - {{ $exam->ends_at->format('M d, Y H:i') }}</p>
                            <p><strong>Questions:</strong> {{ $exam->exam_questions_count }}</p>
                            <a href="{{ route('school.exams.show', $exam->id) }}" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">
            No exams assigned to your school yet.
        </div>
    @endif
</div>
@endsection
```

---

## FILE: resources/views/school/exams/show.blade.php

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $exam->title_en }}</h2>
        <a href="{{ route('school.exams.index') }}" class="btn btn-secondary">Back to Exams</a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Exam Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Title (Arabic):</strong> {{ $exam->title_ar }}</p>
                    <p><strong>Duration:</strong> {{ $exam->duration_minutes }} minutes</p>
                    <p><strong>Questions:</strong> {{ $exam->examQuestions->count() }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Start:</strong> {{ $exam->starts_at->format('M d, Y H:i') }}</p>
                    <p><strong>End:</strong> {{ $exam->ends_at->format('M d, Y H:i') }}</p>
                    <p><strong>Max Attempts:</strong> {{ $exam->max_attempts }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Questions Preview</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>Note:</strong> Correct answers and points are not visible to school users.
            </div>

            @foreach($exam->examQuestions as $examQuestion)
                <div class="mb-4 p-3 border rounded">
                    <h6>Question {{ $examQuestion->order_index }}</h6>
                    <p><strong>Type:</strong> <span class="badge badge-info">{{ $examQuestion->question->type }}</span></p>
                    <p><strong>Difficulty:</strong> <span class="badge badge-secondary">{{ $examQuestion->question->difficulty }}</span></p>
                    <p><strong>Prompt (EN):</strong> {{ $examQuestion->question->prompt_en }}</p>
                    <p><strong>Prompt (AR):</strong> {{ $examQuestion->question->prompt_ar }}</p>

                    @if($examQuestion->question->type === 'MCQ' || $examQuestion->question->type === 'TF')
                        <p><strong>Options:</strong></p>
                        <ul>
                            @foreach($examQuestion->question->options as $option)
                                <li>{{ $option->content_en }} / {{ $option->content_ar }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
```

---

## FILE: resources/views/student/exams/index.blade.php

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">My Exams</h2>

    @if($examsWithState->count() > 0)
        <div class="row">
            @foreach($examsWithState as $exam)
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title">{{ $exam->title_en }}</h5>
                                <span class="badge {{ $exam->state_badge }}">{{ $exam->state_icon }} {{ $exam->state }}</span>
                            </div>
                            <p class="card-text text-muted">{{ $exam->title_ar }}</p>
                            <hr>
                            <p><strong>Duration:</strong> {{ $exam->duration_minutes }} minutes</p>
                            <p><strong>Available:</strong> {{ $exam->starts_at->format('M d, Y H:i') }} - {{ $exam->ends_at->format('M d, Y H:i') }}</p>
                            <p><strong>Questions:</strong> {{ $exam->exam_questions_count }}</p>
                            <a href="{{ route('student.exams.show', $exam->id) }}" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">
            No exams assigned to you yet.
        </div>
    @endif
</div>
@endsection
```

---

## FILE: resources/views/student/exams/show.blade.php

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $exam->title_en }}</h2>
        <a href="{{ route('student.exams.index') }}" class="btn btn-secondary">Back to My Exams</a>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Exam Status</h5>
            <span class="badge {{ $stateBadge }} badge-lg">{{ $stateIcon }} {{ $state }}</span>
        </div>
        <div class="card-body">
            <div class="alert alert-{{ $state === 'AVAILABLE' ? 'success' : 'info' }}">
                {{ $stateMessage }}
            </div>

            <div class="row">
                <div class="col-md-6">
                    <p><strong>Title (Arabic):</strong> {{ $exam->title_ar }}</p>
                    <p><strong>Duration:</strong> {{ $exam->duration_minutes }} minutes</p>
                    <p><strong>Questions:</strong> {{ $exam->examQuestions->count() }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Start:</strong> {{ $exam->starts_at->format('M d, Y H:i') }}</p>
                    <p><strong>End:</strong> {{ $exam->ends_at->format('M d, Y H:i') }}</p>
                    @if($override && $override->override_ends_at)
                        <p><strong>Your Extended Deadline:</strong> {{ $override->override_ends_at->format('M d, Y H:i') }}</p>
                    @endif
                    <p><strong>Max Attempts:</strong> {{ $exam->max_attempts }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($state === 'AVAILABLE')
        <div class="card mb-4">
            <div class="card-header">
                <h5>Instructions</h5>
            </div>
            <div class="card-body">
                <ul>
                    <li>You have {{ $exam->duration_minutes }} minutes to complete this exam</li>
                    <li>You can take this exam up to {{ $exam->max_attempts }} times</li>
                    <li>Make sure you have a stable internet connection</li>
                    <li>Read each question carefully before answering</li>
                </ul>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5>Questions Preview</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>Note:</strong> Correct answers, points, and scores are not shown to students.
            </div>

            @foreach($exam->examQuestions as $examQuestion)
                <div class="mb-4 p-3 border rounded">
                    <h6>Question {{ $examQuestion->order_index }}</h6>
                    <p><strong>Type:</strong> <span class="badge badge-info">{{ $examQuestion->question->type }}</span></p>
                    <p><strong>Difficulty:</strong> <span class="badge badge-secondary">{{ $examQuestion->question->difficulty }}</span></p>
                    <p><strong>Prompt (EN):</strong> {{ $examQuestion->question->prompt_en }}</p>
                    <p><strong>Prompt (AR):</strong> {{ $examQuestion->question->prompt_ar }}</p>

                    @if($examQuestion->question->type === 'MCQ' || $examQuestion->question->type === 'TF')
                        <p><strong>Options:</strong></p>
                        <ul>
                            @foreach($examQuestion->question->options as $option)
                                <li>{{ $option->content_en }} / {{ $option->content_ar }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
```

---

## NOTES:

1. All views use Bootstrap 4 classes (already included in Sprint 1 layout)
2. CSRF tokens are included in all forms
3. School and Student views DO NOT show:
   - Correct answers (is_correct field)
   - Points per question
   - Any grading information
4. State badges use color coding:
   - LOCKED: red (badge-danger)
   - UPCOMING: blue (badge-info)
   - AVAILABLE: green (badge-success)
   - EXPIRED: gray (badge-secondary)
5. All forms include proper validation and error handling
6. Modals use Bootstrap's modal component
7. JavaScript for assignment type toggle is included inline
