{{-- resources/views/admin/lessons/show.blade.php.php --}}
@extends('layouts.admin')

@section('title', __('View Lesson'))
@section('page_title', __('Lesson Details'))

@section('page_actions')
  {{-- View Questions filtered by this lesson --}}
  <a href="{{ route('admin.questions.index', ['lesson_id' => $lesson->id]) }}" class="btn btn-outline-dark btn-sm">
    {{ __('View Questions') }} ({{ $lesson->questions_count ?? 0 }})
  </a>

  <a href="{{ route('admin.exams.index', ['lesson_id' => $lesson->id]) }}" class="btn btn-outline-dark btn-sm">
    {{ __('View Exams') }} ({{ $examsCount ?? 0 }})
  </a>

  {{-- AI Question Generator --}}
  <a href="{{ route('admin.lessons.ai.questions.create', $lesson) }}" class="btn btn-success btn-sm">
    {{ __('Generate AI Questions') }}
  </a>

  <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-outline-primary btn-sm">
    {{ __('Edit') }}
  </a>

  <a href="{{ route('admin.lessons.index') }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back') }}
  </a>
@endsection

@section('content')

  {{-- SUMMARY --}}
  <div class="card admin-card mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
          <h5 class="mb-1">{{ $lesson->title_ar ?: ($lesson->title_en ?: __('Lesson')) }}</h5>
          <div class="text-muted small">
            #{{ $lesson->id }}
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
          {{-- Questions count --}}
          <span class="badge rounded-pill bg-primary">
            {{ __('Questions') }}: {{ $lesson->questions_count ?? 0 }}
          </span>

          {{-- Exams count (computed in controller) --}}
          <span class="badge rounded-pill bg-dark">
            {{ __('Exams') }}: {{ $examsCount ?? 0 }}
          </span>

          {{-- Content Updated --}}
          <span class="badge rounded-pill bg-secondary">
            {{ __('Content Updated') }}:
            {{ optional($lesson->content_updated_at)->format('Y-m-d') ?: '-' }}
          </span>
        </div>
      </div>

      <hr class="my-3">

      {{-- Subject / Section badges (AR + EN) --}}
      <div class="d-flex flex-wrap gap-2">
        {{-- Subject --}}
        <span class="badge bg-info text-dark">
          {{ __('Subject (EN)') }}: {{ $lesson->section->material->name_en ?? '-' }}
        </span>
        <span class="badge bg-info text-dark" dir="rtl">
          {{ __('Subject (AR)') }}: {{ $lesson->section->material->name_ar ?? '-' }}
        </span>

        {{-- Section --}}
        <span class="badge bg-warning text-dark">
          {{ __('Section (EN)') }}: {{ $lesson->section->title_en ?? '-' }}
        </span>
        <span class="badge bg-warning text-dark" dir="rtl">
          {{ __('Section (AR)') }}: {{ $lesson->section->title_ar ?? '-' }}
        </span>
      </div>
    </div>
  </div>

  {{-- BASIC INFO --}}
  <div class="card admin-card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="text-muted small">{{ __('Title (EN)') }}</div>
          <div class="fw-semibold">{{ $lesson->title_en ?: '-' }}</div>
        </div>

        <div class="col-md-6">
          <div class="text-muted small">{{ __('Title (AR)') }}</div>
          <div class="fw-semibold" dir="rtl">{{ $lesson->title_ar ?: '-' }}</div>
        </div>

        <div class="col-md-4">
          <div class="text-muted small">{{ __('Created') }}</div>
          <div class="fw-semibold">{{ optional($lesson->created_at)->format('Y-m-d H:i') ?: '-' }}</div>
        </div>

        <div class="col-md-4">
          <div class="text-muted small">{{ __('Updated') }}</div>
          <div class="fw-semibold">{{ optional($lesson->updated_at)->format('Y-m-d H:i') ?: '-' }}</div>
        </div>

        <div class="col-md-4">
          <div class="text-muted small">{{ __('Content Updated') }}</div>
          <div class="fw-semibold">{{ optional($lesson->content_updated_at)->format('Y-m-d H:i') ?: '-' }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- LESSON CONTENT --}}
  <div class="card admin-card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <div class="fw-semibold">{{ __('Lesson Content') }}</div>
      <div class="text-muted small">{{ __('Arabic only, English only, or both — as needed.') }}</div>
    </div>

    <div class="card-body">
      <div class="row g-3">
        <div class="col-lg-6">
          <div class="text-muted small mb-1">{{ __('Content (Arabic)') }}</div>
          <div class="border rounded p-3" dir="rtl" style="white-space: pre-wrap; min-height: 160px;">
            {{ $lesson->content_ar ?: __('No Arabic content.') }}
          </div>
        </div>

        <div class="col-lg-6">
          <div class="text-muted small mb-1">{{ __('Content (English)') }}</div>
          <div class="border rounded p-3" style="white-space: pre-wrap; min-height: 160px;">
            {{ $lesson->content_en ?: __('No English content.') }}
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- LEARNING OUTCOMES --}}
  <div class="card admin-card mb-3">
    <div class="card-header bg-white">
      <div class="fw-semibold">{{ __('Learning Outcomes') }}</div>
    </div>

    <div class="card-body">
      @if(!empty($lesson->learningOutcomes) && $lesson->learningOutcomes->count())
        <ul class="mb-0">
          @foreach($lesson->learningOutcomes as $o)
            <li>{{ $o->title_ar ?? $o->title_en ?? '-' }}</li>
          @endforeach
        </ul>
      @else
        <div class="text-muted">{{ __('No outcomes linked.') }}</div>
      @endif
    </div>
  </div>

  {{-- ATTACHMENTS --}}
  <div class="card admin-card">
    <div class="card-header bg-white">
      <div class="fw-semibold">{{ __('Attachments') }}</div>
    </div>

    <div class="card-body">
      @if(!empty($lesson->attachments) && $lesson->attachments->count())
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>{{ __('File') }}</th>
                <th class="text-nowrap">{{ __('Type') }}</th>
                <th class="text-nowrap">{{ __('Uploaded') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lesson->attachments as $a)
                <tr>
                  <td class="fw-semibold">
                    {{ $a->title ?? $a->name ?? $a->filename ?? ('#'.$a->id) }}
                  </td>
                  <td class="text-muted">
                    {{ $a->mime_type ?? $a->type ?? '-' }}
                  </td>
                  <td class="text-muted text-nowrap">
                    {{ optional($a->created_at)->format('Y-m-d H:i') ?: '-' }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-muted">{{ __('No attachments.') }}</div>
      @endif
    </div>
  </div>

@endsection
