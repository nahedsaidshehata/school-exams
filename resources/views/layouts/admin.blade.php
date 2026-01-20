{{-- resources/views/layouts/admin.blade.php --}}
<!doctype html>
@php
  $locale = app()->getLocale();
  $isRtl = in_array($locale, ['ar', 'fa', 'ur']);
  $dir = $isRtl ? 'rtl' : 'ltr';

  // ✅ Safe named routes (avoid breaks)
  $r = function($name, $fallback = '#') {
    return \Illuminate\Support\Facades\Route::has($name) ? route($name) : $fallback;
  };

  $dashboardUrl = $r('admin.dashboard', url('/admin/dashboard'));
  $schoolsUrl   = $r('admin.schools.index', url('/admin/schools'));
  $studentsUrl  = $r('admin.students.index', url('/admin/students'));

  // ✅ Keep routes/DB as-is (materials), change UI label to Subjects
  $subjectsUrl  = $r('admin.materials.index', url('/admin/materials'));
  $sectionsUrl  = $r('admin.sections.index', url('/admin/sections'));
  $lessonsUrl   = $r('admin.lessons.index', url('/admin/lessons'));

  // ✅ Learning Outcomes link
  $learningOutcomesUrl = $r('admin.learning_outcomes.index', url('/admin/learning_outcomes'));

  $questionsUrl = $r('admin.questions.index', url('/admin/questions'));
  $examsUrl     = $r('admin.exams.index', url('/admin/exams'));
@endphp
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', __('Admin')) - {{ config('app.name', 'School Exams') }}</title>

  {{-- Bootstrap (CDN) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

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

  <style>
    html, body { pointer-events: auto !important; opacity: 1 !important; filter: none !important; }
    html::before, html::after, body::before, body::after { content: none !important; pointer-events: none !important; }
    .offcanvas-backdrop, .modal-backdrop, .sidebar-overlay, .overlay, [data-ui-overlay] {
      display: none !important; pointer-events: none !important;
    }
    body.admin-ui { background: #f6f7fb; }
    .admin-navbar { border-bottom: 1px solid rgba(0,0,0,.06); background: #ffffff; }
    .admin-brand-badge {
      width: 34px; height: 34px; border-radius: 10px;
      display: inline-flex; align-items: center; justify-content: center;
      font-weight: 700; background: #0d6efd; color: #fff;
    }
    .admin-shell { min-height: calc(100vh - 56px); }
    .admin-page-title { font-weight: 700; letter-spacing: .2px; }
    .admin-card { border: 1px solid rgba(0,0,0,.06); box-shadow: 0 6px 18px rgba(0,0,0,.04); border-radius: 14px; }
    .admin-table thead th { background: rgba(13,110,253,.06); border-bottom: 1px solid rgba(0,0,0,.06) !important; font-weight: 700; }
  </style>

  @stack('head')
</head>

<body class="admin-ui {{ $isRtl ? 'rtl' : 'ltr' }}">
  <nav class="navbar navbar-expand-lg admin-navbar sticky-top">
    <div class="container-fluid px-3">
      <a class="navbar-brand d-flex align-items-center gap-2" href="{{ $dashboardUrl }}">
        <span class="admin-brand-badge">A</span>
        <div class="d-flex flex-column lh-sm">
          <span class="fw-bold">{{ config('app.name', 'School Exams') }}</span>
          <small class="text-muted">{{ __('Admin Panel') }}</small>
        </div>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar"
              aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="adminNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active fw-semibold' : '' }}"
               href="{{ $dashboardUrl }}">{{ __('Dashboard') }}</a>
          </li>

          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.schools.*') ? 'active fw-semibold' : '' }}"
               href="{{ $schoolsUrl }}">{{ __('Schools') }}</a>
          </li>

          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.students.*') ? 'active fw-semibold' : '' }}"
               href="{{ $studentsUrl }}">{{ __('Students') }}</a>
          </li>

          {{-- Content bank --}}
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle {{
                request()->routeIs('admin.materials.*') ||
                request()->routeIs('admin.sections.*') ||
                request()->routeIs('admin.lessons.*') ||
                request()->routeIs('admin.learning_outcomes.*')
                ? 'active fw-semibold' : ''
              }}"
               href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              {{ __('Content') }}
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="{{ $subjectsUrl }}">{{ __('Subjects') }}</a></li>
              <li><a class="dropdown-item" href="{{ $sectionsUrl }}">{{ __('Sections') }}</a></li>
              <li><a class="dropdown-item" href="{{ $lessonsUrl }}">{{ __('Lessons') }}</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="{{ $learningOutcomesUrl }}">{{ __('Learning Outcomes') }}</a></li>
            </ul>
          </li>

          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.questions.*') ? 'active fw-semibold' : '' }}"
               href="{{ $questionsUrl }}">{{ __('Questions') }}</a>
          </li>

          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.exams.*') ? 'active fw-semibold' : '' }}"
               href="{{ $examsUrl }}">{{ __('Exams') }}</a>
          </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small d-none d-lg-inline">{{ strtoupper($locale) }}</span>

          <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
              {{ auth()->user()->name ?? __('Admin') }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="{{ $dashboardUrl }}">{{ __('Dashboard') }}</a></li>
              <li><hr class="dropdown-divider"></li>
              @if(\Illuminate\Support\Facades\Route::has('logout'))
                <li>
                  <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item" type="submit">{{ __('Logout') }}</button>
                  </form>
                </li>
              @endif
            </ul>
          </div>
        </div>

      </div>
    </div>
  </nav>

  <div class="admin-shell">
    <div class="container-fluid px-3 px-lg-4 py-3">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
          <h1 class="h4 mb-0 admin-page-title">
            @yield('page_title', trim($__env->yieldContent('title')) ?: __('Admin'))
          </h1>
          @hasSection('page_subtitle')
            <div class="text-muted small mt-1">@yield('page_subtitle')</div>
          @endif
        </div>

        @hasSection('page_actions')
          <div class="d-flex gap-2">
            @yield('page_actions')
          </div>
        @endif
      </div>

      @if ($errors->any())
        <div class="alert alert-danger">
          <div class="fw-semibold mb-1">{{ __('Please fix the following errors:') }}</div>
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
      @if (session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif
      @if (session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
      @if (session('info'))    <div class="alert alert-info">{{ session('info') }}</div> @endif

      @yield('content')
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    (function () {
      try {
        document.documentElement.classList.remove('student-sidebar-open');
        document.body.classList.remove('modal-open', 'offcanvas-open', 'sidebar-open');
        document.querySelectorAll('.offcanvas-backdrop,.modal-backdrop,.sidebar-overlay,.overlay,[data-ui-overlay]')
          .forEach(function (el) { el.remove(); });
        document.documentElement.style.pointerEvents = 'auto';
        document.body.style.pointerEvents = 'auto';
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
      } catch (e) {}
    })();
  </script>

  @stack('scripts')
</body>
</html>
