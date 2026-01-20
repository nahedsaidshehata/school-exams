{{-- resources/views/admin/exams/edit.blade.php --}}
@extends('layouts.admin')

@section('title', __('Edit Exam'))
@section('page_title', __('Edit Exam'))
@section('page_subtitle')
  {{ __('Update exam configuration and access rules.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.exams.show', $exam->id) }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back to Exam') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .req { color:#dc3545; font-weight:800; }
      .admin-form-card .card-header{
        background: rgba(13,110,253,.06);
        border-bottom: 1px solid rgba(0,0,0,.06);
        font-weight:800;
      }
      .form-hint { color:#6c757d; font-size:.9rem; }
    </style>
  @endpush

  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-semibold mb-2">{{ __('Please fix the following:') }}</div>
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-3">
    <div class="col-12 col-xl-10">
      <div class="card admin-card admin-form-card">
        <div class="card-header d-flex align-items-center justify-content-between gap-2">
          <span>{{ __('Exam Details') }}</span>
          <span class="badge text-bg-secondary">#{{ $exam->id }}</span>
        </div>

        <div class="card-body">
          <form action="{{ route('admin.exams.update', $exam->id) }}" method="POST" class="needs-validation" novalidate>
            @csrf
            @method('PUT')

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="title_en" class="form-label">{{ __('Title (English)') }} <span class="req">*</span></label>
                <input type="text" class="form-control @error('title_en') is-invalid @enderror" id="title_en" name="title_en" value="{{ old('title_en', $exam->title_en) }}" required>
                @error('title_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="title_ar" class="form-label">{{ __('Title (Arabic)') }} <span class="req">*</span></label>
                <input type="text" class="form-control @error('title_ar') is-invalid @enderror" id="title_ar" name="title_ar" value="{{ old('title_ar', $exam->title_ar) }}" required dir="rtl">
                @error('title_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-4">
                <label for="duration_minutes" class="form-label">{{ __('Duration (minutes)') }} <span class="req">*</span></label>
                <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" min="1" required>
                @error('duration_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-4">
                <label for="starts_at" class="form-label">{{ __('Start Date & Time') }} <span class="req">*</span></label>
                <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" id="starts_at" name="starts_at" value="{{ old('starts_at', $exam->starts_at->format('Y-m-d\TH:i')) }}" required>
                @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-4">
                <label for="ends_at" class="form-label">{{ __('End Date & Time') }} <span class="req">*</span></label>
                <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" id="ends_at" name="ends_at" value="{{ old('ends_at', $exam->ends_at->format('Y-m-d\TH:i')) }}" required>
                @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="max_attempts" class="form-label">{{ __('Maximum Attempts') }} <span class="req">*</span></label>
                <input type="number" class="form-control @error('max_attempts') is-invalid @enderror" id="max_attempts" name="max_attempts" value="{{ old('max_attempts', $exam->max_attempts) }}" min="1" required>
                @error('max_attempts') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label d-block">{{ __('Access') }}</label>
                <div class="form-check mt-2">
                  <input type="checkbox" class="form-check-input" id="is_globally_locked" name="is_globally_locked" value="1" {{ old('is_globally_locked', $exam->is_globally_locked) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_globally_locked">
                    {{ __('Globally Locked (students cannot access)') }}
                  </label>
                </div>
                <div class="form-hint mt-1">{{ __('If enabled, the exam is locked by default for all students unless overridden.') }}</div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <button type="submit" class="btn btn-primary">{{ __('Update Exam') }}</button>
              <a href="{{ route('admin.exams.show', $exam->id) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
