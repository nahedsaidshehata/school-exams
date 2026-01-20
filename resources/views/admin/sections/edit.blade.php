{{-- resources/views/admin/sections/edit.blade.php --}}
@extends('layouts.admin')

@section('title', __('Edit Section'))
@section('page_title', __('Edit Section'))
@section('page_subtitle')
  {{ __('Update section details and its parent subject.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary btn-sm">
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
    <div class="col-12 col-lg-10 col-xl-8">
      <div class="card admin-card admin-form-card">
        <div class="card-header d-flex align-items-center justify-content-between gap-2">
          <span>{{ __('Section Information') }}</span>
          <span class="badge text-bg-secondary">#{{ $section->id }}</span>
        </div>

        <div class="card-body">
          <form action="{{ route('admin.sections.update', $section) }}" method="POST" class="needs-validation" novalidate>
            @csrf
            @method('PUT')

            <div class="row g-3">
              <div class="col-12">
                <label for="material_id" class="form-label">
                  {{ __('Subject') }} <span class="req">*</span>
                </label>
                <select
                  id="material_id"
                  name="material_id"
                  class="form-select @error('material_id') is-invalid @enderror"
                  required
                >
                  <option value="">{{ __('Select Subject') }}</option>
                  @foreach($materials as $material)
                    <option value="{{ $material->id }}" {{ old('material_id', $section->material_id) == $material->id ? 'selected' : '' }}>
                      {{ $material->name_en }}
                    </option>
                  @endforeach
                </select>
                @error('material_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint mt-1">{{ __('Choose the parent subject for this section.') }}</div>
              </div>

              <div class="col-12 col-md-6">
                <label for="title_en" class="form-label">
                  {{ __('Title (English)') }} <span class="req">*</span>
                </label>
                <input
                  type="text"
                  id="title_en"
                  name="title_en"
                  value="{{ old('title_en', $section->title_en) }}"
                  class="form-control @error('title_en') is-invalid @enderror"
                  required
                  autocomplete="off"
                >
                @error('title_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="title_ar" class="form-label">
                  {{ __('Title (Arabic)') }} <span class="req">*</span>
                </label>
                <input
                  type="text"
                  id="title_ar"
                  name="title_ar"
                  value="{{ old('title_ar', $section->title_ar) }}"
                  class="form-control @error('title_ar') is-invalid @enderror"
                  required
                  autocomplete="off"
                >
                @error('title_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <button type="submit" class="btn btn-success">
                {{ __('Update Section') }}
              </button>
              <a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary">
                {{ __('Cancel') }}
              </a>
            </div>
          </form>
        </div>
      </div>

      <div class="text-muted small mt-3">
        {{ __('Tip: Changing section parent subject affects lesson grouping.') }}
      </div>
    </div>
  </div>
@endsection
