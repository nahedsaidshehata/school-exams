{{-- resources/views/admin/learning_outcomes/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Learning Outcomes'))
@section('page_title', __('Learning Outcomes'))
@section('page_subtitle')
  {{ __('Manage learning outcomes used for lessons and AI question generation.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.learning_outcomes.create') }}" class="btn btn-primary btn-sm">
    + {{ __('Add Learning Outcome') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .truncate { max-width: 520px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .section-card .card-header {
        background: rgba(13,110,253,.06);
        border-bottom: 1px solid rgba(0,0,0,.06);
        font-weight: 800;
      }
      .badge-soft {
        border: 1px solid rgba(0,0,0,.08);
        background: rgba(13,110,253,.08);
        color: #0d6efd;
      }
      .filters .form-label { font-size: .85rem; color: #6c757d; margin-bottom: .25rem; }
    </style>
  @endpush

  @php
    $q = request('q') ?? request('search');

    $materialMap = collect($materials ?? [])->mapWithKeys(function ($m) {
      $name = $m->name_en ?? $m->title_en ?? $m->name_ar ?? $m->title_ar ?? $m->id;
      return [(string)$m->id => $name];
    });

    $sectionMap = collect($sections ?? [])->mapWithKeys(function ($s) {
      $name = $s->title_en ?? $s->title_ar ?? $s->id;
      return [(string)$s->id => $name];
    });

    $hasDestroyRoute = \Illuminate\Support\Facades\Route::has('admin.learning_outcomes.destroy');
  @endphp

  @if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
  @endif

  <div class="card admin-card section-card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
      <span>{{ __('Learning Outcomes') }}</span>
      <span class="text-muted small">{{ __('Total:') }} {{ $outcomes->total() ?? ($outcomes->count() ?? 0) }}</span>
    </div>

    <div class="card-body">
      {{-- Filters --}}
      <form method="GET" class="row g-2 filters align-items-end mb-3">
        <div class="col-12 col-md-4">
          <label class="form-label">{{ __('Search') }}</label>
          <input
            type="text"
            name="q"
            value="{{ $q }}"
            class="form-control form-control-sm"
            placeholder="{{ __('Search (code / title)') }}"
          >
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">{{ __('Subject') }}</label>
          <select id="material_id_filter" name="material_id" class="form-select form-select-sm">
            <option value="">{{ __('All') }}</option>
            @foreach($materials as $m)
              @php $mid = (string)$m->id; @endphp
              <option value="{{ $mid }}" {{ (string)request('material_id') === $mid ? 'selected' : '' }}>
                {{ $materialMap[$mid] ?? $mid }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">{{ __('Section') }}</label>
          <select id="section_id_filter" name="section_id" class="form-select form-select-sm">
            <option value="">{{ __('All') }}</option>
            @foreach($sections as $s)
              @php
                $sid = (string)$s->id;
                $smid = (string)($s->material_id ?? '');
              @endphp
              <option
                value="{{ $sid }}"
                data-material-id="{{ $smid }}"
                {{ (string)request('section_id') === $sid ? 'selected' : '' }}
              >
                {{ $sectionMap[$sid] ?? $sid }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label">{{ __('Grade') }}</label>
          <input
            type="text"
            name="grade_level"
            value="{{ request('grade_level') }}"
            class="form-control form-control-sm"
            placeholder="e.g. 6"
          >
        </div>

        <div class="col-12 d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm" type="submit">{{ __('Filter') }}</button>
          <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.learning_outcomes.index') }}">{{ __('Reset') }}</a>
        </div>
      </form>

      {{-- Table --}}
      @if(($outcomes->count() ?? 0) > 0)
        <div class="table-responsive">
          <table class="table admin-table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="text-nowrap">{{ __('Code') }}</th>
                <th>{{ __('Title (Arabic)') }}</th>
                <th>{{ __('Title (English)') }}</th>
                <th class="text-nowrap">{{ __('Subject') }}</th>
                <th class="text-nowrap">{{ __('Section') }}</th>
                <th class="text-nowrap">{{ __('Grade') }}</th>
                <th class="text-nowrap text-end">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($outcomes as $o)
                @php
                  $mid = (string)($o->material_id ?? '');
                  $sid = (string)($o->section_id ?? '');
                @endphp
                <tr>
                  <td class="text-nowrap">
                    @if($o->code)
                      <span class="badge badge-soft">{{ $o->code }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>

                  <td class="truncate" dir="rtl" title="{{ $o->title_ar }}">
                    {{ \Illuminate\Support\Str::limit($o->title_ar, 90) }}
                  </td>

                  <td class="truncate" title="{{ $o->title_en }}">
                    {{ \Illuminate\Support\Str::limit($o->title_en, 90) }}
                  </td>

                  <td class="text-muted small text-nowrap">
                    {{ $mid ? ($materialMap[$mid] ?? $mid) : '—' }}
                  </td>

                  <td class="text-muted small text-nowrap">
                    {{ $sid ? ($sectionMap[$sid] ?? $sid) : '—' }}
                  </td>

                  <td class="text-nowrap">
                    {{ $o->grade_level ?? '—' }}
                  </td>

                  <td class="text-end text-nowrap">
                    <a href="{{ route('admin.learning_outcomes.edit', $o) }}" class="btn btn-outline-warning btn-sm">
                      {{ __('Edit') }}
                    </a>

                    @if($hasDestroyRoute)
                      <form action="{{ route('admin.learning_outcomes.destroy', $o) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('{{ __('Delete this learning outcome?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                          {{ __('Delete') }}
                        </button>
                      </form>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="mt-3">
          {{ $outcomes->links() }}
        </div>
      @else
        <div class="text-muted">{{ __('No learning outcomes found.') }}</div>
      @endif
    </div>
  </div>

  @push('scripts')
    <script>
      (function () {
        const materialEl = document.getElementById('material_id_filter');
        const sectionEl  = document.getElementById('section_id_filter');
        if (!materialEl || !sectionEl) return;

        const allSectionOptions = Array.from(sectionEl.querySelectorAll('option'))
          .filter(o => o.value); // exclude "All"

        function applySectionFilter() {
          const selectedMaterial = (materialEl.value || '').trim();

          // No subject => show all sections
          if (!selectedMaterial) {
            allSectionOptions.forEach(opt => {
              opt.hidden = false;
              opt.disabled = false;
            });
            return;
          }

          // Filter by material_id
          allSectionOptions.forEach(opt => {
            const mid = (opt.getAttribute('data-material-id') || '').trim();
            const ok = mid === selectedMaterial;
            opt.hidden = !ok;
            opt.disabled = !ok;
          });

          // If currently selected section mismatches, reset
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
        applySectionFilter(); // initial run
      })();
    </script>
  @endpush
@endsection
