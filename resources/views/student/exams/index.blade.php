{{-- resources/views/student/exams/index.blade.php --}}
@extends('layouts.student')

@section('title', __('Exams'))

@php
  $status = request('status', 'all');
  $q = request('q', '');

  // Flexible exams list
  $exams = $exams ?? $items ?? $data ?? [];
@endphp

@section('content')
  <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
      <h1 class="stu-page-title h3 mb-1">{{ __('Exams') }}</h1>
      <div class="stu-subtitle">{{ __('Find and open available exams. No grades are shown to students.') }}</div>
    </div>

    <form method="GET" action="{{ route('student.exams.index') }}" class="d-flex flex-wrap gap-2" style="max-width: 560px; width: 100%;">
      <input type="hidden" name="status" value="{{ $status }}">

      <div class="input-group">
        <span class="input-group-text">🔎</span>
        <input type="search"
               name="q"
               value="{{ $q }}"
               class="form-control"
               placeholder="{{ __('Search exams...') }}"
               aria-label="{{ __('Search exams...') }}">
      </div>

      <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
    </form>
  </div>

  {{-- Filter chips --}}
  <div class="d-flex flex-wrap gap-2 mb-4">
    @php
      $chips = [
        'all' => __('All'),
        'available' => __('Available'),
        'active' => __('Active'),
        'submitted' => __('Submitted'),
        'expired' => __('Expired'),
      ];
    @endphp

    @foreach($chips as $key => $label)
      <a class="filter-chip {{ $status === $key ? 'active' : '' }}"
         href="{{ route('student.exams.index') . '?status=' . urlencode($key) . '&q=' . urlencode($q) }}">
        {{ $label }}
      </a>
    @endforeach
  </div>

  {{-- Cards list --}}
  <div class="row g-3">
    @forelse($exams as $exam)
      @php
        $examId = $exam->id ?? $exam['id'] ?? null;

        $title = $exam->title ?? $exam['title'] ?? __('Exam');
        $duration = $exam->duration_minutes ?? $exam['duration_minutes'] ?? $exam->duration ?? $exam['duration'] ?? null;

        $windowStart = $exam->start_at ?? $exam['start_at'] ?? null;
        $windowEnd   = $exam->end_at ?? $exam['end_at'] ?? null;

        $badge = $exam->status ?? $exam['status'] ?? $status;
        $badgeClass = match($badge) {
          'available' => 'badge-soft-success',
          'active' => 'badge-soft-primary',
          'submitted' => 'badge-soft-info',
          'expired' => 'badge-soft-secondary',
          default => 'badge-soft-secondary'
        };

        $showUrl = $examId ? route('student.exams.show', $examId) : route('student.exams.index');
      @endphp

      <div class="col-12 col-lg-6">
        <div class="card student-card h-100">
          <div class="card-body">
            <div class="d-flex align-items-start justify-content-between gap-2">
              <div style="min-width:0">
                <div class="h5 fw-bold mb-1 text-truncate">{{ $title }}</div>

                <div class="text-muted small d-flex flex-wrap gap-3">
                  @if($duration)
                    <span>⏱ {{ $duration }} {{ __('min') }}</span>
                  @endif

                  @if($windowStart || $windowEnd)
                    <span>🗓
                      {{ $windowStart ? \Illuminate\Support\Carbon::parse($windowStart)->format('Y-m-d H:i') : '—' }}
                      —
                      {{ $windowEnd ? \Illuminate\Support\Carbon::parse($windowEnd)->format('Y-m-d H:i') : '—' }}
                    </span>
                  @endif
                </div>
              </div>

              <span class="badge {{ $badgeClass }} px-3 py-2">{{ ucfirst((string)$badge) }}</span>
            </div>

            <hr class="soft-divider my-3">

            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
              <div class="text-muted small">
                🔒 {{ __('Grades and correct answers are hidden.') }}
              </div>

              <a class="btn btn-primary" href="{{ $showUrl }}">
                {{ __('View Exam') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="card student-card">
          <div class="card-body text-center py-5">
            <div class="mb-2" style="font-size:34px;">🗂️</div>
            <div class="h5 fw-bold mb-1">{{ __('No exams found') }}</div>
            <div class="text-muted">{{ __('Try changing filters or search keywords.') }}</div>
          </div>
        </div>
      </div>
    @endforelse
  </div>
@endsection
