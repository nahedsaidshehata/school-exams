{{-- resources/views/admin/materials/create.blade.php --}}
@extends('layouts.admin')

@section('title', __('Create Subject'))
@section('page_title', __('Create New Subject'))
@section('page_subtitle')
  {{ __('Add a new subject to the central content bank.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.materials.index') }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .req { color: #dc3545; font-weight: 700; }
      .form-hint { color: #6c757d; font-size: .9rem; }
      .admin-form-card .card-header {
        background: rgba(13,110,253,.06);
        border-bottom: 1px solid rgba(0,0,0,.06);
        font-weight: 700;
      }
    </style>
  @endpush

  <div class="row g-3">
    <div class="col-12 col-lg-8 col-xl-6">
      <div class="card admin-card admin-form-card">
        <div class="card-header">
          {{ __('Subject Information') }}
        </div>
        <div class="card-body">
          <form action="{{ route('admin.materials.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf

            <div class="row g-3">
              <div class="col-12">
                <label for="name_en" class="form-label">
                  {{ __('Name (English)') }} <span class="req">*</span>
                </label>
                <input
                  type="text"
                  id="name_en"
                  name="name_en"
                  value="{{ old('name_en') }}"
                  class="form-control @error('name_en') is-invalid @enderror"
                  required
                  autocomplete="off"
                >
                @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12">
                <label for="name_ar" class="form-label">
                  {{ __('Name (Arabic)') }} <span class="req">*</span>
                </label>
                <input
                  type="text"
                  id="name_ar"
                  name="name_ar"
                  value="{{ old('name_ar') }}"
                  class="form-control @error('name_ar') is-invalid @enderror"
                  required
                  autocomplete="off"
                >
                @error('name_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint mt-1">{{ __('Example: مادة التربية الإسلامية') }}</div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <button type="submit" class="btn btn-success">
                {{ __('Create Subject') }}
              </button>
              <a href="{{ route('admin.materials.index') }}" class="btn btn-outline-secondary">
                {{ __('Cancel') }}
              </a>
            </div>
          </form>
        </div>
      </div>

      <div class="text-muted small mt-3">
        {{ __('Tip: Keep names consistent (EN/AR) to improve search and organization.') }}
      </div>
    </div>
  </div>
@endsection
