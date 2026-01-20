{{-- resources/views/admin/lessons/create.blade.php --}}
@extends('layouts.admin')

@section('title', __('Create Lesson'))
@section('page_title', __('Create New Lesson'))
@section('page_subtitle')
  {{ __('Add a new lesson under a section.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.lessons.index') }}" class="btn btn-outline-secondary btn-sm">
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
      .lo-box {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 12px;
        padding: 12px;
        max-height: 320px;
        overflow: auto;
        background: #fff;
      }
      .lo-item {
        border-bottom: 1px dashed rgba(0,0,0,.08);
        padding: 8px 0;
      }
      .lo-item:last-child { border-bottom: 0; }
      .lo-meta { font-size: .85rem; color:#6c757d; }
      .lo-title { font-weight: 600; }
      .lo-tools .btn { padding: .25rem .5rem; border-radius: 10px; }

      .content-card .card-header{
        background: rgba(25,135,84,.06);
        border-bottom: 1px solid rgba(0,0,0,.06);
        font-weight: 700;
      }
      .content-help {
        font-size: .9rem;
        color: #6c757d;
      }
    </style>
  @endpush

  @php
    $selectedOutcomeIds = old('learning_outcome_ids', []);
    $oldMaterialId = old('material_id', $selectedMaterialId ?? '');
    $oldSectionId  = old('section_id', '');
  @endphp

  <div class="row g-3">
    <div class="col-12 col-lg-10 col-xl-8">
      <div class="card admin-card admin-form-card">
        <div class="card-header">
          {{ __('Lesson Information') }}
        </div>
        <div class="card-body">
          <form action="{{ route('admin.lessons.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf

            <div class="row g-3">

              {{-- ✅ NEW: Material first --}}
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
                  @foreach($materials as $m)
                    <option value="{{ $m->id }}" {{ (string)$oldMaterialId === (string)$m->id ? 'selected' : '' }}>
                      {{ $m->name_en }} @if(!empty($m->name_ar)) — {{ $m->name_ar }} @endif
                    </option>
                  @endforeach
                </select>
                @error('material_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint mt-1">{{ __('Choose a subject first, then select one of its sections.') }}</div>
              </div>

              {{-- ✅ Section (depends on material) --}}
              <div class="col-12">
                <label for="section_id" class="form-label">
                  {{ __('Section') }} <span class="req">*</span>
                </label>
                <select
                  id="section_id"
                  name="section_id"
                  class="form-select @error('section_id') is-invalid @enderror"
                  required
                  data-old="{{ $oldSectionId }}"
                  disabled
                >
                  <option value="">{{ __('Select Section') }}</option>

                  {{-- If validation failed & we preloaded sections server-side --}}
                  @if(isset($sections) && count($sections) > 0)
                    @foreach($sections as $section)
                      <option value="{{ $section->id }}" {{ (string)$oldSectionId === (string)$section->id ? 'selected' : '' }}>
                        {{ $section->title_en }} @if(!empty($section->title_ar)) — {{ $section->title_ar }} @endif
                      </option>
                    @endforeach
                  @endif
                </select>
                @error('section_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-hint mt-1">{{ __('Choose the parent section for this lesson.') }}</div>
              </div>

              <div class="col-12 col-md-6">
                <label for="title_en" class="form-label">
                  {{ __('Title (English)') }} <span class="req">*</span>
                </label>
                <input
                  type="text"
                  id="title_en"
                  name="title_en"
                  value="{{ old('title_en') }}"
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
                  value="{{ old('title_ar') }}"
                  class="form-control @error('title_ar') is-invalid @enderror"
                  required
                  autocomplete="off"
                >
                @error('title_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            {{-- ✅ Lesson Content (Optional) --}}
            <div class="card admin-card content-card mb-3 mt-4">
              <div class="card-header fw-bold d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>{{ __('Lesson Content (Optional)') }}</span>
                <span class="text-muted small">{{ __('You can leave it empty and add it later from Edit.') }}</span>
              </div>
              <div class="card-body">
                <div class="content-help mb-2">
                  {{ __('Write Arabic only, English only, or both — as needed.') }}
                </div>

                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('Content (Arabic)') }} <span class="text-muted small">({{ __('Optional') }})</span></label>
                    <textarea
                      name="content_ar"
                      rows="10"
                      class="form-control @error('content_ar') is-invalid @enderror"
                      placeholder="{{ __('Add lesson explanation in Arabic...') }}"
                    >{{ old('content_ar') }}</textarea>
                    @error('content_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('Content (English)') }} <span class="text-muted small">({{ __('Optional') }})</span></label>
                    <textarea
                      name="content_en"
                      rows="10"
                      class="form-control @error('content_en') is-invalid @enderror"
                      placeholder="{{ __('Add lesson explanation in English...') }}"
                    >{{ old('content_en') }}</textarea>
                    @error('content_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
                </div>
              </div>
            </div>

            {{-- ✅ Learning Outcomes inside the same form --}}
            <div class="card admin-card mb-3 mt-4">
              <div class="card-header fw-bold d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>{{ __('Learning Outcomes') }}</span>
                <span class="text-muted small">{{ __('Search & select outcomes for this lesson.') }}</span>
              </div>
              <div class="card-body">
                <div class="row g-2 align-items-center mb-2">
                  <div class="col-12 col-md-7">
                    <input id="loSearch" type="text" class="form-control"
                           placeholder="{{ __('Search (code / title)') }}">
                  </div>
                  <div class="col-12 col-md-5 lo-tools d-flex gap-2 justify-content-md-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="loSelectAll">
                      {{ __('Select all') }}
                    </button>
                    <button type="button" class="btn btn-outline-dark btn-sm" id="loClear">
                      {{ __('Clear') }}
                    </button>
                  </div>
                </div>

                <div class="lo-box" id="loBox">
                  @forelse($learningOutcomes as $o)
                    @php
                      $checked = in_array($o->id, $selectedOutcomeIds);
                    @endphp
                    <div class="lo-item" data-text="{{ strtolower(($o->code ?? '').' '.$o->title_en.' '.$o->title_ar) }}">
                      <label class="d-flex gap-2 align-items-start mb-0">
                        <input class="form-check-input lo-check mt-1"
                               type="checkbox"
                               name="learning_outcome_ids[]"
                               value="{{ $o->id }}"
                               @checked($checked)>
                        <div>
                          <div class="lo-title">{{ $o->title_ar }}</div>
                          <div class="lo-meta">
                            <span class="me-2">{{ $o->code ? $o->code : '—' }}</span>
                            <span>{{ $o->title_en }}</span>
                          </div>
                        </div>
                      </label>
                    </div>
                  @empty
                    <div class="text-muted">{{ __('No learning outcomes found.') }}</div>
                  @endforelse
                </div>

                @error('learning_outcome_ids')
                  <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <button type="submit" class="btn btn-success">
                {{ __('Create Lesson') }}
              </button>
              <a href="{{ route('admin.lessons.index') }}" class="btn btn-outline-secondary">
                {{ __('Cancel') }}
              </a>
            </div>
          </form>
        </div>
      </div>

      <div class="text-muted small mt-3">
        {{ __('Tip: The section selection determines the lesson’s subject grouping automatically.') }}
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  // Learning Outcomes search (unchanged)
  (function(){
    const search = document.getElementById('loSearch');
    const box = document.getElementById('loBox');
    if(!search || !box) return;

    const items = Array.from(box.querySelectorAll('.lo-item'));
    const checks = () => Array.from(box.querySelectorAll('.lo-check'));

    function applyFilter() {
      const q = (search.value || '').trim().toLowerCase();
      items.forEach(el => {
        const hay = (el.getAttribute('data-text') || '');
        el.style.display = (!q || hay.includes(q)) ? '' : 'none';
      });
    }

    document.getElementById('loSelectAll')?.addEventListener('click', function(){
      items.forEach(el => {
        if(el.style.display === 'none') return;
        const cb = el.querySelector('.lo-check');
        if(cb) cb.checked = true;
      });
    });

    document.getElementById('loClear')?.addEventListener('click', function(){
      checks().forEach(cb => cb.checked = false);
    });

    search.addEventListener('input', applyFilter);
  })();

  // ✅ NEW: material -> sections cascading dropdown
  (function(){
    const materialSel = document.getElementById('material_id');
    const sectionSel  = document.getElementById('section_id');
    if (!materialSel || !sectionSel) return;

    const oldSectionId = sectionSel.getAttribute('data-old') || '';

    function setLoading(isLoading) {
      if (isLoading) {
        sectionSel.disabled = true;
      }
    }

    function resetSections() {
      sectionSel.innerHTML = '';
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = '{{ __("Select Section") }}';
      sectionSel.appendChild(opt);
      sectionSel.disabled = true;
    }

    async function loadSections(materialId) {
      if (!materialId) {
        resetSections();
        return;
      }

      setLoading(true);

      const url = `{{ url('/admin/materials') }}/${materialId}/sections`;

      try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('Failed to load sections');

        const list = await res.json();

        sectionSel.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = '{{ __("Select Section") }}';
        sectionSel.appendChild(opt0);

        list.forEach(item => {
          const o = document.createElement('option');
          o.value = item.id;

          const en = item.title_en || '';
          const ar = item.title_ar || '';
          o.textContent = ar && en ? `${en} — ${ar}` : (en || ar || item.id);

          if (oldSectionId && String(oldSectionId) === String(item.id)) {
            o.selected = true;
          }

          sectionSel.appendChild(o);
        });

        sectionSel.disabled = false;
      } catch (e) {
        resetSections();
      }
    }

    materialSel.addEventListener('change', function () {
      // when changing material, drop old selection
      sectionSel.setAttribute('data-old', '');
      loadSections(this.value);
    });

    // on load: if material already selected (validation old input), load its sections
    if (materialSel.value) {
      // if sections already server-rendered, enable; otherwise fetch
      const hasServerOptions = sectionSel.querySelectorAll('option').length > 1;
      sectionSel.disabled = !hasServerOptions;
      if (!hasServerOptions) {
        loadSections(materialSel.value);
      } else {
        sectionSel.disabled = false;
      }
    } else {
      resetSections();
    }
  })();
</script>
@endpush
