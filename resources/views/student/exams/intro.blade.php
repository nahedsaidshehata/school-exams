{{-- resources/views/student/exams/intro.blade.php --}}
@extends('layouts.student')

@section('title', __('Exam Instructions'))

@php
  $examObj = $exam ?? $item ?? null;
  $examId = $examObj->id ?? $examObj['id'] ?? request()->route('exam') ?? null;

  $title = $examObj->title ?? $examObj['title'] ?? __('Exam');
  $duration = $examObj->duration_minutes ?? $examObj['duration_minutes'] ?? $examObj->duration ?? $examObj['duration'] ?? null;

  // attempts
  $attemptsLimit = $attemptsLimit ?? ($examObj->max_attempts ?? $examObj['max_attempts'] ?? $examObj->attempts_limit ?? $examObj['attempts_limit'] ?? null);
  $attemptsUsed  = $attemptsUsed  ?? ($attemptCount ?? ($examObj->attempts_used ?? $examObj['attempts_used'] ?? null));
  $attemptsRemaining = $attemptsRemaining ?? (is_numeric($attemptsLimit) && is_numeric($attemptsUsed) ? max(0, $attemptsLimit - $attemptsUsed) : null);

  $activeAttempt = $activeAttempt ?? null;
  $activeAttemptId = $activeAttempt->id ?? $activeAttempt['id'] ?? null;

  $canStart = $canStart ?? (is_null($attemptsRemaining) ? true : ($attemptsRemaining > 0));

  // routes
  $startUrl = $examId ? route('student.exams.start', $examId) : route('student.exams.index');
  $backUrl  = $examId ? route('student.exams.show', $examId) : route('student.exams.index');
  $roomUrl  = $activeAttemptId ? route('student.attempts.room', ['attempt' => $activeAttemptId]) : null;
@endphp

@section('content')
  @push('head')
    <style>
      .instruction-list { padding-inline-start: 1.2rem; }
      .instruction-list li { margin-bottom: .5rem; color: rgba(15,23,42,.82); }
      .btn-wide { min-width: 190px; }
      .muted-mini { font-size: .92rem; color: rgba(15,23,42,.62); }
      .soft-divider { border-top: 1px dashed rgba(15,23,42,.14); }
      .sp-card-title { font-weight: 900; font-size: 1.02rem; letter-spacing: .2px; }
    </style>
  @endpush

  @if($roomUrl)
    @push('scripts')
      <script>
        window.addEventListener('load', function () {
          try {
            setTimeout(function () {
              window.location.href = @json($roomUrl);
            }, 350);
          } catch (e) {}
        });
      </script>
    @endpush
  @endif

  <div class="card student-card mb-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="stu-page-title h3 mb-1">{{ $title }}</div>
        <div class="stu-subtitle">{{ __('Please read the instructions before starting.') }}</div>
      </div>
      <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
        {{ __('Back') }}
      </a>
    </div>
  </div>

  <div class="card student-card mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="badge badge-soft-primary">⏱ {{ $duration ? $duration.' '.__('min') : '—' }}</span>

        @if(!is_null($attemptsLimit))
          <span class="badge badge-soft-secondary">🎯 {{ __('Attempts') }}: {{ $attemptsLimit }}</span>
        @endif
        @if(!is_null($attemptsUsed))
          <span class="badge badge-soft-secondary">📌 {{ __('Used') }}: {{ $attemptsUsed }}</span>
        @endif
        @if(!is_null($attemptsRemaining))
          <span class="badge badge-soft-success">✅ {{ __('Remaining') }}: {{ $attemptsRemaining }}</span>
        @endif

        <span class="badge badge-soft-primary">🔒 {{ __('Correct answers are hidden') }}</span>

        @if($roomUrl)
          <span class="badge badge-soft-success">🟢 {{ __('In progress') }}</span>
        @endif
      </div>

      @if($roomUrl)
        <div class="mt-2 muted-mini">
          {{ __('You have an active attempt. Redirecting you to the exam room...') }}
        </div>
      @else
        <div class="mt-2 muted-mini">
          {{ __('You can start the exam now. Once started, do not close the tab until you finish.') }}
        </div>
      @endif
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="card student-card">
        <div class="card-body">
          <div class="sp-card-title mb-2">{{ __('Instructions') }}</div>

          <ul class="instruction-list mb-0">
            <li>{{ __('Do not refresh the page during the exam.') }}</li>
            <li>{{ __('Your answers are saved automatically.') }}</li>
            <li>{{ __('If time ends, your attempt will be submitted automatically.') }}</li>
            <li>{{ __('Grades and correct answers are not shown to students.') }}</li>
          </ul>

          <hr class="soft-divider my-3">

          <div class="d-flex flex-wrap gap-2 align-items-center">
            @if($roomUrl)
              <a href="{{ $roomUrl }}" class="btn btn-primary btn-wide">
                {{ __('Continue Exam') }}
              </a>
            @else
              <form method="POST" action="{{ $startUrl }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-primary btn-wide" @if(!$canStart) disabled @endif>
                  {{ __('Start Exam') }}
                </button>
              </form>
            @endif

            <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-wide">
              {{ __('Back') }}
            </a>
          </div>

          @if(!$roomUrl && !$canStart)
            <div class="text-danger small mt-2">
              {{ __('You have reached the maximum number of attempts for this exam.') }}
            </div>
          @endif

          <div class="text-muted small mt-3">
            {{ __('No grades will be shown after submission.') }}
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card student-card">
        <div class="card-body">
          <div class="sp-card-title mb-2">{{ __('Before you start') }}</div>
          <div class="text-muted small">
            {{ __('Make sure you have a stable internet connection. Keep this tab open until you finish.') }}
          </div>

          <hr class="soft-divider my-3">

          <div class="small text-muted">
            <div class="mb-2">🔒 {{ __('Correct answers are hidden.') }}</div>
            <div class="mb-2">🧠 {{ __('Focus on answering all questions.') }}</div>
            <div>⚡ {{ __('Auto-save is enabled.') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
