{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', __('Admin Dashboard'))
@section('page_title', __('Admin Dashboard'))
@section('page_subtitle')
  {{ __('Overview of platform stats and quick access actions.') }}
@endsection

@section('content')
  @push('head')
    <style>
      /* Page-local styles (admin safe) */
      .admin-stat-card {
        border: 1px solid rgba(0,0,0,.06);
        box-shadow: 0 6px 18px rgba(0,0,0,.04);
        border-radius: 14px;
      }
      .admin-stat-value {
        font-weight: 800;
        letter-spacing: .2px;
        font-size: 1.6rem;
        margin: 0;
      }
      .admin-stat-label {
        margin: 0;
        color: #6c757d;
        font-size: .9rem;
      }
      .admin-quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
      }
      .admin-quick-actions .btn {
        text-align: start;
        padding: 12px 14px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
      }
      .admin-qa-title {
        font-weight: 700;
      }
      .admin-qa-sub {
        font-size: .85rem;
        color: rgba(255,255,255,.85);
      }
      .admin-qa-sub.dark {
        color: #6c757d;
      }
      .admin-qa-arrow {
        font-weight: 700;
        opacity: .9;
      }
    </style>
  @endpush

  {{-- Top welcome card --}}
  <div class="card admin-card mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
          <h2 class="h5 mb-1">{{ __('Welcome, Admin') }}</h2>
          <p class="text-muted mb-0">{{ __('Manage schools, students, content, questions, and exams from one place.') }}</p>
        </div>

        <div class="d-flex gap-2">
          <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-primary btn-sm">
            {{ __('View Exams') }}
          </a>
          <a href="{{ route('admin.questions.create') }}" class="btn btn-primary btn-sm">
            {{ __('Create Question') }}
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Stats --}}
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
      <div class="card admin-stat-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <p class="admin-stat-value">{{ $stats['schools'] ?? 0 }}</p>
              <p class="admin-stat-label">{{ __('Schools') }}</p>
            </div>
            <span class="badge text-bg-primary">{{ __('Total') }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card admin-stat-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <p class="admin-stat-value">{{ $stats['students'] ?? 0 }}</p>
              <p class="admin-stat-label">{{ __('Students') }}</p>
            </div>
            <span class="badge text-bg-success">{{ __('Total') }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card admin-stat-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <p class="admin-stat-value">{{ $stats['materials'] ?? 0 }}</p>
              <p class="admin-stat-label">{{ __('Materials') }}</p>
            </div>
            <span class="badge text-bg-warning">{{ __('Content') }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
      <div class="card admin-stat-card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <p class="admin-stat-value">{{ $stats['questions'] ?? 0 }}</p>
              <p class="admin-stat-label">{{ __('Questions') }}</p>
            </div>
            <span class="badge text-bg-info">{{ __('Bank') }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Quick actions --}}
  <div class="card admin-card">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h3 class="h6 mb-0">{{ __('Quick Actions') }}</h3>
        <span class="text-muted small">{{ __('Shortcuts to your most used admin pages') }}</span>
      </div>

      <div class="admin-quick-actions">
        <button type="button"
                class="btn btn-outline-dark"
                onclick="window.location.href='{{ route('admin.schools.index') }}'">
          <div>
            <div class="admin-qa-title">{{ __('Manage Schools') }}</div>
            <div class="admin-qa-sub dark">{{ __('Create & view schools') }}</div>
          </div>
          <span class="admin-qa-arrow">→</span>
        </button>

        <button type="button"
                class="btn btn-outline-dark"
                onclick="window.location.href='{{ route('admin.students.index') }}'">
          <div>
            <div class="admin-qa-title">{{ __('Manage Students') }}</div>
            <div class="admin-qa-sub dark">{{ __('Create & view students') }}</div>
          </div>
          <span class="admin-qa-arrow">→</span>
        </button>

        <button type="button"
                class="btn btn-success"
                onclick="window.location.href='{{ route('admin.materials.index') }}'">
          <div>
            <div class="admin-qa-title">{{ __('Content Bank') }}</div>
            <div class="admin-qa-sub">{{ __('Materials • Sections • Lessons') }}</div>
          </div>
          <span class="admin-qa-arrow">→</span>
        </button>

        <button type="button"
                class="btn btn-primary"
                onclick="window.location.href='{{ route('admin.questions.index') }}'">
          <div>
            <div class="admin-qa-title">{{ __('Question Bank') }}</div>
            <div class="admin-qa-sub">{{ __('Create & manage questions') }}</div>
          </div>
          <span class="admin-qa-arrow">→</span>
        </button>
      </div>
    </div>
  </div>
@endsection
