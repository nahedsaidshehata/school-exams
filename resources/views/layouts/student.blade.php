{{-- resources/views/layouts/student.blade.php --}}
<!doctype html>
@php
  $locale = app()->getLocale();
  $isRtl = in_array($locale, ['ar', 'fa', 'ur']);
  $dir = $isRtl ? 'rtl' : 'ltr';
@endphp
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', __('Student'))</title>

  {{-- Bootstrap (guaranteed) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  {{-- SPRINT 6 assets (already exist) --}}
  <link rel="stylesheet" href="{{ asset('css/student-ui.css') }}">
  <script defer src="{{ asset('js/student-ui.js') }}"></script>

  {{-- ✅ Student UI v2 (Shared Components + RTL/LTR safe) --}}
  <style>
    :root{
      --stu-bg: #f6f8fb;
      --stu-card: #ffffff;
      --stu-text: #0f172a;
      --stu-muted: #64748b;
      --stu-border: rgba(15, 23, 42, .08);
      --stu-soft: rgba(13,110,253,.08);
      --stu-radius: 18px;
      --stu-shadow: 0 10px 30px rgba(15,23,42,.06);
      --stu-shadow-sm: 0 6px 18px rgba(15,23,42,.06);
    }

    body.student-ui{
      background: var(--stu-bg);
      color: var(--stu-text);
    }

    /* Header */
    .student-header{
      background: rgba(255,255,255,.92);
      backdrop-filter: blur(10px);
    }

    /* Shell */
    .student-shell{
      display:flex;
      min-height: calc(100vh - 56px);
    }

    /* Sidebar */
    .student-sidebar{
      width: 260px;
      background: #fff;
      border-inline-end: 1px solid var(--stu-border);
    }
    .student-main{
      flex:1;
      min-width: 0;
    }

    /* Mobile sidebar */
    @media (max-width: 991.98px){
      .student-sidebar{
        position: fixed;
        inset-block: 56px 0;
        inset-inline-start: 0;
        transform: translateX(-110%);
        transition: transform .22s ease;
        z-index: 1030;
        box-shadow: var(--stu-shadow);
      }
      html[dir="rtl"] .student-sidebar{
        inset-inline-start: auto;
        inset-inline-end: 0;
        transform: translateX(110%);
      }
      .student-main{
        width: 100%;
      }
      .student-sidebar-open .student-sidebar{
        transform: translateX(0);
      }
      .student-sidebar-open::before{
        content:"";
        position: fixed;
        inset: 0;
        background: rgba(2,6,23,.35);
        z-index: 1029;
      }
    }

    /* Brand */
    .student-brand{
      display:flex;
      align-items:center;
      gap: 12px;
      user-select:none;
    }
    .brand-logo{
      width: 38px;
      height: 38px;
      border-radius: 12px;
      background: #fff;
      border: 1px solid var(--stu-border);
      box-shadow: var(--stu-shadow-sm);
      display:flex;
      align-items:center;
      justify-content:center;
      overflow:hidden;
      flex: 0 0 auto;
    }
    .brand-logo img{
      width: 100%;
      height: 100%;
      object-fit: contain;
      padding: 6px;
    }
    .brand-text .brand-title{
      font-weight: 900;
      letter-spacing: .2px;
      line-height: 1.05;
    }
    .brand-text .brand-subtitle{
      color: var(--stu-muted);
      font-size: .85rem;
      line-height: 1.05;
    }

    /* Nav links */
    .student-nav{
      padding: 14px;
    }
    .student-nav-link{
      display:flex;
      align-items:center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 14px;
      text-decoration:none;
      color: var(--stu-text);
      border: 1px solid transparent;
      transition: background .15s ease, border-color .15s ease, transform .1s ease;
      margin-bottom: 8px;
    }
    .student-nav-link:hover{
      background: rgba(13,110,253,.06);
      border-color: rgba(13,110,253,.14);
      transform: translateY(-1px);
    }
    .student-nav-link.active{
      background: rgba(13,110,253,.10);
      border-color: rgba(13,110,253,.18);
      font-weight: 800;
    }
    .nav-ico{ width: 22px; text-align:center; }

    .nav-section{
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px dashed rgba(15,23,42,.12);
    }
    .nav-section-title{
      color: var(--stu-muted);
      font-size: .8rem;
      font-weight: 800;
      margin-bottom: 10px;
      text-transform: uppercase;
      letter-spacing: .7px;
    }

    /* Shared Card */
    .student-card{
      border-radius: var(--stu-radius);
      border: 1px solid var(--stu-border);
      background: var(--stu-card);
      box-shadow: var(--stu-shadow-sm);
    }
    .student-card .card-body{ padding: 18px; }

    /* Page header block */
    .stu-page-title{
      font-weight: 900;
      letter-spacing: .2px;
    }
    .stu-subtitle{ color: var(--stu-muted); }

    /* Badges */
    .badge-soft-primary { background: rgba(13,110,253,.10); color:#0d6efd; border:1px solid rgba(13,110,253,.18); }
    .badge-soft-success { background: rgba(25,135,84,.10); color:#198754; border:1px solid rgba(25,135,84,.18); }
    .badge-soft-warning { background: rgba(255,193,7,.16); color:#a16207; border:1px solid rgba(255,193,7,.28); }
    .badge-soft-info    { background: rgba(13,202,240,.14); color:#0aa2c0; border:1px solid rgba(13,202,240,.22); }
    .badge-soft-secondary{ background: rgba(108,117,125,.10); color:#6c757d; border:1px solid rgba(108,117,125,.18); }

    /* Filter chips */
    .filter-chip{
      display:inline-flex;
      align-items:center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid rgba(15,23,42,.12);
      background: #fff;
      text-decoration:none;
      color: var(--stu-text);
      font-weight: 700;
      transition: background .15s ease, border-color .15s ease, transform .1s ease;
    }
    .filter-chip:hover{
      background: rgba(13,110,253,.06);
      border-color: rgba(13,110,253,.20);
      transform: translateY(-1px);
    }
    .filter-chip.active{
      background: rgba(13,110,253,.10);
      border-color: rgba(13,110,253,.24);
      color: #0d6efd;
    }

    /* Soft divider */
    .soft-divider{ border-top: 1px dashed rgba(15,23,42,.14); }

    /* Focus mode hook for exam room (optional) */
    .exam-focus .student-sidebar,
    .exam-focus .student-header{
      display:none !important;
    }
    .exam-focus .student-main{
      width: 100%;
    }
  </style>

  <script>
    // ✅ Close sidebar when clicking the overlay on mobile
    document.addEventListener('click', function (e) {
      try{
        if (!document.documentElement.classList.contains('student-sidebar-open')) return;
        if (e.target === document.documentElement) {
          document.documentElement.classList.remove('student-sidebar-open');
        }
      }catch(err){}
    });
  </script>

  @stack('head')
</head>

<body class="student-ui {{ $isRtl ? 'rtl' : 'ltr' }}">
  {{-- Top Header --}}
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
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button">
              {{ auth()->user()->name ?? __('Student') }}
            </button>

            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="{{ route('student.dashboard') }}">
                  {{ __('Dashboard') }}
                </a>
              </li>

              <li>
                <a class="dropdown-item" href="{{ route('student.exams.index') }}">
                  {{ __('Exams') }}
                </a>
              </li>

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
    {{-- Sidebar --}}
    <aside class="student-sidebar">
      <nav class="student-nav">
        <a class="student-nav-link {{ request()->is('student/dashboard') ? 'active' : '' }}"
           href="{{ route('student.dashboard') }}">
          <span class="nav-ico">🏠</span>
          <span>{{ __('Dashboard') }}</span>
        </a>

        <a class="student-nav-link {{ request()->is('student/exams*') ? 'active' : '' }}"
           href="{{ route('student.exams.index') }}">
          <span class="nav-ico">📝</span>
          <span>{{ __('Exams') }}</span>
        </a>

        {{-- Support --}}
        <div class="nav-section">
          <div class="nav-section-title">{{ __('Support') }}</div>

          @php
            $helpUrl = \Illuminate\Support\Facades\Route::has('student.help')
              ? route('student.help')
              : null;
          @endphp

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

    {{-- Main --}}
    <main class="student-main">
      <div class="container-fluid py-3">
        @if (session('success'))
          <div class="alert alert-success student-card">{{ session('success') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger student-card">{{ session('error') }}</div>
        @endif

        @yield('content')
      </div>
    </main>
  </div>

  @stack('scripts')
</body>
</html>
