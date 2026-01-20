{{-- resources/views/admin/schools/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Schools Management'))
@section('page_title', __('Schools'))
@section('page_subtitle')
  {{ __('Create and manage schools and their accounts.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.schools.create') }}" class="btn btn-success btn-sm">
    {{ __('Create School') }}
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
      .search-wrap .form-control { min-width: 260px; }
      .search-loading {
        opacity: 0.6;
        pointer-events: none;
      }
    </style>
  @endpush

  <div class="card admin-card">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="text-muted small">
          {{ __('Total:') }}
          <span class="fw-semibold" id="schoolsTotal">
            {{ method_exists($schools, 'total') ? $schools->total() : '' }}
          </span>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
          {{-- Live Search --}}
          <form method="GET" action="{{ route('admin.schools.index') }}" class="d-flex gap-2 search-wrap" id="schoolSearchForm">
            <input
              type="text"
              name="q"
              value="{{ old('q', $q ?? request('q')) }}"
              class="form-control form-control-sm"
              placeholder="{{ __('Search school...') }}"
              aria-label="{{ __('Search school') }}"
              id="schoolSearchInput"
              autocomplete="off"
            >
            <a href="{{ route('admin.schools.index') }}" class="btn btn-outline-secondary btn-sm" id="schoolSearchClear" style="{{ !empty($q) ? '' : 'display:none;' }}">
              {{ __('Clear') }}
            </a>
          </form>

          <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            {{ __('Back to Dashboard') }}
          </a>
        </div>
      </div>

      <div class="table-responsive" id="schoolsTableWrap">
        <table class="table admin-table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>{{ __('Name (EN)') }}</th>
              <th>{{ __('Name (AR)') }}</th>
              <th>{{ __('School Account') }}</th>
              <th class="text-nowrap">{{ __('Created') }}</th>
                <th class="text-nowrap text-end">{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody id="schoolsTbody">
            @include('admin.schools.partials.rows', ['schools' => $schools])
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-3" id="schoolsPagination">
        {{ $schools->links() }}
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      (function () {
        const form = document.getElementById('schoolSearchForm');
        const input = document.getElementById('schoolSearchInput');
        const clearBtn = document.getElementById('schoolSearchClear');

        const tbody = document.getElementById('schoolsTbody');
        const pagination = document.getElementById('schoolsPagination');
        const totalEl = document.getElementById('schoolsTotal');
        const wrap = document.getElementById('schoolsTableWrap');

        let debounceTimer = null;
        let abortController = null;

        function setLoading(isLoading) {
          if (isLoading) wrap.classList.add('search-loading');
          else wrap.classList.remove('search-loading');
        }

        function buildUrl(q, pageUrl = null) {
          // Use current index route, preserve page if passed
          const base = pageUrl ? new URL(pageUrl, window.location.origin) : new URL(form.getAttribute('action'), window.location.origin);
          if (q && q.trim() !== '') base.searchParams.set('q', q.trim());
          else base.searchParams.delete('q');

          return base.toString();
        }

        async function fetchSchools(url) {
          // cancel previous
          if (abortController) abortController.abort();
          abortController = new AbortController();

          setLoading(true);
          try {
            const res = await fetch(url, {
              method: 'GET',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
              },
              signal: abortController.signal
            });

            if (!res.ok) throw new Error('Request failed');

            const data = await res.json();

            tbody.innerHTML = data.rows ?? '';
            pagination.innerHTML = data.pagination ?? '';
            if (typeof data.total !== 'undefined' && data.total !== null) {
              totalEl.textContent = data.total;
            }

            const q = input.value.trim();
            clearBtn.style.display = q ? '' : 'none';

            // update browser URL (no reload)
            const newUrl = buildUrl(q);
            window.history.replaceState({}, '', newUrl);

          } catch (e) {
            // ignore abort
            if (e.name !== 'AbortError') {
              console.error(e);
            }
          } finally {
            setLoading(false);
          }
        }

        function onInputChange() {
          const q = input.value || '';
          clearBtn.style.display = q.trim() ? '' : 'none';

          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(() => {
            const url = buildUrl(q);
            fetchSchools(url);
          }, 300);
        }

        // live typing
        input.addEventListener('input', onInputChange);

        // clear button
        clearBtn.addEventListener('click', function (e) {
          e.preventDefault();
          input.value = '';
          clearBtn.style.display = 'none';
          const url = buildUrl('');
          fetchSchools(url);
        });

        // handle pagination clicks via delegation (AJAX)
        pagination.addEventListener('click', function (e) {
          const a = e.target.closest('a');
          if (!a) return;

          // Laravel pagination links
          const href = a.getAttribute('href');
          if (!href) return;

          e.preventDefault();
          const q = input.value || '';
          // keep q even when clicking page link
          const url = buildUrl(q, href);
          fetchSchools(url);
        });

        // prevent normal submit (no refresh)
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          const url = buildUrl(input.value || '');
          fetchSchools(url);
        });
      })();
    </script>
  @endpush
@endsection
