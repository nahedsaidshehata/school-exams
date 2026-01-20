{{-- resources/views/admin/sections/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Sections Management'))
@section('page_title', __('Sections'))
@section('page_subtitle')
  {{ __('Manage sections under each subject in the central content bank.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.sections.create') }}" class="btn btn-success btn-sm">
    {{ __('Create Section') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .table td, .table th { vertical-align: middle; }
      .truncate {
        max-width: 280px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .btn-icon { padding: .35rem .55rem; border-radius: 10px; }

      .filters-grid{
        display: grid;
        grid-template-columns: 280px 1fr auto auto;
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
          <span class="fw-semibold" id="sectionsTotal">{{ method_exists($sections, 'total') ? $sections->total() : '' }}</span>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
          {{ __('Back to Dashboard') }}
        </a>
      </div>

      {{-- ✅ Filters + Search (AJAX) --}}
      <form id="sectionsFiltersForm" method="GET" action="{{ route('admin.sections.index') }}" class="mb-3">
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
            <label class="form-label mb-1">{{ __('Search') }}</label>
            <input
              type="text"
              name="q"
              id="q"
              value="{{ $filters['q'] ?? '' }}"
              class="form-control form-control-sm"
              placeholder="{{ __('Section title (EN/AR) or subject name...') }}"
              autocomplete="off"
            >
          </div>

          <div>
            <button type="submit" class="btn btn-primary btn-sm w-100">
              {{ __('Apply') }}
            </button>
          </div>

          <div>
            <a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary btn-sm w-100" id="sectionsResetBtn">
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
              <th>{{ __('Subject') }}</th>
              <th class="text-nowrap">{{ __('Created') }}</th>
              <th class="text-nowrap text-end">{{ __('Actions') }}</th>
            </tr>
          </thead>

          <tbody id="sectionsTbody">
            @include('admin.sections.partials.rows', ['sections' => $sections])
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-3" id="sectionsPagination">
        @include('admin.sections.partials.pagination', ['sections' => $sections])
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      (function () {
        const form = document.getElementById('sectionsFiltersForm');
        const inputQ = document.getElementById('q');
        const selectMaterial = document.getElementById('material_id');

        const tbody = document.getElementById('sectionsTbody');
        const pagination = document.getElementById('sectionsPagination');
        const totalEl = document.getElementById('sectionsTotal');

        let debounceTimer = null;
        let abortCtrl = null;

        function buildUrl(baseUrl, extraParams = {}) {
          const url = new URL(baseUrl, window.location.origin);
          const formData = new FormData(form);

          // current form params
          for (const [k, v] of formData.entries()) {
            const val = (v || '').toString().trim();
            if (val !== '') url.searchParams.set(k, val);
          }

          // override / add extra
          Object.keys(extraParams).forEach(k => {
            const val = (extraParams[k] ?? '').toString().trim();
            if (val === '') url.searchParams.delete(k);
            else url.searchParams.set(k, val);
          });

          return url;
        }

        async function fetchSections(url, { pushUrl = true } = {}) {
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

            // ✅ update URL (so refresh keeps filters)
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
            fetchSections(url, { pushUrl: true });
          }, 250);
        }

        // ✅ Search typing (debounced)
        inputQ.addEventListener('input', () => scheduleFetch({ page: 1 }));

        // ✅ Material change
        selectMaterial.addEventListener('change', () => scheduleFetch({ page: 1 }));

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
          fetchSections(url, { pushUrl: true });
        });
      })();
    </script>
  @endpush
@endsection
