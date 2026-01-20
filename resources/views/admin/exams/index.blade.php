{{-- resources/views/admin/exams/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Exams Management'))
@section('page_title', __('Exams'))
@section('page_subtitle')
  {{ __('Create, manage, and review exams and their configurations.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.exams.create') }}" class="btn btn-primary btn-sm">
    {{ __('Create Exam') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .table td, .table th { vertical-align: middle; }
      .truncate {
        max-width: 320px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .pill {
        display:inline-flex; align-items:center; gap:.35rem;
        padding:.35rem .55rem; border-radius:999px;
        font-size:.78rem; font-weight:800;
        border:1px solid rgba(0,0,0,.08);
        background: rgba(13,110,253,.08);
        color:#0d6efd;
      }
    </style>
  @endpush

  <div class="card admin-card">
    <div class="card-body">

      {{-- ✅ FILTER BANNER (Lesson) --}}
      @if(!empty($lessonId) && !empty($filterLesson))
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <div class="fw-semibold">
              {{ __('Filtered by lesson:') }}
              {{ $filterLesson->title_en ?? '-' }}
              <span class="text-muted">—</span>
              <span dir="rtl">{{ $filterLesson->title_ar ?? '-' }}</span>
            </div>
            <div class="text-muted small mt-1">
              {{ __('Subject:') }}
              {{ $filterLesson->section->material->name_en ?? '-' }}
              <span class="text-muted">—</span>
              <span dir="rtl">{{ $filterLesson->section->material->name_ar ?? '-' }}</span>
              <span class="mx-2">•</span>
              {{ __('Section:') }}
              {{ $filterLesson->section->title_en ?? '-' }}
              <span class="text-muted">—</span>
              <span dir="rtl">{{ $filterLesson->section->title_ar ?? '-' }}</span>
            </div>
          </div>

          <div class="d-flex gap-2">
            <a href="{{ route('admin.lessons.show', $filterLesson) }}" class="btn btn-outline-secondary btn-sm">
              {{ __('Back to Lesson') }}
            </a>
            <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-dark btn-sm">
              {{ __('Clear Filter') }}
            </a>
          </div>
        </div>
      @endif

      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="text-muted small">
          {{ __('Total:') }}
          <span class="fw-semibold">{{ method_exists($exams, 'total') ? $exams->total() : $exams->count() }}</span>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
          {{ __('Back to Dashboard') }}
        </a>
      </div>

      @if($exams->count() > 0)
        <div class="table-responsive">
          <table class="table admin-table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>{{ __('Title') }}</th>
                <th class="text-nowrap">{{ __('Duration') }}</th>
                <th class="text-nowrap">{{ __('Start') }}</th>
                <th class="text-nowrap">{{ __('End') }}</th>
                <th class="text-nowrap">{{ __('Questions') }}</th>
                <th class="text-nowrap">{{ __('Status') }}</th>
                <th class="text-nowrap text-end">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($exams as $exam)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $exam->title_en }}</div>
                    <div class="text-muted small" dir="rtl">{{ $exam->title_ar }}</div>
                  </td>

                  <td class="text-nowrap">
                    <span class="pill">{{ $exam->duration_minutes }} {{ __('min') }}</span>
                  </td>

                  <td class="text-nowrap">
                    {{ optional($exam->starts_at)->format('M d, Y H:i') }}
                  </td>

                  <td class="text-nowrap">
                    {{ optional($exam->ends_at)->format('M d, Y H:i') }}
                  </td>

                  <td class="text-nowrap">
                    <span class="badge text-bg-info">
                      {{ $exam->exam_questions_count }} {{ __('questions') }}
                    </span>
                  </td>

                  <td class="text-nowrap">
                    @if($exam->is_globally_locked)
                      <span class="badge text-bg-danger">🔒 {{ __('Locked') }}</span>
                    @else
                      <span class="badge text-bg-success">🔓 {{ __('Unlocked') }}</span>
                    @endif
                  </td>

                  <td class="text-end text-nowrap">
                    <div class="d-inline-flex gap-2">
                      <a href="{{ route('admin.exams.show', $exam->id) }}" class="btn btn-outline-info btn-sm">
                        {{ __('View') }}
                      </a>
                      <a href="{{ route('admin.exams.edit', $exam->id) }}" class="btn btn-outline-warning btn-sm">
                        {{ __('Edit') }}
                      </a>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
          {{ $exams->links() }}
        </div>
      @else
        <div class="alert alert-info mb-0">
          {{ __('No exams found.') }}
          <a class="ms-1" href="{{ route('admin.exams.create') }}">{{ __('Create your first exam') }}</a>
        </div>
      @endif
    </div>
  </div>
@endsection
