{{-- resources/views/admin/students/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Students Management'))
@section('page_title', __('Students'))
@section('page_subtitle')
  {{ __('Create and manage students across schools.') }}
@endsection

@php
  $cardsPdfUrl = \Illuminate\Support\Facades\Route::has('admin.students.cards.pdf')
      ? route('admin.students.cards.pdf')
      : url('/admin/students/cards/pdf');

  $rotateQrUrl = \Illuminate\Support\Facades\Route::has('admin.students.cards.rotate')
      ? route('admin.students.cards.rotate')
      : url('/admin/students/cards/rotate');
@endphp

@section('page_actions')
  <div class="d-flex gap-2">
    <form id="cardsPrintForm" method="POST" action="{{ $cardsPdfUrl }}" target="_blank" class="m-0">
      @csrf
      <div id="cardsPrintIds"></div>
      <button type="submit" id="btnPrintCards" class="btn btn-primary btn-sm" disabled>
        {{ __('Print Cards (PDF)') }}
      </button>
    </form>

    <form id="cardsRotateForm" method="POST" action="{{ $rotateQrUrl }}" class="m-0">
      @csrf
      <div id="cardsRotateIds"></div>
      <button type="submit" id="btnRotateQr" class="btn btn-outline-danger btn-sm" disabled>
        {{ __('Rotate QR') }}
      </button>
    </form>

    <a href="{{ route('admin.students.import.form') }}" class="btn btn-outline-primary btn-sm">
      {{ __('Import XLSX') }}
    </a>
    <a href="{{ route('admin.students.create') }}" class="btn btn-success btn-sm">
      {{ __('Create Student') }}
    </a>
  </div>
@endsection

@section('content')
  @push('head')
    <style>
      .table td, .table th { vertical-align: middle; }
      .truncate { max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
      .pill {
        display: inline-flex; align-items: center; gap: .35rem; padding: .15rem .55rem;
        border-radius: 999px; font-size: .85rem; border: 1px solid rgba(0,0,0,.08);
        background: rgba(0,0,0,.02); white-space: nowrap;
      }
      .sticky-actions { position: sticky; top: 0; z-index: 2; background: #fff; }

      /* Filters UI */
      .filters-bar .form-control, .filters-bar .form-select { min-width: 160px; }
      .filters-bar .form-control.q { min-width: 260px; }

      /* Top horizontal scrollbar */
      .hscroll-top {
        overflow-x: auto;
        overflow-y: hidden;
        height: 14px;
        border-radius: 8px;
        background: rgba(0,0,0,.03);
      }
      .hscroll-top .hscroll-spacer {
        height: 1px; /* just to create width */
      }
    </style>
  @endpush

  <div class="card admin-card">
    <div class="card-body">

      {{-- ❌ تم إزالة session('success') من هنا لأن الـ layout يعرضه بالفعل --}}
      {{-- ✅ نترك فقط رسائل الاستيراد الخاصة بهذه الصفحة --}}

      @if(session('import_errors') && is_array(session('import_errors')) && count(session('import_errors')) > 0)
        <div class="alert alert-warning">
          <div class="fw-semibold mb-1">Import Errors (first 20):</div>
          <ul class="mb-0">
            @foreach(array_slice(session('import_errors'), 0, 20) as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if(session('import_creds') && is_array(session('import_creds')) && count(session('import_creds')) > 0)
        <div class="alert alert-info">
          <div class="fw-semibold mb-2">Generated / Imported Credentials (first 20):</div>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Row</th><th>School</th><th>Student</th><th>Username</th><th>Password</th>
                </tr>
              </thead>
              <tbody>
                @foreach(array_slice(session('import_creds'), 0, 20) as $c)
                  <tr>
                    <td class="text-muted">{{ $c['row'] }}</td>
                    <td class="truncate" title="{{ $c['school'] }}">{{ $c['school'] }}</td>
                    <td class="truncate" title="{{ $c['name'] ?? '' }}">{{ $c['name'] ?? '—' }}</td>
                    <td class="mono">{{ $c['username'] }}</td>
                    <td class="mono">{{ $c['password'] }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="small text-muted mt-2">
            Tip: later we can switch to random passwords + export the creds to XLSX for security.
          </div>
        </div>
      @endif

      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="text-muted small">
          {{ __('Total:') }} <span class="fw-semibold">{{ method_exists($students, 'total') ? $students->total() : '' }}</span>
          <span class="ms-2">•</span>
          <span class="ms-2">{{ __('Selected:') }} <span class="fw-semibold" id="selectedCount">0</span></span>
        </div>

        <div class="d-flex gap-2">
          <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            {{ __('Back to Dashboard') }}
          </a>
        </div>
      </div>

      {{-- ✅ Filters UI (Top of table) --}}
      <form method="GET" action="{{ route('admin.students.index') }}" class="filters-bar mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-end">

          <div>
            <label class="form-label small text-muted mb-1">{{ __('School') }}</label>
            <select name="school_id" class="form-select form-select-sm">
              <option value="">{{ __('All Schools') }}</option>
              @foreach(($schools ?? []) as $s)
                <option value="{{ $s->id }}" @selected(($filters['school_id'] ?? '') === $s->id)>
                  {{ $s->name_en }}
                </option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="form-label small text-muted mb-1">{{ __('Grade') }}</label>
            <select name="grade" class="form-select form-select-sm">
              <option value="">{{ __('All') }}</option>
              @foreach(($grades ?? []) as $g)
                <option value="{{ $g }}" @selected(($filters['grade'] ?? '') === $g)>{{ $g }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="form-label small text-muted mb-1">{{ __('Academic Year') }}</label>
            <select name="year" class="form-select form-select-sm">
              <option value="">{{ __('All') }}</option>
              @foreach(($years ?? []) as $y)
                <option value="{{ $y }}" @selected(($filters['year'] ?? '') === $y)>{{ $y }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="form-label small text-muted mb-1">{{ __('Gender') }}</label>
            <select name="gender" class="form-select form-select-sm">
              <option value="">{{ __('All') }}</option>
              <option value="male" @selected(($filters['gender'] ?? '') === 'male')>male</option>
              <option value="female" @selected(($filters['gender'] ?? '') === 'female')>female</option>
            </select>
          </div>

          <div>
            <label class="form-label small text-muted mb-1">{{ __('SEND') }}</label>
            <select name="send" class="form-select form-select-sm">
              <option value="">{{ __('All') }}</option>
              <option value="1" @selected(($filters['send'] ?? '') === '1')>Yes</option>
              <option value="0" @selected(($filters['send'] ?? '') === '0')>No</option>
            </select>
          </div>

          <div>
            <label class="form-label small text-muted mb-1">{{ __('Nationality') }}</label>
            <select name="nationality" class="form-select form-select-sm">
              <option value="">{{ __('All') }}</option>
              @foreach(($nationalities ?? []) as $n)
                <option value="{{ $n }}" @selected(($filters['nationality'] ?? '') === $n)>{{ $n }}</option>
              @endforeach
            </select>
          </div>

          <div class="flex-grow-1">
            <label class="form-label small text-muted mb-1">{{ __('Search') }}</label>
            <input
              type="text"
              name="q"
              value="{{ $filters['q'] ?? '' }}"
              class="form-control form-control-sm q"
              placeholder="{{ __('name, username, email, parent email...') }}"
            >
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" type="submit">{{ __('Apply') }}</button>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.students.index') }}">{{ __('Reset') }}</a>
          </div>

        </div>
      </form>

      {{-- ✅ Top horizontal scrollbar (synced) --}}
      <div id="studentsHScrollTop" class="hscroll-top mb-2" aria-hidden="true">
        <div id="studentsHScrollSpacer" class="hscroll-spacer"></div>
      </div>

      <div id="studentsTableWrap" class="table-responsive">
        <table class="table admin-table table-hover align-middle mb-0" id="studentsTable">
          <thead class="sticky-actions">
            <tr>
              <th style="width:34px;">
                <input type="checkbox" id="checkAll">
              </th>

              <th>{{ __('Username') }}</th>
              <th>{{ __('Email') }}</th>
              <th>{{ __('Full Name') }}</th>

              <th>{{ __('Academic Year') }}</th>
              <th>{{ __('Grade') }}</th>
              <th>{{ __('Gender') }}</th>
              <th>{{ __('SEND') }}</th>
              <th>{{ __("Parent's Email") }}</th>
              <th>{{ __('Nationality') }}</th>

              <th>{{ __('School') }}</th>
              <th class="text-nowrap">{{ __('Created') }}</th>
              <th class="text-nowrap">{{ __('Card') }}</th>
            </tr>
          </thead>

          <tbody>
            @forelse($students as $student)
              @php $p = $student->studentProfile; @endphp
              <tr>
                <td>
                  <input type="checkbox" class="row-check" value="{{ $student->id }}">
                </td>

                <td class="text-nowrap">
                  <span class="fw-semibold">{{ $student->username }}</span>
                </td>

                <td class="truncate" title="{{ $student->email ?? '' }}">
                  {{ $student->email ?? 'N/A' }}
                </td>

                <td class="truncate" title="{{ $student->full_name ?? '' }}">
                  {{ $student->full_name ?? 'N/A' }}
                </td>

                <td class="text-nowrap">{{ $p?->year ?? '—' }}</td>
                <td class="text-nowrap">{{ $p?->grade ?? '—' }}</td>

                <td class="text-nowrap">
                  @if($p?->gender === 'male')
                    <span class="pill">male</span>
                  @elseif($p?->gender === 'female')
                    <span class="pill">female</span>
                  @else
                    —
                  @endif
                </td>

                <td class="text-nowrap">
                  @if(($p?->send ?? false) === true)
                    <span class="pill">Yes</span>
                  @else
                    <span class="pill">No</span>
                  @endif
                </td>

                <td class="truncate" title="{{ $p?->parent_email ?? '' }}">
                  {{ $p?->parent_email ?? 'N/A' }}
                </td>

                <td class="text-nowrap">{{ $p?->nationality ?? '—' }}</td>

                <td class="truncate" title="{{ $student->school->name_en ?? '' }}">
                  {{ $student->school->name_en ?? 'N/A' }}
                </td>

                <td class="text-nowrap">
                  {{ optional($student->created_at)->format('Y-m-d') }}
                </td>

                <td class="text-nowrap">
                  <form method="POST" action="{{ $cardsPdfUrl }}" target="_blank" class="m-0">
                    @csrf
                    <input type="hidden" name="student_ids[]" value="{{ $student->id }}">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                      {{ __('Card') }}
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="13" class="text-center py-4 text-muted">
                  {{ __('No students found.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- ✅ Pagination only when NOT filtered --}}
      @if(empty($isFiltered))
        <div class="d-flex justify-content-center mt-3">
          {{ $students->links('pagination::bootstrap-5') }}
        </div>
      @endif

    </div>
  </div>

  @push('scripts')
    <script>
      (function () {
        // checkbox selection logic (as-is)
        const checkAll = document.getElementById('checkAll');
        const checks = () => Array.from(document.querySelectorAll('.row-check'));
        const selectedCount = document.getElementById('selectedCount');

        const btnPrint = document.getElementById('btnPrintCards');
        const btnRotate = document.getElementById('btnRotateQr');

        const printIdsBox = document.getElementById('cardsPrintIds');
        const rotateIdsBox = document.getElementById('cardsRotateIds');

        function getSelectedIds() {
          return checks().filter(ch => ch.checked).map(ch => ch.value);
        }

        function sync() {
          const ids = getSelectedIds();
          selectedCount.textContent = ids.length;

          btnPrint.disabled = ids.length === 0;
          btnRotate.disabled = ids.length === 0;

          printIdsBox.innerHTML = '';
          rotateIdsBox.innerHTML = '';
          ids.forEach(id => {
            const i1 = document.createElement('input');
            i1.type = 'hidden'; i1.name = 'student_ids[]'; i1.value = id;
            printIdsBox.appendChild(i1);

            const i2 = document.createElement('input');
            i2.type = 'hidden'; i2.name = 'student_ids[]'; i2.value = id;
            rotateIdsBox.appendChild(i2);
          });
        }

        if (checkAll) {
          checkAll.addEventListener('change', function () {
            checks().forEach(ch => ch.checked = checkAll.checked);
            sync();
          });
        }

        document.addEventListener('change', function (e) {
          if (e.target && e.target.classList.contains('row-check')) {
            const all = checks();
            const allChecked = all.length > 0 && all.every(ch => ch.checked);
            if (checkAll) checkAll.checked = allChecked;
            sync();
          }
        });

        sync();

        // ✅ top horizontal scrollbar sync
        const top = document.getElementById('studentsHScrollTop');
        const spacer = document.getElementById('studentsHScrollSpacer');
        const wrap = document.getElementById('studentsTableWrap');

        if (top && spacer && wrap) {
          function syncWidths() {
            // match spacer width to the full scrollable width of table wrapper
            spacer.style.width = wrap.scrollWidth + 'px';
          }

          let lock = false;

          top.addEventListener('scroll', function () {
            if (lock) return;
            lock = true;
            wrap.scrollLeft = top.scrollLeft;
            lock = false;
          });

          wrap.addEventListener('scroll', function () {
            if (lock) return;
            lock = true;
            top.scrollLeft = wrap.scrollLeft;
            lock = false;
          });

          window.addEventListener('resize', syncWidths);
          // initial
          syncWidths();
          // a tiny delay for fonts/layout
          setTimeout(syncWidths, 50);
        }
      })();
    </script>
  @endpush
@endsection
