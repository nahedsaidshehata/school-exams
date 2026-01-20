{{-- resources/views/admin/attempts/show.blade.php.php --}}
@extends('layouts.admin')

@section('title', __('Attempt Grading'))

@php
  $locale = app()->getLocale();
  $isRtl = in_array($locale, ['ar', 'fa', 'ur']);
@endphp

@section('content')
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1">{{ __('Attempt Grading') }}</h1>
      <div class="text-muted small">
        {{ __('Review answers, grade essay questions, then finalize grading.') }}
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('admin.exams.index') }}" class="btn btn-outline-secondary btn-sm">
        {{ __('Back to Exams') }}
      </a>
    </div>
  </div>

  {{-- Alerts placeholder (also session alerts handled by layout) --}}
  <div id="page-alert" class="alert d-none" role="alert"></div>

  {{-- Loading skeleton --}}
  <div id="loadingCard" class="card shadow-sm border-0">
    <div class="card-body">
      <div class="placeholder-glow">
        <div class="placeholder col-4 mb-2"></div>
        <div class="placeholder col-7 mb-3"></div>
        <div class="placeholder col-12"></div>
      </div>
    </div>
  </div>

  {{-- Main --}}
  <div id="app" class="d-none">

    {{-- Attempt summary --}}
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-lg-7">
            <div class="d-flex flex-wrap align-items-center gap-2">
              <span class="badge text-bg-primary" id="attemptStatus">—</span>
              <span class="text-muted small">
                #<span id="attemptNumber">—</span>
              </span>
            </div>

            <div class="mt-2">
              <div class="fw-semibold" id="studentName">—</div>
              <div class="text-muted small">
                <span id="studentUsername">—</span>
              </div>
            </div>

            <div class="mt-3">
              <div class="fw-semibold" id="examTitleEn">—</div>
              <div class="text-muted small" id="examTitleAr" style="{{ $isRtl ? 'direction:rtl' : '' }}">—</div>
            </div>
          </div>

          <div class="col-12 col-lg-5">
            <div class="row g-2">
              <div class="col-6">
                <div class="border rounded-3 p-2 h-100">
                  <div class="text-muted small">{{ __('Started') }}</div>
                  <div class="fw-semibold" id="startedAt">—</div>
                </div>
              </div>
              <div class="col-6">
                <div class="border rounded-3 p-2 h-100">
                  <div class="text-muted small">{{ __('Submitted') }}</div>
                  <div class="fw-semibold" id="submittedAt">—</div>
                </div>
              </div>
              <div class="col-6">
                <div class="border rounded-3 p-2 h-100">
                  <div class="text-muted small">{{ __('Max Score') }}</div>
                  <div class="fw-semibold" id="maxScore">—</div>
                </div>
              </div>
              <div class="col-6">
                <div class="border rounded-3 p-2 h-100">
                  <div class="text-muted small">{{ __('Raw Score') }}</div>
                  <div class="fw-semibold" id="rawScore">—</div>
                </div>
              </div>
              <div class="col-12">
                <div class="border rounded-3 p-2">
                  <div class="text-muted small">{{ __('Percentage') }}</div>
                  <div class="fw-semibold" id="percentage">—</div>
                </div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3 justify-content-end">
              <button type="button" class="btn btn-outline-primary btn-sm" id="btnRefresh">
                {{ __('Refresh') }}
              </button>

              <button type="button" class="btn btn-primary btn-sm" id="btnFinalize">
                {{ __('Finalize Grading') }}
              </button>

            </div>
          </div>
        </div>

        <hr class="my-3">

        <div class="d-flex flex-wrap gap-2 align-items-center">
          <span class="text-muted small">{{ __('Answers') }}:</span>
          <span class="badge text-bg-secondary" id="answersCount">0</span>
          <span class="text-muted small">{{ __('Ungraded Essays') }}:</span>
          <span class="badge text-bg-warning" id="ungradedEssays">0</span>
        </div>
      </div>
    </div>

    {{-- Answers list --}}
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-0">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="fw-semibold">{{ __('Attempt Answers') }}</div>

          <div class="d-flex gap-2">
            <input type="search" class="form-control form-control-sm" id="search" placeholder="{{ __('Search in prompts...') }}" style="max-width: 260px;">
            <select class="form-select form-select-sm" id="filterType" style="max-width: 180px;">
              <option value="">{{ __('All types') }}</option>
              <option value="MCQ">MCQ</option>
              <option value="TF">TF</option>
              <option value="ESSAY">ESSAY</option>
            </select>
          </div>
        </div>
      </div>

      <div class="card-body">
        <div id="answersList" class="vstack gap-3"></div>

        <div id="emptyState" class="text-center text-muted py-4 d-none">
          {{ __('No answers to show.') }}
        </div>
      </div>
    </div>

  </div>
@endsection

@push('scripts')
<script>
(function () {
  const attemptShowUrl = window.location.href;
  const attemptId = @json(request()->route('attempt'));
  const gradeEssayUrl = @json(route('admin.attempts.grade-essay', ['attempt' => request()->route('attempt')]));
  const finalizeUrl = @json(route('admin.attempts.finalize-grading', ['attempt' => request()->route('attempt')]));

  const els = {
    loading: document.getElementById('loadingCard'),
    app: document.getElementById('app'),
    alert: document.getElementById('page-alert'),

    attemptStatus: document.getElementById('attemptStatus'),
    attemptNumber: document.getElementById('attemptNumber'),
    studentName: document.getElementById('studentName'),
    studentUsername: document.getElementById('studentUsername'),
    examTitleEn: document.getElementById('examTitleEn'),
    examTitleAr: document.getElementById('examTitleAr'),
    startedAt: document.getElementById('startedAt'),
    submittedAt: document.getElementById('submittedAt'),
    maxScore: document.getElementById('maxScore'),
    rawScore: document.getElementById('rawScore'),
    percentage: document.getElementById('percentage'),

    answersCount: document.getElementById('answersCount'),
    ungradedEssays: document.getElementById('ungradedEssays'),

    answersList: document.getElementById('answersList'),
    emptyState: document.getElementById('emptyState'),

    btnRefresh: document.getElementById('btnRefresh'),
    finalizeForm: document.getElementById('finalizeForm'),
    btnFinalize: document.getElementById('btnFinalize'),

    search: document.getElementById('search'),
    filterType: document.getElementById('filterType'),
  };


  function showAlert(type, msg) {
    els.alert.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
    els.alert.classList.add('alert-' + type);
    els.alert.textContent = msg;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function hideAlert() {
    els.alert.classList.add('d-none');
    els.alert.textContent = '';
  }

  function fmtDate(val) {
    if (!val) return '—';
    try {
      const d = new Date(val);
      if (isNaN(d.getTime())) return String(val);
      return d.toLocaleString();
    } catch (e) {
      return String(val);
    }
  }

  function badgeForStatus(status) {
    const s = (status || '').toUpperCase();
    const map = {
      'IN_PROGRESS': 'text-bg-info',
      'SUBMITTED': 'text-bg-secondary',
      'PENDING_MANUAL': 'text-bg-warning',
      'GRADED': 'text-bg-success',
      'LOCKED': 'text-bg-dark',
    };
    return map[s] || 'text-bg-primary';
  }

  function safeText(v) {
    return (v === null || v === undefined || v === '') ? '—' : String(v);
  }

  function parseStudentResponse(resp) {
    if (resp === null || resp === undefined) return null;
    if (typeof resp === 'object') return resp;
    const s = String(resp).trim();
    if (!s) return null;
    try { return JSON.parse(s); } catch (e) { return { raw: s }; }
  }

  function renderChoiceAnswer(answerObj, options) {
    // Student response shape is unknown; we try common keys
    if (!answerObj) return '<span class="text-muted">—</span>';

    const chosen =
      answerObj.answer ??
      answerObj.option_id ??
      answerObj.optionId ??
      answerObj.selected_option_id ??
      answerObj.selectedOptionId ??
      answerObj.choice ??
      null;

    // TF may use true/false directly
    if (typeof chosen === 'boolean') {
      return '<span class="fw-semibold">' + (chosen ? 'True' : 'False') + '</span>';
    }

    if (chosen === null || chosen === undefined) {
      // maybe it stores text
      if (answerObj.raw) return '<span class="fw-semibold">' + escapeHtml(answerObj.raw) + '</span>';
      return '<span class="text-muted">—</span>';
    }

    // If chosen is an id, map to option content
    const opt = (options || []).find(o => String(o.id) === String(chosen));
    if (opt) {
      return `
        <div class="fw-semibold">${escapeHtml(opt.content_en || '')}</div>
        <div class="text-muted small" style="direction:rtl">${escapeHtml(opt.content_ar || '')}</div>
      `;
    }

    // fallback
    return '<span class="fw-semibold">' + escapeHtml(String(chosen)) + '</span>';
  }

  function renderEssayAnswer(answerObj) {
    if (!answerObj) return '<span class="text-muted">—</span>';
    const text =
      answerObj.text ??
      answerObj.answer ??
      answerObj.response ??
      answerObj.raw ??
      '';
    if (!text) return '<span class="text-muted">—</span>';
    return `<div class="bg-light border rounded-3 p-2" style="white-space:pre-wrap">${escapeHtml(String(text))}</div>`;
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function buildAnswerCard(a) {
    const type = a.question_type || '—';
    const pointsAwarded = (a.points_awarded === null || a.points_awarded === undefined) ? null : a.points_awarded;

    const answerObj = parseStudentResponse(a.student_response);
    const promptEn = a.question_prompt_en || '';
    const promptAr = a.question_prompt_ar || '';

    const isEssay = String(type).toUpperCase() === 'ESSAY';

    const optionsHtml = (!isEssay)
      ? `
        <details class="mt-2">
          <summary class="small text-muted" style="cursor:pointer">{{ __('Show options') }}</summary>
          <div class="mt-2">
            ${(a.options || []).map(o => {
              const correct = o.is_correct ? '<span class="badge text-bg-success ms-2">✔</span>' : '';
              return `
                <div class="border rounded-3 p-2 mb-2">
                  <div class="fw-semibold">${escapeHtml(o.content_en || '')} ${correct}</div>
                  <div class="text-muted small" style="direction:rtl">${escapeHtml(o.content_ar || '')}</div>
                </div>
              `;
            }).join('')}
          </div>
        </details>
      `
      : '';

    const studentAnswerHtml = isEssay
      ? renderEssayAnswer(answerObj)
      : renderChoiceAnswer(answerObj, a.options || []);

    // grading box for essay
    const gradeBox = isEssay ? `
      <div class="mt-3 border-top pt-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div class="text-muted small">
            {{ __('Essay grading') }}
          </div>

          <div class="d-flex gap-2 align-items-center">
            <div class="input-group input-group-sm" style="max-width: 220px;">
              <span class="input-group-text">{{ __('Points') }}</span>
              <input type="number" step="0.01" min="0" class="form-control js-essay-points" value="${pointsAwarded !== null ? escapeHtml(pointsAwarded) : ''}" placeholder="0">
            </div>

            <button type="button" class="btn btn-sm btn-outline-primary js-grade-essay">
              {{ __('Save') }}
            </button>
          </div>
        </div>

        <div class="text-muted small mt-2">
          {{ __('Current points awarded') }}: <span class="fw-semibold js-points-awarded">${pointsAwarded !== null ? escapeHtml(pointsAwarded) : '—'}</span>
        </div>
      </div>
    ` : '';

    const statusPill = isEssay && (pointsAwarded === null)
      ? `<span class="badge text-bg-warning">{{ __('Needs grading') }}</span>`
      : `<span class="badge text-bg-success">{{ __('OK') }}</span>`;

    return `
      <div class="border rounded-4 p-3" data-qid="${escapeHtml(a.question_id)}" data-qtype="${escapeHtml(type)}" data-prompt="${escapeHtml((promptEn + ' ' + promptAr).toLowerCase())}">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge text-bg-secondary">${escapeHtml(type)}</span>
              ${statusPill}
            </div>

            <div class="fw-semibold">${escapeHtml(promptEn)}</div>
            <div class="text-muted small" style="direction:rtl">${escapeHtml(promptAr)}</div>
          </div>

          <div class="text-end">
            <div class="text-muted small">{{ __('Points awarded') }}</div>
            <div class="fw-semibold js-points-awarded-header">${pointsAwarded !== null ? escapeHtml(pointsAwarded) : '—'}</div>
          </div>
        </div>

        <div class="mt-3">
          <div class="text-muted small mb-1">{{ __('Student answer') }}</div>
          ${studentAnswerHtml}
        </div>

        ${optionsHtml}
        ${gradeBox}
      </div>
    `;
  }

  function applyFilters() {
    const q = (els.search.value || '').trim().toLowerCase();
    const type = (els.filterType.value || '').trim().toUpperCase();

    const cards = els.answersList.querySelectorAll('[data-qid]');
    let shown = 0;

    cards.forEach(card => {
      const prompt = card.getAttribute('data-prompt') || '';
      const qtype = (card.getAttribute('data-qtype') || '').toUpperCase();

      const okSearch = !q || prompt.includes(q);
      const okType = !type || qtype === type;

      const show = okSearch && okType;
      card.classList.toggle('d-none', !show);
      if (show) shown++;
    });

    els.emptyState.classList.toggle('d-none', shown !== 0);
  }

  async function fetchAttemptJson() {
    hideAlert();

    const res = await fetch(attemptShowUrl, {
      headers: {
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    });

    if (!res.ok) {
      const txt = await res.text();
      throw new Error('Failed to load attempt: ' + res.status + ' ' + txt);
    }

    return await res.json();
  }

  function bindEssayActions(container, attemptData) {
    container.querySelectorAll('.js-grade-essay').forEach(btn => {
      btn.addEventListener('click', async function () {
        const card = btn.closest('[data-qid]');
        const qid = card.getAttribute('data-qid');
        const pointsInput = card.querySelector('.js-essay-points');
        const points = pointsInput.value;

        if (points === '' || points === null || points === undefined) {
          showAlert('warning', @json(__('Please enter points before saving.')));
          return;
        }

        btn.disabled = true;
        btn.classList.add('disabled');

        try {
          const res = await fetch(gradeEssayUrl, {
            method: 'PATCH',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
              grades: [{
                question_id: qid,
                points_awarded: Number(points)
              }]
            })
          });

          const data = await res.json().catch(() => ({}));
          if (!res.ok) {
            const msg = data.error || data.message || ('HTTP ' + res.status);
            throw new Error(msg);
          }

          // Refresh from server to reflect updated points & counts
          await load();

          showAlert('success', data.message || @json(__('Saved successfully.')));
        } catch (e) {
          showAlert('danger', e.message || @json(__('Failed to save.')));
        } finally {
          btn.disabled = false;
          btn.classList.remove('disabled');
        }
      });
    });

    // finalize form submit: we keep standard POST; but we prevent if ungraded essays exist (client-side)
    els.btnFinalize.addEventListener('click', async function () {
  const ungraded = Number(els.ungradedEssays.textContent || '0');
  if (ungraded > 0) {
    showAlert('warning', @json(__('Cannot finalize: there are still ungraded essay questions.')));
    return;
  }

  els.btnFinalize.disabled = true;
  try {
    const res = await fetch(finalizeUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      credentials: 'same-origin',
      body: JSON.stringify({})
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw new Error(data.error || data.message || ('HTTP ' + res.status));
    }

    await load();
    showAlert('success', data.message || @json(__('Grading finalized successfully.')));
  } catch (e) {
    showAlert('danger', e.message || @json(__('Failed to finalize.')));
  } finally {
    els.btnFinalize.disabled = false;
  }
});

  }

  function render(data) {
    const a = data?.attempt || {};
    const answers = Array.isArray(a.answers) ? a.answers : [];

    // summary
    els.attemptStatus.textContent = safeText(a.status);
    els.attemptStatus.className = 'badge ' + badgeForStatus(a.status);

    els.attemptNumber.textContent = safeText(a.attempt_number);
    els.studentName.textContent = safeText(a.student?.full_name || a.student?.username);
    els.studentUsername.textContent = '@' + safeText(a.student?.username || '—');

    els.examTitleEn.textContent = safeText(a.exam?.title_en);
    els.examTitleAr.textContent = safeText(a.exam?.title_ar);

    els.startedAt.textContent = fmtDate(a.started_at);
    els.submittedAt.textContent = fmtDate(a.submitted_at);

    els.maxScore.textContent = safeText(a.max_possible_score);
    els.rawScore.textContent = safeText(a.raw_score);
    els.percentage.textContent = (a.percentage === null || a.percentage === undefined) ? '—' : (String(a.percentage) + '%');

    // counts
    els.answersCount.textContent = String(answers.length);

    const ungraded = answers.filter(x => String(x.question_type).toUpperCase() === 'ESSAY' && (x.points_awarded === null || x.points_awarded === undefined)).length;
    els.ungradedEssays.textContent = String(ungraded);

    // list
    els.answersList.innerHTML = answers.map(buildAnswerCard).join('');
    els.emptyState.classList.toggle('d-none', answers.length !== 0);

    // bind
    bindEssayActions(els.answersList, a);

    // filters
    applyFilters();
  }

  async function load() {
    els.loading.classList.remove('d-none');
    els.app.classList.add('d-none');

    try {
      const data = await fetchAttemptJson();
      render(data);

      els.loading.classList.add('d-none');
      els.app.classList.remove('d-none');
    } catch (e) {
      els.loading.classList.add('d-none');
      showAlert('danger', e.message || @json(__('Failed to load attempt.')));
    }
  }

  // events
  els.btnRefresh.addEventListener('click', load);
  els.search.addEventListener('input', applyFilters);
  els.filterType.addEventListener('change', applyFilters);

  // initial
  load();
})();
</script>
@endpush
