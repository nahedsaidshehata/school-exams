{{-- resources/views/student/partials/ui-head.blade.php --}}
<style>
  :root{
    --sp-bg: #f7f8fb;
    --sp-card: #ffffff;
    --sp-text: #111827;
    --sp-muted: #6b7280;
    --sp-border: rgba(17,24,39,.10);
    --sp-border-2: rgba(17,24,39,.14);
    --sp-shadow: 0 10px 30px rgba(17,24,39,.06);
    --sp-shadow-sm: 0 6px 18px rgba(17,24,39,.06);
    --sp-radius: 18px;
    --sp-radius-sm: 14px;
    --sp-focus: 0 0 0 .2rem rgba(13,110,253,.20);
  }

  /* Container */
  .student-portal{
    max-width: 1240px;
    margin-inline: auto;
    padding-inline: 12px;
  }

  /* Page header */
  .sp-page-header{
    background: linear-gradient(135deg, rgba(13,110,253,.10), rgba(25,135,84,.08));
    border: 1px solid var(--sp-border);
    border-radius: var(--sp-radius);
    padding: 14px 16px;
    box-shadow: var(--sp-shadow-sm);
  }
  .sp-title{
    font-weight: 900;
    letter-spacing: -.2px;
    color: var(--sp-text);
    margin: 0;
  }
  .sp-subtitle{
    color: var(--sp-muted);
    margin-top: 4px;
  }

  /* Cards */
  .student-card{
    border-radius: var(--sp-radius);
    border: 1px solid var(--sp-border);
    background: var(--sp-card);
    box-shadow: var(--sp-shadow-sm);
  }
  .student-card .card-body{ padding: 16px; }
  .sp-card-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
  }
  .sp-card-title{
    font-weight: 900;
    margin: 0;
    color: var(--sp-text);
  }
  .sp-card-meta{
    color: var(--sp-muted);
    font-size: .9rem;
    line-height: 1.3;
  }

  /* Stat Cards */
  .sp-stat{
    position: relative;
    overflow: hidden;
  }
  .sp-stat:before{
    content:'';
    position:absolute;
    inset: -60px -60px auto auto;
    width: 160px;
    height: 160px;
    border-radius: 999px;
    background: rgba(13,110,253,.08);
    transform: rotate(18deg);
  }
  .sp-stat .sp-stat-ico{
    width: 44px;
    height: 44px;
    border-radius: 14px;
    border: 1px solid var(--sp-border);
    display:flex;
    align-items:center;
    justify-content:center;
    background: rgba(255,255,255,.8);
    box-shadow: 0 10px 20px rgba(17,24,39,.06);
    font-size: 20px;
    flex: 0 0 auto;
  }
  .sp-stat .sp-stat-num{
    font-size: 2.1rem;
    font-weight: 900;
    line-height: 1;
    color: var(--sp-text);
  }
  .sp-stat .sp-stat-label{
    color: var(--sp-muted);
    font-size: .9rem;
    margin-bottom: 4px;
  }

  /* Soft badges */
  .badge-soft-primary { background: rgba(13,110,253,.10); color: #0d6efd; border: 1px solid rgba(13,110,253,.18); }
  .badge-soft-secondary { background: rgba(108,117,125,.10); color: #6c757d; border: 1px solid rgba(108,117,125,.18); }
  .badge-soft-success { background: rgba(25,135,84,.10); color: #198754; border: 1px solid rgba(25,135,84,.18); }
  .badge-soft-info { background: rgba(13,202,240,.12); color: #0aa2c0; border: 1px solid rgba(13,202,240,.22); }
  .badge-soft-warning { background: rgba(255,193,7,.14); color: #b58100; border: 1px solid rgba(255,193,7,.22); }

  /* Filter chips */
  .filter-chip{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid var(--sp-border);
    background: #fff;
    text-decoration: none;
    color: var(--sp-text);
    font-weight: 700;
    font-size: .92rem;
    transition: .15s ease;
    user-select:none;
  }
  .filter-chip:hover{
    background: rgba(13,110,253,.06);
    border-color: rgba(13,110,253,.22);
  }
  .filter-chip.active{
    background: rgba(13,110,253,.12);
    border-color: rgba(13,110,253,.30);
    color: #0d6efd;
  }

  /* Inputs */
  .sp-search{
    min-width: 260px;
    border-radius: 14px;
    border: 1px solid var(--sp-border);
    box-shadow: 0 8px 18px rgba(17,24,39,.04);
  }
  .sp-search:focus{
    border-color: rgba(13,110,253,.45);
    box-shadow: var(--sp-focus);
  }

  /* Exam card */
  .exam-card .sp-exam-title{
    font-weight: 900;
    margin: 0 0 4px 0;
    color: var(--sp-text);
    letter-spacing: -.2px;
  }
  .exam-card .sp-exam-meta{
    color: var(--sp-muted);
    font-size: .9rem;
    line-height: 1.35;
  }
  .sp-divider{
    border-top: 1px dashed rgba(17,24,39,.14);
    margin-block: 12px;
  }

  /* Empty state */
  .sp-empty{
    text-align:center;
    padding: 42px 14px;
  }
  .sp-empty .sp-empty-ico{
    font-size: 34px;
    margin-bottom: 10px;
  }
  .sp-empty .sp-empty-title{
    font-weight: 900;
    margin-bottom: 4px;
    color: var(--sp-text);
  }
  .sp-empty .sp-empty-sub{
    color: var(--sp-muted);
  }

  /* Toasts */
  .sp-toast-wrap{
    position: fixed;
    inset: 16px 16px auto auto;
    z-index: 1085;
    display:flex;
    flex-direction:column;
    gap:10px;
    max-width: 360px;
    pointer-events:none;
  }
  [dir="rtl"] .sp-toast-wrap{
    inset: 16px auto auto 16px;
  }
  .sp-toast{
    pointer-events:auto;
    background: #fff;
    border: 1px solid var(--sp-border);
    border-radius: 14px;
    box-shadow: var(--sp-shadow);
    padding: 10px 12px;
    display:flex;
    gap:10px;
    align-items:flex-start;
  }
  .sp-toast .sp-toast-ico{
    width: 30px; height:30px;
    border-radius: 10px;
    display:flex; align-items:center; justify-content:center;
    border: 1px solid var(--sp-border);
    background: rgba(17,24,39,.03);
    flex: 0 0 auto;
  }
  .sp-toast .sp-toast-title{
    font-weight: 900;
    margin: 0;
    font-size: .95rem;
    color: var(--sp-text);
  }
  .sp-toast .sp-toast-msg{
    margin: 2px 0 0 0;
    color: var(--sp-muted);
    font-size: .9rem;
    line-height: 1.35;
  }
  .sp-toast.success .sp-toast-ico{ background: rgba(25,135,84,.10); border-color: rgba(25,135,84,.22); color: #198754; }
  .sp-toast.error .sp-toast-ico{ background: rgba(220,53,69,.10); border-color: rgba(220,53,69,.22); color: #dc3545; }
  .sp-toast.info .sp-toast-ico{ background: rgba(13,110,253,.10); border-color: rgba(13,110,253,.22); color: #0d6efd; }

  /* Focus mode (Exam Room) */
  .sp-focus-mode .question-nav-sidebar,
  .sp-focus-mode .exam-room-topbar .exam-progress-container{
    display:none !important;
  }
  .sp-focus-mode .exam-room-topbar{
    position: sticky;
    top: 10px;
    z-index: 10;
  }

  /* Accessibility */
  .sp-sr-only{
    position:absolute !important;
    width:1px !important;
    height:1px !important;
    padding:0 !important;
    margin:-1px !important;
    overflow:hidden !important;
    clip:rect(0,0,0,0) !important;
    white-space:nowrap !important;
    border:0 !important;
  }

  /* Room (common improvements) */
  .exam-room-topbar{
    background: linear-gradient(135deg, rgba(13,110,253,.10), rgba(25,135,84,.08));
    border: 1px solid var(--sp-border);
    border-radius: var(--sp-radius);
    padding: 14px 16px;
    box-shadow: var(--sp-shadow-sm);
  }
  .status-indicator{
    display:flex;
    align-items:center;
    gap:8px;
    padding: 8px 10px;
    border-radius: 14px;
    border: 1px solid var(--sp-border);
    background: #fff;
    font-weight: 800;
    font-size: .92rem;
    color: var(--sp-text);
    user-select:none;
  }
  .status-indicator .dot{
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: rgba(108,117,125,.55);
  }
  .status-indicator.saved .dot{ background: rgba(25,135,84,.85); }
  .status-indicator.saving .dot{ background: rgba(255,193,7,.85); }

  .timer-pill{
    font-variant-numeric: tabular-nums;
    letter-spacing: .2px;
    padding: 8px 10px;
    border-radius: 14px;
    border: 1px solid var(--sp-border);
    background: #fff;
    font-weight: 900;
    user-select:none;
  }
  .timer-pill.normal{ color: var(--sp-text); }
  .timer-pill.warning{ color: #b58100; border-color: rgba(255,193,7,.35); background: rgba(255,193,7,.10); }
  .timer-pill.danger{ color: #dc3545; border-color: rgba(220,53,69,.35); background: rgba(220,53,69,.08); }

  .modern-progress{
    height: 12px;
    border-radius: 999px;
    background: rgba(17,24,39,.06);
    overflow:hidden;
  }
  .modern-progress .progress-bar{
    border-radius: 999px;
  }

  .question-card{
    border: 1px solid var(--sp-border);
    border-radius: var(--sp-radius);
    background: #fff;
    padding: 14px 14px;
  }
  .question-title .q-ar{
    font-weight: 900;
    font-size: 1.05rem;
    color: var(--sp-text);
    line-height: 1.35;
  }

  .option-card{
    border: 1px solid rgba(17,24,39,.10);
    border-radius: 16px;
    padding: 10px 12px;
    display:flex;
    gap:10px;
    cursor:pointer;
    background:#fff;
    transition: .12s ease;
  }
  .option-card:hover{
    background: rgba(13,110,253,.05);
    border-color: rgba(13,110,253,.20);
  }
  .option-card.selected{
    background: rgba(13,110,253,.10);
    border-color: rgba(13,110,253,.28);
  }

  .question-grid{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
  }
  .question-btn{
    width: 44px;
    height: 40px;
    border-radius: 14px;
    border: 1px solid rgba(17,24,39,.12);
    background:#fff;
    font-weight: 900;
    display:flex;
    align-items:center;
    justify-content:center;
    user-select:none;
  }
  .question-btn:hover{ background: rgba(13,110,253,.06); border-color: rgba(13,110,253,.22); }
  .question-btn.current{ background: rgba(13,110,253,.12); border-color: rgba(13,110,253,.30); color:#0d6efd; }
  .question-btn.answered{ background: rgba(25,135,84,.10); border-color: rgba(25,135,84,.24); color:#198754; }
  .question-btn.unanswered{ background: #fff; }
  .question-btn.flagged{ outline: 2px solid rgba(255,193,7,.45); outline-offset: 1px; }

  .nav-legend .legend-item{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid var(--sp-border);
    background:#fff;
  }
  .legend-dot{
    width:10px;height:10px;border-radius:999px;
    display:inline-block;
    background: rgba(108,117,125,.55);
  }
  .legend-dot.current{ background: rgba(13,110,253,.85); }
  .legend-dot.answered{ background: rgba(25,135,84,.85); }
  .legend-dot.unanswered{ background: rgba(108,117,125,.55); }
  .legend-dot.flagged{ background: rgba(255,193,7,.90); }

  /* Small improvements for RTL */
  [dir="rtl"] .btn-group > .btn:not(:first-child){ margin-right: -1px; margin-left: 0; }
  [dir="rtl"] .btn-group > .btn:first-child{ border-top-right-radius: .375rem; border-bottom-right-radius: .375rem; border-top-left-radius: 0; border-bottom-left-radius: 0; }
  [dir="rtl"] .btn-group > .btn:last-child{ border-top-left-radius: .375rem; border-bottom-left-radius: .375rem; border-top-right-radius: 0; border-bottom-right-radius: 0; }
</style>
