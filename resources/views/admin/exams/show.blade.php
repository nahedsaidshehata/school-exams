{{-- resources/views/admin/exams/show.blade.php.php --}}
@extends('layouts.admin')

@section('title', $exam->title_en)
@section('page_title', $exam->title_en)
@section('page_subtitle')
  <span dir="rtl">{{ $exam->title_ar }}</span>
@endsection

@section('page_actions')
  <a href="{{ route('admin.exams.edit', $exam->id) }}" class="btn btn-outline-warning btn-sm">
    {{ __('Edit') }}
  </a>
  <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back to List') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .info-kv { margin: 0; }
      .info-kv strong { display:inline-block; min-width: 130px; }
      .table td, .table th { vertical-align: middle; }
      .truncate { max-width: 420px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
      .section-card .card-header {
        background: rgba(13,110,253,.06);
        border-bottom: 1px solid rgba(0,0,0,.06);
        font-weight: 800;
      }
    </style>
  @endpush

  {{-- Exam Info --}}
  <div class="card admin-card section-card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between gap-2">
      <span>{{ __('Exam Information') }}</span>
      <div>
        @if($exam->is_globally_locked)
          <span class="badge text-bg-danger">🔒 {{ __('Locked') }}</span>
        @else
          <span class="badge text-bg-success">🔓 {{ __('Unlocked') }}</span>
        @endif
      </div>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-lg-6">
          <p class="info-kv"><strong>{{ __('Title (Arabic)') }}:</strong> <span dir="rtl">{{ $exam->title_ar }}</span></p>
          <p class="info-kv"><strong>{{ __('Duration') }}:</strong> {{ $exam->duration_minutes }} {{ __('minutes') }}</p>
          <p class="info-kv"><strong>{{ __('Max Attempts') }}:</strong> {{ $exam->max_attempts }}</p>
        </div>
        <div class="col-12 col-lg-6">
          <p class="info-kv"><strong>{{ __('Start') }}:</strong> {{ optional($exam->starts_at)->format('M d, Y H:i') }}</p>
          <p class="info-kv"><strong>{{ __('End') }}:</strong> {{ optional($exam->ends_at)->format('M d, Y H:i') }}</p>
          <p class="info-kv"><strong>{{ __('Questions') }}:</strong> {{ $exam->examQuestions->count() }}</p>
        </div>
      </div>
    </div>
  </div>

  {{-- Questions --}}
  <div class="card admin-card section-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <span>{{ __('Questions') }} ({{ $exam->examQuestions->count() }})</span>
      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
        {{ __('Add Question') }}
      </button>
    </div>
    <div class="card-body">
      @if($exam->examQuestions->count() > 0)
        <div class="table-responsive">
          <table class="table admin-table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="text-nowrap">{{ __('Order') }}</th>
                <th>{{ __('Question') }}</th>
                <th class="text-nowrap">{{ __('Type') }}</th>
                <th class="text-nowrap">{{ __('Difficulty') }}</th>
                <th class="text-nowrap">{{ __('Points') }}</th>
                <th class="text-nowrap text-end">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($exam->examQuestions as $examQuestion)
                <tr>
                  <td class="text-nowrap">{{ $examQuestion->order_index }}</td>
                  <td class="truncate" title="{{ $examQuestion->question->prompt_en ?? '' }}">
                    {{ \Illuminate\Support\Str::limit($examQuestion->question->prompt_en ?? '', 70) }}
                  </td>
                  <td class="text-nowrap">
                    <span class="badge text-bg-info">{{ $examQuestion->question->type }}</span>
                  </td>
                  <td class="text-nowrap">
                    <span class="badge text-bg-secondary">{{ $examQuestion->question->difficulty }}</span>
                  </td>
                  <td class="text-nowrap">{{ $examQuestion->points }}</td>
                  <td class="text-end text-nowrap">
                    <form action="{{ route('admin.exams.questions.remove', [$exam->id, $examQuestion->question_id]) }}" method="POST" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Remove this question?') }}')">
                        {{ __('Remove') }}
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <td colspan="4" class="text-end fw-semibold">{{ __('Total Points:') }}</td>
                <td colspan="2" class="fw-semibold">{{ $exam->examQuestions->sum('points') }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      @else
        <div class="text-muted">{{ __('No questions added yet.') }}</div>
      @endif
    </div>
  </div>

  {{-- Assignments --}}
  <div class="card admin-card section-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <span>{{ __('Assignments') }} ({{ $exam->assignments->count() }})</span>
      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
        {{ __('Create Assignment') }}
      </button>
    </div>
    <div class="card-body">
      @if($exam->assignments->count() > 0)
        <div class="table-responsive">
          <table class="table admin-table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="text-nowrap">{{ __('Type') }}</th>
                <th>{{ __('Target') }}</th>
                <th class="text-nowrap">{{ __('Created') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($exam->assignments as $assignment)
                <tr>
                  <td class="text-nowrap">
                    <span class="badge text-bg-primary">{{ $assignment->assignment_type }}</span>
                  </td>
                  <td>
                    @if($assignment->assignment_type === 'SCHOOL')
                      {{ $assignment->school->name_en ?? 'N/A' }}
                    @elseif($assignment->assignment_type === 'GRADE')
                      {{ ($assignment->school->name_en ?? 'School') }} — {{ __('Grade') }}: {{ $assignment->grade ?? '-' }}
                    @else
                      {{ $assignment->student->full_name ?? $assignment->student->username ?? 'N/A' }}
                      @if(!empty($assignment->grade))
                        <span class="text-muted">— {{ __('Grade') }}: {{ $assignment->grade }}</span>
                      @endif
                    @endif
                  </td>
                  <td class="text-nowrap">{{ optional($assignment->created_at)->format('M d, Y') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-muted">{{ __('No assignments created yet.') }}</div>
      @endif
    </div>
  </div>

  {{-- Overrides --}}
  <div class="card admin-card section-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <span>{{ __('Student Overrides') }} ({{ $exam->overrides->count() }})</span>
      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createOverrideModal">
        {{ __('Add Override') }}
      </button>
    </div>
    <div class="card-body">
      @if($exam->overrides->count() > 0)
        <div class="table-responsive">
          <table class="table admin-table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>{{ __('Student') }}</th>
                <th class="text-nowrap">{{ __('Lock Mode') }}</th>
                <th class="text-nowrap">{{ __('Extended Deadline') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($exam->overrides as $override)
                <tr>
                  <td>{{ $override->student->full_name ?? $override->student->username ?? 'N/A' }}</td>
                  <td class="text-nowrap">
                    @if($override->lock_mode === 'LOCK')
                      <span class="badge text-bg-danger">LOCK</span>
                    @elseif($override->lock_mode === 'UNLOCK')
                      <span class="badge text-bg-success">UNLOCK</span>
                    @else
                      <span class="badge text-bg-secondary">DEFAULT</span>
                    @endif
                  </td>
                  <td class="text-nowrap">
                    {{ $override->override_ends_at ? $override->override_ends_at->format('M d, Y H:i') : '-' }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-muted">{{ __('No overrides created yet.') }}</div>
      @endif
    </div>
  </div>

  {{-- ===================== Modals (Bootstrap 5) ===================== --}}

  {{-- Add Question Modal --}}
  <div class="modal fade" id="addQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title">{{ __('Add Question to Exam') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
        </div>

        <div class="modal-body">
          <div id="addQuestionAjaxAlert" class="alert alert-danger d-none mb-3"></div>

          {{-- Filters --}}
          <div class="card mb-3" style="border-radius:14px;">
            <div class="card-body">
              <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-4">
                  <label class="form-label">{{ __('Search') }}</label>
                  <input type="text" class="form-control" id="aq_search" placeholder="{{ __('Search prompt, lesson, metadata...') }}">
                </div>

                <div class="col-6 col-lg-2">
                  <label class="form-label">{{ __('Type') }}</label>
                  <select class="form-select" id="aq_type">
                    <option value="">{{ __('All') }}</option>
                    @foreach(['MCQ','TF','ESSAY','CLASSIFICATION','REORDER','FILL_BLANK'] as $t)
                      <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="col-6 col-lg-2">
                  <label class="form-label">{{ __('Difficulty') }}</label>
                  <select class="form-select" id="aq_difficulty">
                    <option value="">{{ __('All') }}</option>
                    @foreach(['EASY','MEDIUM','HARD'] as $d)
                      <option value="{{ $d }}">{{ ucfirst(strtolower($d)) }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="col-6 col-lg-2">
                  <label class="form-label">{{ __('Subject') }}</label>
                  <select class="form-select" id="aq_subject">
                    <option value="">{{ __('All') }}</option>
                    @foreach(($subjects ?? []) as $subj)
                      <option value="{{ $subj->id }}">{{ $subj->name_en ?? $subj->name_ar ?? 'Subject' }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="col-6 col-lg-2">
                  <label class="form-label">{{ __('Section') }}</label>
                  <select class="form-select" id="aq_section" disabled>
                    <option value="">{{ __('All') }}</option>
                  </select>
                </div>

                <div class="col-12 col-lg-6">
                  <label class="form-label">{{ __('Lesson') }}</label>
                  <select class="form-select" id="aq_lesson" disabled>
                    <option value="">{{ __('All') }}</option>
                  </select>
                </div>

                <div class="col-12 col-lg-6 d-flex justify-content-end gap-2">
                  <button type="button" class="btn btn-primary" id="aq_apply">
                    {{ __('Apply Filters') }}
                  </button>
                  <button type="button" class="btn btn-outline-secondary" id="aq_reset">
                    {{ __('Reset') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          {{-- Add Form --}}
          <form id="addQuestionToExamForm" action="{{ route('admin.exams.questions.add', $exam->id) }}" method="POST">
            @csrf

            <div class="mb-2 d-flex justify-content-between align-items-center">
              <label for="add_question_id" class="form-label mb-0">{{ __('Select Question') }} *</label>
              <div class="form-text" id="aq_selected_hint">Selected: 0</div>
            </div>

            <div class="mb-3">
              <select class="form-select" id="add_question_id" name="question_ids[]" multiple size="10" required>
                <option value="">{{ __('-- Select Question --') }}</option>
              </select>
              <div class="form-text" id="aq_hint">{{ __('Use filters to load questions...') }}</div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label for="add_points" class="form-label">{{ __('Points') }} *</label>
                <input type="number" class="form-control" id="add_points" name="points" step="0.01" min="0.01" required>
              </div>
              <div class="col-md-6">
                <label for="add_order_index" class="form-label">{{ __('Order Index') }} *</label>
                <input type="number" class="form-control" id="add_order_index" name="order_index" min="1" value="{{ $exam->examQuestions->count() + 1 }}" required>
              </div>
            </div>

            <div class="modal-footer px-0 mt-3">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
              <button type="submit" class="btn btn-primary">{{ __('Add Question') }}</button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>

  {{-- Create Assignment Modal --}}
  <div class="modal fade" id="createAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('admin.exams.assignments.create', $exam->id) }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">{{ __('Create Assignment') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
          </div>

          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">{{ __('Assignment Type') }} *</label>
              <div class="d-grid gap-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="type_school" name="assignment_type" value="SCHOOL" checked>
                  <label class="form-check-label" for="type_school">{{ __('Entire School') }}</label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="radio" id="type_grade" name="assignment_type" value="GRADE">
                  <label class="form-check-label" for="type_grade">{{ __('Specific Grade (in a School)') }}</label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="radio" id="type_student" name="assignment_type" value="STUDENT">
                  <label class="form-check-label" for="type_student">{{ __('Specific Students') }}</label>
                </div>
              </div>
            </div>

            {{-- SCHOOL (also used for GRADE) --}}
            <div class="mb-3" id="school_select">
              <label for="school_id" class="form-label">{{ __('Select School') }} *</label>
              <select class="form-select" id="school_id" name="school_id">
                <option value="">{{ __('-- Select School --') }}</option>
                @foreach($schools as $school)
                  <option value="{{ $school->id }}">{{ $school->name_en }}</option>
                @endforeach
              </select>
            </div>

            {{-- GRADE (AJAX) --}}
            <div class="mb-3" id="grade_select" style="display:none;">
              <label for="grade" class="form-label">{{ __('Select Grade') }} *</label>
              <select class="form-select" id="grade" name="grade" disabled>
                <option value="">{{ __('-- Select Grade --') }}</option>
              </select>
              <div class="form-text" id="grade_hint">{{ __('Select a school to load grades...') }}</div>
            </div>

            {{-- STUDENTS --}}
            <div class="mb-3" id="student_select" style="display:none;">
              <label for="student_ids" class="form-label">{{ __('Select Students') }} *</label>
              <select class="form-select" id="student_ids" name="student_ids[]" multiple size="8">
                @foreach($students as $student)
                  <option value="{{ $student->id }}">
                    {{ $student->full_name ?? $student->username }}
                    ({{ $student->school->name_en ?? 'No School' }})
                  </option>
                @endforeach
              </select>
              <div class="form-text">{{ __('Hold Ctrl/Cmd to select multiple students') }}</div>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Create Assignment') }}</button>
          </div>

        </form>
      </div>
    </div>
  </div>

  {{-- Create Override Modal --}}
  <div class="modal fade" id="createOverrideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('admin.exams.overrides.create', $exam->id) }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">{{ __('Add Student Override') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="student_id" class="form-label">{{ __('Select Student') }} *</label>
              <select class="form-select" id="student_id" name="student_id" required>
                <option value="">{{ __('-- Select Student --') }}</option>
                @foreach($students as $student)
                  <option value="{{ $student->id }}">{{ $student->full_name ?? $student->username }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">{{ __('Lock Mode') }} *</label>
              <div class="d-grid gap-2">
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="lock_default" name="lock_mode" value="DEFAULT" checked>
                  <label class="form-check-label" for="lock_default">{{ __("DEFAULT (use exam's global setting)") }}</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="lock_unlock" name="lock_mode" value="UNLOCK">
                  <label class="form-check-label" for="lock_unlock">{{ __('UNLOCK (force unlock for this student)') }}</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="lock_lock" name="lock_mode" value="LOCK">
                  <label class="form-check-label" for="lock_lock">{{ __('LOCK (force lock for this student)') }}</label>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label for="override_ends_at" class="form-label">{{ __('Extended Deadline (optional)') }}</label>
              <input type="datetime-local" class="form-control" id="override_ends_at" name="override_ends_at">
              <div class="form-text">{{ __("Leave empty to use exam's end date") }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Save Override') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {

      // ================= Assignment UI (School / Grade / Students) =================
      const typeRadios   = document.querySelectorAll('input[name="assignment_type"]');
      const schoolWrap   = document.getElementById('school_select');
      const gradeWrap    = document.getElementById('grade_select');
      const studentWrap  = document.getElementById('student_select');

      const schoolId     = document.getElementById('school_id');
      const gradeEl      = document.getElementById('grade');
      const gradeHint    = document.getElementById('grade_hint');
      const studentIds   = document.getElementById('student_ids');

      // ✅ IMPORTANT: this must match your route name after fixing routes
      const gradesPickerUrl = @json(route('admin.exams.grades.picker', $exam->id));

      function setGradeOptions(grades) {
        if (!gradeEl) return;

        gradeEl.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = '-- Select Grade --';
        gradeEl.appendChild(ph);

        (grades || []).forEach(g => {
          const opt = document.createElement('option');
          opt.value = g;
          opt.textContent = g;
          gradeEl.appendChild(opt);
        });
      }

      async function loadGradesForSchool() {
        if (!schoolId || !gradeEl) return;

        const sid = (schoolId.value || '').trim();

        // reset always
        setGradeOptions([]);
        gradeEl.value = '';
        gradeEl.disabled = true;

        if (!sid) {
          if (gradeHint) gradeHint.textContent = 'Select a school to load grades...';
          return;
        }

        try {
          if (gradeHint) gradeHint.textContent = 'Loading grades...';

          const url = new URL(gradesPickerUrl, window.location.origin);
          url.searchParams.set('school_id', sid);

          const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          if (!res.ok) throw new Error('Request failed');

          const data = await res.json();

          const grades = data.grades || [];
          setGradeOptions(grades);

          gradeEl.disabled = false;

          if (gradeHint) {
            gradeHint.textContent = (grades.length > 0)
              ? `Loaded ${grades.length} grade(s)`
              : 'No grades found for this school.';
          }
        } catch (e) {
          if (gradeHint) gradeHint.textContent = 'Failed to load grades. Check route/controller.';
        }
      }

      function syncAssignmentUI(val) {
        // hide all
        if (schoolWrap) schoolWrap.style.display = 'none';
        if (gradeWrap) gradeWrap.style.display = 'none';
        if (studentWrap) studentWrap.style.display = 'none';

        // required defaults
        if (schoolId) schoolId.required = false;
        if (gradeEl) gradeEl.required = false;
        if (studentIds) studentIds.required = false;

        if (val === 'SCHOOL') {
          if (schoolWrap) schoolWrap.style.display = 'block';
          if (schoolId) schoolId.required = true;

          // reset grade
          if (gradeEl) {
            gradeEl.value = '';
            gradeEl.disabled = true;
          }
          if (gradeHint) gradeHint.textContent = 'Select a school to load grades...';

        } else if (val === 'GRADE') {
          if (schoolWrap) schoolWrap.style.display = 'block';
          if (gradeWrap) gradeWrap.style.display = 'block';
          if (schoolId) schoolId.required = true;
          if (gradeEl) gradeEl.required = true;

          // load grades (if school already selected)
          loadGradesForSchool();

        } else { // STUDENT
          if (studentWrap) studentWrap.style.display = 'block';
          if (studentIds) studentIds.required = true;

          // reset school/grade to avoid sending stale values
          if (schoolId) schoolId.value = '';
          if (gradeEl) {
            gradeEl.value = '';
            gradeEl.disabled = true;
          }
          if (gradeHint) gradeHint.textContent = 'Select a school to load grades...';
        }
      }

      typeRadios.forEach(radio => {
        radio.addEventListener('change', function () {
          syncAssignmentUI(this.value);
        });
      });

      const checked = document.querySelector('input[name="assignment_type"]:checked');
      if (checked) syncAssignmentUI(checked.value);

      // when school changes and we are in GRADE mode -> reload grades
      if (schoolId) {
        schoolId.addEventListener('change', function () {
          const currentType = document.querySelector('input[name="assignment_type"]:checked')?.value;
          if (currentType === 'GRADE') {
            loadGradesForSchool();
          }
        });
      }

      // ================= Add Question Modal - Filters + Load Questions =================
      const pickerUrl = @json(route('admin.exams.questions.picker', $exam->id));

      const aqSearch     = document.getElementById('aq_search');
      const aqType       = document.getElementById('aq_type');
      const aqDifficulty = document.getElementById('aq_difficulty');
      const aqSubject    = document.getElementById('aq_subject');
      const aqSection    = document.getElementById('aq_section');
      const aqLesson     = document.getElementById('aq_lesson');
      const aqApply      = document.getElementById('aq_apply');
      const aqReset      = document.getElementById('aq_reset');

      const questionSelect = document.getElementById('add_question_id');
      const aqHint = document.getElementById('aq_hint');
      const aqSelectedHint = document.getElementById('aq_selected_hint');

      function updateSelectedCount(){
        if (!questionSelect || !aqSelectedHint) return;
        aqSelectedHint.textContent = `Selected: ${questionSelect.selectedOptions.length}`;
      }

      if (questionSelect) {
        questionSelect.addEventListener('change', updateSelectedCount);
      }

      function setOptions(selectEl, items, placeholder, getLabel) {
        const current = selectEl.value;
        selectEl.innerHTML = '';

        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = placeholder;
        selectEl.appendChild(ph);

        (items || []).forEach(item => {
          const opt = document.createElement('option');
          opt.value = item.id;
          opt.textContent = getLabel(item);
          selectEl.appendChild(opt);
        });

        if ([...selectEl.options].some(o => o.value === current)) {
          selectEl.value = current;
        }
      }

      async function loadPickerData({ resetSectionLesson = false, resetLessonOnly = false } = {}) {
        const url = new URL(pickerUrl, window.location.origin);

        const q = (aqSearch?.value || '').trim();
        if (q) url.searchParams.set('q', q);

        if (aqType?.value) url.searchParams.set('type', aqType.value);
        if (aqDifficulty?.value) url.searchParams.set('difficulty', aqDifficulty.value);

        if (aqSubject?.value) url.searchParams.set('material_id', aqSubject.value);
        if (aqSection?.value) url.searchParams.set('section_id', aqSection.value);
        if (aqLesson?.value) url.searchParams.set('lesson_id', aqLesson.value);

        aqHint.textContent = 'Loading...';
        const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();

        setOptions(aqSection, data.sections, 'All', (s) => (s.title_en || s.title_ar || 'Section'));
        aqSection.disabled = !(aqSubject?.value);

        if (resetSectionLesson) {
          aqSection.value = '';
          aqLesson.value = '';
        }

        setOptions(aqLesson, data.lessons, 'All', (l) => (l.title_en || l.title_ar || 'Lesson'));
        aqLesson.disabled = !(aqSubject?.value || aqSection?.value);

        if (resetLessonOnly) {
          aqLesson.value = '';
        }

        setOptions(questionSelect, data.questions, '-- Select Question --', (qq) => {
          const prompt = (qq.prompt_en || qq.prompt_ar || '').slice(0, 90);
          return `[${qq.type}] (${qq.difficulty}) ${prompt}`;
        });
        updateSelectedCount();

        aqHint.textContent = `Loaded: ${data.questions?.length || 0} question(s)`;
      }

      let searchTimer = null;
      if (aqSearch) {
        aqSearch.addEventListener('input', () => {
          clearTimeout(searchTimer);
          searchTimer = setTimeout(() => loadPickerData(), 350);
        });
      }

      if (aqSubject) {
        aqSubject.addEventListener('change', async () => {
          await loadPickerData({ resetSectionLesson: true });
        });
      }

      if (aqSection) {
        aqSection.addEventListener('change', async () => {
          await loadPickerData({ resetLessonOnly: true });
        });
      }

      if (aqLesson) aqLesson.addEventListener('change', () => loadPickerData());
      if (aqType) aqType.addEventListener('change', () => loadPickerData());
      if (aqDifficulty) aqDifficulty.addEventListener('change', () => loadPickerData());

      if (aqApply) aqApply.addEventListener('click', () => loadPickerData());

      if (aqReset) {
        aqReset.addEventListener('click', async () => {
          aqSearch.value = '';
          aqType.value = '';
          aqDifficulty.value = '';
          aqSubject.value = '';
          aqSection.value = '';
          aqLesson.value = '';
          aqSection.disabled = true;
          aqLesson.disabled = true;
          await loadPickerData({ resetSectionLesson: true });
        });
      }

      const addModalEl = document.getElementById('addQuestionModal');
      if (addModalEl) {
        addModalEl.addEventListener('shown.bs.modal', () => {
          loadPickerData();
        });
      }

      // ================= AJAX SUBMISSION FOR ADD QUESTION FORM =================
      const addQuestionForm = document.getElementById('addQuestionToExamForm');
      const ajaxAlert = document.getElementById('addQuestionAjaxAlert');

      if (addQuestionForm && ajaxAlert) {
        addQuestionForm.addEventListener('submit', function (e) {
          e.preventDefault();

          ajaxAlert.classList.add('d-none');
          ajaxAlert.innerHTML = '';

          const formData = new FormData(addQuestionForm);

          formData.delete('question_id');
          formData.delete('question_ids[]');

          [...questionSelect.selectedOptions].forEach(opt => {
            if (opt.value) formData.append('question_ids[]', opt.value);
          });

          const submitBtn = addQuestionForm.querySelector('button[type="submit"]');
          const originalBtnText = submitBtn ? submitBtn.innerHTML : '';

          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Adding...';
          }

          fetch(addQuestionForm.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
          .then(async (response) => {
            if (response.ok || response.status === 302) {
              location.reload();
              return;
            }

            if (response.status === 422) {
              const data = await response.json().catch(() => ({}));
              throw { validation: true, errors: data.errors || {}, message: data.message || '' };
            }

            throw { validation: false, message: 'Server error occurred' };
          })
          .catch((error) => {
            if (error.validation) {
              let errorHtml = '<ul class="mb-0">';
              if (error.message) errorHtml += `<li>${error.message}</li>`;
              for (let field in (error.errors || {})) {
                (error.errors[field] || []).forEach((msg) => {
                  errorHtml += `<li>${msg}</li>`;
                });
              }
              errorHtml += '</ul>';
              ajaxAlert.innerHTML = errorHtml;
            } else {
              ajaxAlert.innerHTML = error.message || 'An error occurred. Please try again.';
            }

            ajaxAlert.classList.remove('d-none');
          })
          .finally(() => {
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalBtnText;
            }
          });
        });
      }

    });
    </script>
  @endpush
@endsection
