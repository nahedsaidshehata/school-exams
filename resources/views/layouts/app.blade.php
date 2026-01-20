{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
@php
  $locale = app()->getLocale();
  $isRtl = in_array($locale, ['ar', 'fa', 'ur']);
  $dir = $isRtl ? 'rtl' : 'ltr';

  // ✅ Detect if this is a student area
  $isStudentArea = request()->is('student*') || request()->routeIs('student.*');

  // ✅ Safe routes (avoid 404 if route missing for any reason) - only for student area
  if ($isStudentArea) {
    $dashboardUrl = \Illuminate\Support\Facades\Route::has('student.dashboard')
        ? route('student.dashboard')
        : url('/student/dashboard');

    $examsUrl = \Illuminate\Support\Facades\Route::has('student.exams.index')
        ? route('student.exams.index')
        : url('/student/exams');

    $helpUrl = \Illuminate\Support\Facades\Route::has('student.help')
        ? route('student.help')
        : null;
  }
@endphp
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', config('app.name', 'School Exams'))</title>

  {{-- Prefer existing app assets if present (Vite), else fallback to common app.css/app.js --}}
  @if (file_exists(public_path('build/manifest.json')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  @else
    @if (file_exists(public_path('css/app.css')))
      <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @endif
    @if (file_exists(public_path('js/app.js')))
      <script defer src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @endif
  @endif

  {{-- SPRINT 6 assets - ONLY load on student pages --}}
  @if($isStudentArea)
    <link rel="stylesheet" href="{{ asset('css/student-ui.css') }}?v={{ filemtime(public_path('css/student-ui.css')) }}">
    <script defer src="{{ asset('js/student-ui.js') }}?v={{ filemtime(public_path('js/student-ui.js')) }}"></script>

    {{-- Minimal brand styling for student header (in case this layout is used) --}}
    <style>
      .student-brand{display:flex;align-items:center;gap:12px;user-select:none}
      .brand-logo{width:38px;height:38px;border-radius:12px;background:#fff;border:1px solid rgba(15,23,42,.08);display:flex;align-items:center;justify-content:center;overflow:hidden}
      .brand-logo img{width:100%;height:100%;object-fit:contain;padding:6px}
      .brand-title{font-weight:900;letter-spacing:.2px;line-height:1.05}
      .brand-subtitle{color:#64748b;font-size:.85rem;line-height:1.05}
    </style>
  @endif

  {{-- ✅ Non-student CSS guard: Safe & targeted --}}
  @if(!$isStudentArea)
    <style>
      html, body {
        pointer-events: auto !important;
        opacity: 1 !important;
        filter: none !important;
      }

      /* Prevent root pseudo-elements from blocking clicks */
      html::before, html::after,
      body::before, body::after {
        content: none !important;
        pointer-events: none !important;
      }

      /* Known overlays/backdrops must never block admin/school pages */
      .offcanvas-backdrop,
      .modal-backdrop,
      .sidebar-overlay,
      .overlay,
      [data-ui-overlay] {
        display: none !important;
        pointer-events: none !important;
      }
    </style>
  @endif

  @stack('head')
</head>

<body class="{{ $isStudentArea ? 'student-ui' : '' }} {{ $isRtl ? 'rtl' : 'ltr' }}">
@if($isStudentArea)

  {{-- ================= STUDENT UI ================= --}}
  <header class="student-header border-bottom">
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-between py-2">
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-secondary d-lg-none"
                  type="button"
                  onclick="document.documentElement.classList.toggle('student-sidebar-open')"
                  aria-label="Toggle menu">
            ☰
          </button>

          <div class="student-brand">
            <div class="brand-logo" aria-hidden="true">
              <img src="{{ asset('images/logo.png') }}" alt="ILM-EDU">
            </div>
            <div class="brand-text">
              <div class="brand-title">ILM-EDU</div>
              <div class="brand-subtitle">{{ __('Student Portal') }}</div>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <div class="d-none d-md-flex align-items-center gap-1">
            <span class="text-muted small">{{ strtoupper($locale) }}</span>
          </div>

          <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                    data-bs-toggle="dropdown"
                    type="button">
              {{ auth()->user()->name ?? __('Student') }}
            </button>

            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="{{ $dashboardUrl }}">{{ __('Dashboard') }}</a>
              </li>
              <li>
                <a class="dropdown-item" href="{{ $examsUrl }}">{{ __('Exams') }}</a>
              </li>

              @if($helpUrl)
                <li>
                  <a class="dropdown-item" href="{{ $helpUrl }}">{{ __('Help') }}</a>
                </li>
              @endif

              <li><hr class="dropdown-divider"></li>
              <li>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button class="dropdown-item" type="submit">{{ __('Logout') }}</button>
                </form>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </header>

  <div class="student-shell">
    <aside class="student-sidebar">
      <nav class="student-nav">
        <a class="student-nav-link {{ request()->is('student/dashboard') ? 'active' : '' }}"
           href="{{ $dashboardUrl }}">
          <span class="nav-ico">🏠</span>
          <span>{{ __('Dashboard') }}</span>
        </a>

        <a class="student-nav-link {{ request()->is('student/exams*') ? 'active' : '' }}"
           href="{{ $examsUrl }}">
          <span class="nav-ico">📝</span>
          <span>{{ __('Exams') }}</span>
        </a>

        <div class="nav-section">
          <div class="nav-section-title">{{ __('Support') }}</div>

          @if($helpUrl)
            <a class="student-nav-link {{ request()->is('student/help*') ? 'active' : '' }}"
               href="{{ $helpUrl }}">
              <span class="nav-ico">❓</span>
              <span>{{ __('Help') }}</span>
            </a>
          @endif
        </div>
      </nav>
    </aside>

    <main class="student-main">
      <div class="container-fluid py-3">
        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
      </div>
    </main>
  </div>

@else

  {{-- ================= NON-STUDENT UI ================= --}}
  <div class="container" style="padding: 20px;">
    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @yield('content')
  </div>

  {{-- Minimal cleanup: remove leftover overlay artifacts --}}
  <script>
    (function () {
      try {
        document.documentElement.classList.remove('student-sidebar-open');
        document.body.classList.remove('modal-open', 'offcanvas-open', 'sidebar-open');

        document.querySelectorAll(
          '.offcanvas-backdrop,.modal-backdrop,.sidebar-overlay,.overlay,[data-ui-overlay]'
        ).forEach(function (el) {
          el.remove();
        });

        document.documentElement.style.pointerEvents = 'auto';
        document.body.style.pointerEvents = 'auto';
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
      } catch (e) {}
    })();
  </script>

@endif

@stack('scripts')
</body>
</html>
