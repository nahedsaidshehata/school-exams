{{-- resources/views/admin/students/create.blade.php --}}
@extends('layouts.admin')

@section('title', __('Create Student'))
@section('page_title', __('Create New Student'))
@section('page_subtitle')
  {{ __('Create a new student and assign them to a school.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .form-hint { color: #6c757d; font-size: .9rem; }
      .req { color: #dc3545; font-weight: 700; }
      .admin-form-card .card-header {
        background: rgba(25,135,84,.07);
        border-bottom: 1px solid rgba(0,0,0,.06);
        font-weight: 700;
      }
      .badge-soft {
        border: 1px solid rgba(0,0,0,.08);
        background: rgba(13,110,253,.08);
        color: #0d6efd;
      }
    </style>
  @endpush

  <div class="row g-3">
    <div class="col-12 col-lg-10 col-xl-8">
      <div class="card admin-card admin-form-card">
        <div class="card-header">
          {{ __('Student Information') }}
        </div>
        <div class="card-body">
          <form action="{{ route('admin.students.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf

            <div class="row g-3">
              <div class="col-12">
                <label for="school_id" class="form-label">
                  {{ __('School') }} <span class="req">*</span>
                </label>
                <select
                  id="school_id"
                  name="school_id"
                  class="form-select @error('school_id') is-invalid @enderror"
                  required
                >
                  <option value="">{{ __('Select School') }}</option>
                  @foreach($schools as $school)
                    <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                      {{ $school->name_en }}
                    </option>
                  @endforeach
                </select>
                @error('school_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint mt-1">{{ __('This determines the tenant scope for the student.') }}</div>
              </div>

              <div class="col-12">
                <label for="full_name" class="form-label">
                  {{ __('Full Name') }} <span class="req">*</span>
                </label>
                <input
                  type="text"
                  id="full_name"
                  name="full_name"
                  value="{{ old('full_name') }}"
                  class="form-control @error('full_name') is-invalid @enderror"
                  required
                  autocomplete="off"
                >
                @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="username" class="form-label">
                  {{ __('UserName (Optional)') }}
                </label>
                <input
                  type="text"
                  id="username"
                  name="username"
                  value="{{ old('username') }}"
                  class="form-control @error('username') is-invalid @enderror"
                  autocomplete="off"
                >
                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint mt-1">{{ __('If empty, the system will generate a username automatically.') }}</div>
              </div>

              <div class="col-12 col-md-6">
                <label for="email" class="form-label">
                  {{ __('Email (Optional)') }}
                </label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value="{{ old('email') }}"
                  class="form-control @error('email') is-invalid @enderror"
                  autocomplete="off"
                >
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="password" class="form-label">
                  {{ __('Password (Optional)') }}
                </label>
                <input
                  type="password"
                  id="password"
                  name="password"
                  class="form-control @error('password') is-invalid @enderror"
                  autocomplete="new-password"
                >
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint mt-1">{{ __('If empty, default password will be "password" (for testing only).') }}</div>
              </div>

              <div class="col-12 col-md-6">
                <label for="password_confirmation" class="form-label">
                  {{ __('Confirm Password') }}
                </label>
                <input
                  type="password"
                  id="password_confirmation"
                  name="password_confirmation"
                  class="form-control"
                  autocomplete="new-password"
                >
              </div>

              <hr class="my-2">

              {{-- ✅ Student Profile Fields --}}
              <div class="col-12 col-md-6">
                <label for="year" class="form-label">{{ __('Academic Year') }}</label>
                <input type="text" id="year" name="year" value="{{ old('year') }}"
                       class="form-control @error('year') is-invalid @enderror" autocomplete="off">
                @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="grade" class="form-label">{{ __('Grade') }}</label>
                <input type="text" id="grade" name="grade" value="{{ old('grade') }}"
                       class="form-control @error('grade') is-invalid @enderror" autocomplete="off">
                @error('grade') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="gender" class="form-label">{{ __('Gender') }}</label>
                <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                  <option value="">{{ __('Select') }}</option>
                  <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                  <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                </select>
                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label d-block">{{ __('SEND (Special needs)') }}</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="send" name="send" value="1" {{ old('send') ? 'checked' : '' }}>
                  <label class="form-check-label" for="send">{{ __('Yes') }}</label>
                </div>
              </div>

              <div class="col-12 col-md-6">
                <label for="parent_email" class="form-label">{{ __("Parent's Email") }}</label>
                <input type="email" id="parent_email" name="parent_email" value="{{ old('parent_email') }}"
                       class="form-control @error('parent_email') is-invalid @enderror" autocomplete="off">
                @error('parent_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="nationality" class="form-label">{{ __('Nationality') }}</label>
                <input type="text" id="nationality" name="nationality" value="{{ old('nationality') }}"
                       class="form-control @error('nationality') is-invalid @enderror" autocomplete="off">
                @error('nationality') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <button type="submit" class="btn btn-success">
                {{ __('Create Student') }}
              </button>
              <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">
                {{ __('Cancel') }}
              </a>
            </div>
          </form>
        </div>
      </div>

      <div class="text-muted small mt-3">
        {{ __('Tip: You can leave username/password empty for fast bulk creation during setup.') }}
      </div>
    </div>
  </div>
@endsection
