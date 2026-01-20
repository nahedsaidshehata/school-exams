{{-- resources/views/student/dashboard.blade.php --}}
@extends('layouts.student')

@section('title', __('Student Dashboard'))

@php
  // Flexible counts (works with either arrays or collections)
  $availableCount = $availableCount ?? (isset($availableExams) ? (is_countable($availableExams) ? count($availableExams) : 0) : 0);
  $submittedCount = $submittedCount ?? (isset($submittedExams) ? (is_countable($submittedExams) ? count($submittedExams) : 0) : 0);
  $expiredCount   = $expiredCount   ?? (isset($expiredExams) ? (is_countable($expiredExams) ? count($expiredExams) : 0) : 0);
@endphp

@section('content')
  {{-- Hero (like Admin Dashboard welcome card) --}}
  <div class="card student-card mb-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="stu-page-title h3 mb-1">
          {{ __('Welcome') }}, {{ auth()->user()->name ?? __('Student') }}
        </div>
        <div class="stu-subtitle">
          {{ __('Open your available exams and continue active attempts from one place.') }}
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('student.exams.index') }}" class="btn btn-outline-primary">
          {{ __('Browse Exams') }}
        </a>
        <a href="{{ route('student.exams.index') . '?status=available' }}" class="btn btn-primary">
          {{ __('Available Now') }}
        </a>
      </div>
    </div>
  </div>

  {{-- Stats cards (same sizing vibe as Admin cards) --}}
  <div class="row g-3">
    <div class="col-12 col-md-4">
      <div class="card student-card h-100">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted fw-semibold">{{ __('Available Exams') }}</div>
            <div class="display-6 fw-bold mb-0">{{ $availableCount }}</div>
          </div>
          <span class="badge badge-soft-success px-3 py-2">🟢 {{ __('Available') }}</span>
        </div>
        <div class="px-3 pb-3">
          <a href="{{ route('student.exams.index') . '?status=available' }}" class="btn btn-primary w-100">
            {{ __('View') }}
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card student-card h-100">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted fw-semibold">{{ __('Submitted') }}</div>
            <div class="display-6 fw-bold mb-0">{{ $submittedCount }}</div>
          </div>
          <span class="badge badge-soft-info px-3 py-2">📮 {{ __('Submitted') }}</span>
        </div>
        <div class="px-3 pb-3">
          <a href="{{ route('student.exams.index') . '?status=submitted' }}" class="btn btn-outline-primary w-100">
            {{ __('View') }}
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card student-card h-100">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted fw-semibold">{{ __('Expired') }}</div>
            <div class="display-6 fw-bold mb-0">{{ $expiredCount }}</div>
          </div>
          <span class="badge badge-soft-secondary px-3 py-2">⏳ {{ __('Expired') }}</span>
        </div>
        <div class="px-3 pb-3">
          <a href="{{ route('student.exams.index') . '?status=expired' }}" class="btn btn-outline-secondary w-100">
            {{ __('View') }}
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Quick Actions (like Admin shortcuts block) --}}
  <div class="card student-card mt-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="h5 fw-bold mb-1">{{ __('Quick Actions') }}</div>
        <div class="text-muted">
          {{ __('Go to your exams list and start an attempt when available.') }}
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('student.exams.index') }}" class="btn btn-outline-primary">
          {{ __('All Exams') }}
        </a>
        <a href="{{ route('student.exams.index') . '?status=available' }}" class="btn btn-primary">
          {{ __('Start Now') }}
        </a>
      </div>
    </div>
  </div>
@endsection
