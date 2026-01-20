{{-- resources/views/admin/lessons/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Lessons Management'))
@section('page_title', __('Lessons'))
@section('page_subtitle')
  {{ __('Manage lessons under sections and subjects in the central content bank.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.lessons.create') }}" class="btn btn-success btn-sm">
    {{ __('Create Lesson') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .table td, .table th { vertical-align: middle; }
      .truncate {
        max-width: 260px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .btn-icon { padding: .35rem .55rem; border-radius: 10px; }
      .badge-soft {
        border: 1px solid rgba(0,0,0,.08);
        background: rgba(13,110,253,.08);
        color: #0d6efd;
      }

      .filters-grid{
        display: grid;
        grid-template-columns: 260px 260px 1fr auto auto;
        gap: 10px;
        align-items: end;
      }
      @media (max-width: 992px){
        .filters-grid{ grid-template-columns: 1fr; }
      }
    </style>
  @endpush

  <div class="card admin-card">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="text-muted small">
          {{ __('Total:') }}
          <span class="fw-semibold" id="lessonsTotal">{{ method_exists($lessons, 'total') ? $lessons->total() : '' }}</span>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
          {{ __('Back to Dashboard') }}
        </a>
      </div>

      {{-- ✅ Filters + Search (AJAX) --}}
      <form id="lessonsFiltersForm" method="GET" action="{{ route('admin.lessons.index') }}" class="mb-3">
        <div class="filters-grid">
          <div>
            <label class="form-label mb-1">{{ __('Subject') }}</label>
            <select name="material_id" id="material_id" class="form-select form-select-sm">
              <option value="">{{ __('All') }}</option>
              @foreach($materials as $m)
                <option value="{{ $m->id }}" @selected(($filters['material_id'] ?? '') === $m->id)>
                  {{ $m->name_en }}{{ $m->name_ar ? ' — ' . $m->name_ar : '' }}
                </option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="form-label mb-1">{{ __('Section') }}</label>
            <select name="section_id" id="section_id" class="form-select form-select-sm">
              <option value="">{{ __('All') }}</option>

              {{-- initial load if material already selected --}}
              @foreach(($sections ?? collect()) as $s)
                <option value="{{ $s->id }}" @selected(($filters['section_id'] ?? '') === $s->id)>
                  {{ $s->title_en }}{{ $s->title_ar ? ' — ' . $s->title_ar : '' }}
                </option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="form-label mb-1">{{ __('Search') }}</label>
            <input
              type="text"
              name="q"
              id="q"
              value="{{ $filters['q'] ?? '' }}"
              class="form-control form-control-sm"
              placeholder="{{ __('Lesson title (EN/AR)...') }}"
              autocomplete="off"
            >
          </div>

          <div>
            <button type="submit" class="btn btn-primary btn-sm w-100">
              {{ __('Apply') }}
            </button>
          </div>

          <div>
            <a href="{{ route('admin.lessons.index') }}" class="btn btn-outline-secondary btn-sm w-100" id="lessonsResetBtn">
              {{ __('Reset') }}
            </a>
          </div>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table admin-table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>{{ __('Title (EN)') }}</th>
              <th>{{ __('Title (AR)') }}</th>
              <th>{{ __('Section') }}</th>
              <th>{{ __('Subject') }}</th>
              <th class="text-nowrap">{{ __('Outcomes') }}</th>
              <th class="text-nowrap">{{ __('Created') }}</th>
              <th class="text-nowrap text-end">{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody id="lessonsTbody">
            @include('admin.lessons.partials.rows', ['lessons' => $lessons])
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-3" id="lessonsPagination">
        @include('admin.lessons.partials.pagination', ['lessons' => $lessons])
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      (function () {
        const form = document.getElementById('lessonsFiltersForm');
        const inputQ = document.getElementById('q');
        const selectMaterial = document.getElementById('material_id');
        const selectSection = document.getElementById('section_id');

        const tbody = document.getElementById('lessonsTbody');
        const pagination = document.getElementById('lessonsPagination');
        const totalEl = document.getElementById('lessonsTotal');

        let debounceTimer = null;
        let abortCtrl = null;

        const sectionsEndpointTemplate = @json(route('admin.materials.sections', ['material' => '___ID___']));

        function buildUrl(baseUrl, extraParams = {}) {
          const url = new URL(baseUrl, window.location.origin);
          const formData = new FormData(form);

          for (const [k, v] of formData.entries()) {
            const val = (v || '').toString().trim();
            if (val !== '') url.searchParams.set(k, val);
          }

          Object.keys(extraParams).forEach(k => {
            const val = (extraParams[k] ?? '').toString().trim();
            if (val === '') url.searchParams.delete(k);
            else url.searchParams.set(k, val);
          });

          return url;
        }

        async function fetchLessons(url, { pushUrl = true } = {}) {
          if (abortCtrl) abortCtrl.abort();
          abortCtrl = new AbortController();

          try {
            const res = await fetch(url.toString(), {
              headers: { 'X-Requested-With': 'XMLHttpRequest' },
              signal: abortCtrl.signal
            });

            if (!res.ok) throw new Error('Request failed: ' + res.status);

            const data = await res.json();
            tbody.innerHTML = data.tbody ?? '';
            pagination.innerHTML = data.pagination ?? '';
            if (typeof data.total !== 'undefined') totalEl.textContent = data.total;

            if (pushUrl) {
              window.history.replaceState({}, '', url.pathname + url.search);
            }
          } catch (e) {
            if (e.name === 'AbortError') return;
            console.error(e);
          }
        }

        function scheduleFetch(extraParams = {}) {
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(() => {
            const url = buildUrl(form.action, extraParams);
            fetchLessons(url, { pushUrl: true });
          }, 250);
        }

        function setSectionOptions(items, keepSelected = true) {
          const current = keepSelected ? selectSection.value : '';
          selectSection.innerHTML = '';

          const optAll = document.createElement('option');
          optAll.value = '';
          optAll.textContent = 'All';
          selectSection.appendChild(optAll);

          items.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = (s.title_en || '') + (s.title_ar ? ' — ' + s.title_ar : '');
            selectSection.appendChild(opt);
          });

          if (keepSelected && current) {
            const exists = Array.from(selectSection.options).some(o => o.value === current);
            selectSection.value = exists ? current : '';
          } else {
            selectSection.value = '';
          }
        }

        async function loadSectionsForMaterial(materialId, { keepSelected = false } = {}) {
          if (!materialId) {
            setSectionOptions([], false);
            return;
          }

          const url = sectionsEndpointTemplate.replace('___ID___', materialId);

          try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            setSectionOptions(Array.isArray(data) ? data : [], keepSelected);
          } catch (e) {
            console.error(e);
            setSectionOptions([], false);
          }
        }

        // ✅ Typing search (debounced)
        inputQ.addEventListener('input', () => scheduleFetch({ page: 1 }));

        // ✅ Material change: load sections + reset section + fetch
        selectMaterial.addEventListener('change', async () => {
          await loadSectionsForMaterial(selectMaterial.value, { keepSelected: false });
          scheduleFetch({ page: 1, section_id: '' });
        });

        // ✅ Section change
        selectSection.addEventListener('change', () => scheduleFetch({ page: 1 }));

        // ✅ Apply button (prevent full refresh)
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          scheduleFetch({ page: 1 });
        });

        // ✅ Pagination links (AJAX)
        pagination.addEventListener('click', (e) => {
          const a = e.target.closest('a');
          if (!a) return;
          const href = a.getAttribute('href');
          if (!href) return;

          e.preventDefault();
          const url = new URL(href, window.location.origin);
          fetchLessons(url, { pushUrl: true });
        });

        // ✅ On initial load: if material selected but sections empty (edge case)
        if (selectMaterial.value && selectSection.options.length <= 1) {
          loadSectionsForMaterial(selectMaterial.value, { keepSelected: true });
        }
      })();
    </script>
  @endpush
@endsection
