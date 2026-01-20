{{-- resources/views/admin/learning_outcomes/create.blade.php --}}
@extends('layouts.admin')

@section('title', __('Add Learning Outcome'))
@section('page_title', __('Add Learning Outcome'))
@section('page_subtitle')
  {{ __('Create a learning outcome that can be linked to lessons and AI-generated questions.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.learning_outcomes.index') }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back to List') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .req { color:#dc3545; font-weight:800; }
      .admin-form-card .card-header{
        background: rgba(13,110,253,.06);
        border-bottom: 1px solid rgba(0,0,0,.06);
        font-weight: 800;
      }
      .hint { color:#6c757d; font-size:.9rem; }
    </style>
  @endpush

  <div class="row g-3">
    <div class="col-12 col-lg-10 col-xl-8">
      <div class="card admin-card admin-form-card">
        <div class="card-header">{{ __('Learning Outcome') }}</div>

        <div class="card-body">
          <form action="{{ route('admin.learning_outcomes.store') }}" method="POST">
            @csrf

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">{{ __('Subject') }} <span class="text-muted">(optional)</span></label>
                <select id="material_id" name="material_id" class="form-select @error('material_id') is-invalid @enderror">
                  <option value="">{{ __('Select (optional)') }}</option>
                  @foreach($materials as $m)
                    <option value="{{ $m->id }}" {{ old('material_id') == $m->id ? 'selected' : '' }}>
                      {{ $m->name_en ?? $m->title_en ?? $m->name_ar ?? $m->title_ar }}
                    </option>
                  @endforeach
                </select>
                @error('material_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">{{ __('Section') }} <span class="text-muted">(optional)</span></label>
                <select id="section_id" name="section_id" class="form-select @error('section_id') is-invalid @enderror">
                  <option value="">{{ __('Select (optional)') }}</option>
                  @foreach($sections as $s)
                    <option
                      value="{{ $s->id }}"
                      data-material-id="{{ $s->material_id }}"
                      {{ old('section_id') == $s->id ? 'selected' : '' }}
                    >
                      {{ $s->title_en ?? $s->title_ar }}
                    </option>
                  @endforeach
                </select>
                @error('section_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="hint mt-1">{{ __('Sections will filter based on the selected subject.') }}</div>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">{{ __('Code') }} <span class="text-muted">(optional)</span></label>
                <input
                  type="text"
                  name="code"
                  value="{{ old('code') }}"
                  class="form-control @error('code') is-invalid @enderror"
                  placeholder="LO-1.1"
                  autocomplete="off"
                >
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="hint mt-1">{{ __('Example: LO-1.1') }}</div>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">{{ __('Grade') }} <span class="text-muted">(optional)</span></label>
                <input
                  type="text"
                  name="grade_level"
                  value="{{ old('grade_level') }}"
                  class="form-control @error('grade_level') is-invalid @enderror"
                  placeholder="e.g. 6"
                  autocomplete="off"
                >
                @error('grade_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12"></div>

              <div class="col-12 col-md-6">
                <label class="form-label">{{ __('Title (English)') }} <span class="req">*</span></label>
                <input
                  type="text"
                  name="title_en"
                  value="{{ old('title_en') }}"
                  class="form-control @error('title_en') is-invalid @enderror"
                  required
                  autocomplete="off"
                >
                @error('title_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">{{ __('Title (Arabic)') }} <span class="req">*</span></label>
                <input
                  type="text"
                  name="title_ar"
                  value="{{ old('title_ar') }}"
                  class="form-control @error('title_ar') is-invalid @enderror"
                  required
                  dir="rtl"
                  autocomplete="off"
                >
                @error('title_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12">
                <label class="form-label">{{ __('Description (English)') }} <span class="text-muted">(optional)</span></label>
                <textarea
                  name="description_en"
                  class="form-control @error('description_en') is-invalid @enderror"
                  rows="3"
                >{{ old('description_en') }}</textarea>
                @error('description_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12">
                <label class="form-label">{{ __('Description (Arabic)') }} <span class="text-muted">(optional)</span></label>
                <textarea
                  name="description_ar"
                  class="form-control @error('description_ar') is-invalid @enderror"
                  rows="3"
                  dir="rtl"
                >{{ old('description_ar') }}</textarea>
                @error('description_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <button type="submit" class="btn btn-success">{{ __('Create') }}</button>
              <a href="{{ route('admin.learning_outcomes.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
          </form>

          <div class="hint mt-3">
            {{ __('Tip: You can attach outcomes to lessons, and later use them to guide AI question generation.') }}
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      (function () {
        const materialEl = document.getElementById('material_id');
        const sectionEl  = document.getElementById('section_id');
        if (!materialEl || !sectionEl) return;

        const allSectionOptions = Array.from(sectionEl.querySelectorAll('option'))
          .filter(o => o.value); // exclude placeholder

        function applySectionFilter() {
          const selectedMaterial = (materialEl.value || '').trim();

          // If no subject selected: show all
          if (!selectedMaterial) {
            allSectionOptions.forEach(opt => {
              opt.hidden = false;
              opt.disabled = false;
            });
            return;
          }

          // Show only sections belonging to material_id
          allSectionOptions.forEach(opt => {
            const mid = (opt.getAttribute('data-material-id') || '').trim();
            const ok = mid === selectedMaterial;
            opt.hidden = !ok;
            opt.disabled = !ok;
          });

          // If currently selected section is not compatible, reset it
          const current = sectionEl.value;
          if (current) {
            const selectedOpt = sectionEl.querySelector('option[value="' + CSS.escape(current) + '"]');
            const mid = selectedOpt ? (selectedOpt.getAttribute('data-material-id') || '').trim() : '';
            if (mid !== selectedMaterial) {
              sectionEl.value = '';
            }
          }
        }

        materialEl.addEventListener('change', applySectionFilter);
        // initial run (handles old() values)
        applySectionFilter();
      })();
    </script>
  @endpush
@endsection
