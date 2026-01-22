{{-- resources/views/student/exams/show.blade.php.php --}}
@extends('layouts.student')

@section('title', __('Exam Details'))

@php
  $examObj = $exam ?? $item ?? null;
  $examId = $examObj->id ?? $examObj['id'] ?? request()->route('exam') ?? null;

  $title = $examObj->title_en ?? $examObj['title']['en'] ?? __('Exam');
  $desc = $examObj->description ?? $examObj['description'] ?? null;
  $duration = $examObj->duration_minutes ?? $examObj['duration_minutes'] ?? $examObj->duration ?? $examObj['duration'] ?? null;

  $attemptsLimit = $attemptsLimit ?? ($examObj->max_attempts ?? $examObj['max_attempts'] ?? $examObj->attempts_limit ?? $examObj['attempts_limit'] ?? null);
  $attemptsUsed = $attemptsUsed ?? ($examObj->attempts_used ?? $examObj['attempts_used'] ?? null);
  $attemptsRemaining = $attemptsRemaining ?? (is_numeric($attemptsLimit) && is_numeric($attemptsUsed) ? max(0, $attemptsLimit - $attemptsUsed) : null);

  // Room context (when opened from ExamRoomController@room)
  $attempt = $attempt ?? null;
  $isRoom = !is_null($attempt);

  // Active attempt (if controller passes it on details page)
  $activeAttempt = $activeAttempt ?? $currentAttempt ?? null;
  $activeAttemptId = $activeAttempt->id ?? $activeAttempt['id'] ?? null;

  // URLs
  $introUrl = $examId ? route('student.exams.intro', $examId) : route('student.exams.index');
  $roomUrl = $activeAttemptId ? route('student.attempts.room', ['attempt' => $activeAttemptId]) : null;
  $startUrl = $examId ? route('student.exams.start', $examId) : route('student.exams.index');

  $canStart = is_null($attemptsRemaining) ? true : ($attemptsRemaining > 0);

  // Room endpoints
  $questionsEndpoint = $questionsEndpoint ?? ($examId ? url("/student/exams/{$examId}/questions") : null);

  // Timer
  $remainingSeconds = $remainingSeconds ?? null;

  $locale = app()->getLocale();
  $isRtl = in_array($locale, ['ar', 'fa', 'ur']);
@endphp

@section('content')
  @push('head')
    <style>
      /* Room-only UI (uses layout tokens: --stu-radius, etc.) */
      .room-shell {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 14px;
      }

      @media (max-width: 992px) {
        .room-shell {
          grid-template-columns: 1fr;
        }
      }

      .room-topbar {
        background: linear-gradient(135deg, rgba(13, 110, 253, .10), rgba(25, 135, 84, .08));
        border: 1px solid var(--stu-border);
        border-radius: var(--stu-radius);
        padding: 14px;
      }

      .q-nav {
        position: sticky;
        top: 12px;
      }

      @media (max-width: 992px) {
        .q-nav {
          position: relative;
          top: auto;
        }
      }

      .q-nav .q-nav-list {
        max-height: calc(100vh - 260px);
        overflow: auto;
        padding-bottom: 4px;
      }

      .q-pill {
        width: 44px;
        height: 40px;
        border-radius: 14px;
        border: 1px solid rgba(15, 23, 42, .12);
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 950;
        cursor: pointer;
        user-select: none;
        transition: transform .12s ease, background .12s ease, border-color .12s ease;
      }

      .q-pill:hover {
        background: rgba(13, 110, 253, .06);
        border-color: rgba(13, 110, 253, .25);
        transform: translateY(-1px);
      }

      .q-pill.is-active {
        background: rgba(13, 110, 253, .12);
        border-color: rgba(13, 110, 253, .35);
        color: #0d6efd;
      }

      .q-pill.is-answered {
        background: rgba(25, 135, 84, .10);
        border-color: rgba(25, 135, 84, .25);
        color: #198754;
      }

      .q-card {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: var(--stu-radius);
        padding: 14px;
        background: #fff;
      }

      .q-meta {
        font-size: .88rem;
        color: rgba(15, 23, 42, .58);
      }

      .q-prompt {
        font-size: 1.05rem;
        font-weight: 950;
        letter-spacing: .2px;
      }

      .opt-item {
        border: 1px solid rgba(15, 23, 42, .10);
        border-radius: 16px;
        padding: 10px 12px;
        cursor: pointer;
        background: #fff;
        transition: background .12s ease, border-color .12s ease, transform .12s ease;
      }

      .opt-item:hover {
        background: rgba(13, 110, 253, .05);
        border-color: rgba(13, 110, 253, .22);
        transform: translateY(-1px);
      }

      .opt-item.is-selected {
        background: rgba(13, 110, 253, .10);
        border-color: rgba(13, 110, 253, .30);
      }

      .opt-radio {
        transform: scale(1.05);
      }

      .loading-skeleton {
        background: linear-gradient(90deg, rgba(15, 23, 42, .04), rgba(15, 23, 42, .07), rgba(15, 23, 42, .04));
        background-size: 200% 100%;
        animation: shimmer 1.1s infinite;
        border-radius: 14px;
        height: 18px;
      }

      @keyframes shimmer {
        0% {
          background-position: 0% 0;
        }

        100% {
          background-position: 200% 0;
        }
      }

      .timer-chip {
        font-variant-numeric: tabular-nums;
        letter-spacing: .2px;
        padding: 8px 10px;
        border-radius: 999px;
        border: 1px solid rgba(15, 23, 42, .10);
        background: rgba(255, 255, 255, .92);
        font-weight: 950;
      }

      .room-help {
        color: rgba(15, 23, 42, .62);
        font-size: .9rem;
      }

      .room-shortcuts .stu-kbd {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        padding: 6px 8px;
        border-radius: 10px;
        border: 1px solid rgba(15, 23, 42, .12);
        background: rgba(255, 255, 255, .92);
        font-weight: 900;
        font-size: .85rem;
      }

      .room-shortcuts .item {
        display: flex;
        gap: 8px;
        align-items: center;
        color: rgba(15, 23, 42, .62);
        font-size: .9rem;
      }

      .room-shortcuts .item+.item {
        margin-top: 6px;
      }

      .stu-divider {
        border-top: 1px dashed rgba(15, 23, 42, .14);
        margin: 14px 0;
      }
    </style>
  @endpush

  @if($isRoom)
    {{-- =========================
    EXAM ROOM (simple legacy view inside show)
    ========================= --}}
    <div class="card student-card mb-3">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <div class="stu-page-title h3 mb-1">{{ $title }}</div>
          <div class="stu-subtitle">{{ __('Exam Room') }}</div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
          <div class="timer-chip">⏳ <span id="roomTimerText">—</span></div>
          <a href="{{ route('student.exams.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to Exams') }}
          </a>
        </div>
      </div>
    </div>

    <div class="room-topbar mb-3">
      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="d-flex flex-wrap gap-2 align-items-center">
          @if($duration)
            <span class="badge badge-soft-primary">⏱ {{ $duration }} {{ __('min') }}</span>
          @endif
          <span class="badge badge-soft-secondary">🆔 {{ $examId ?? '—' }}</span>
          <span class="badge badge-soft-primary">🔒 {{ __('Correct answers are hidden') }}</span>
        </div>

        <div class="room-help">
          {{ __('Questions load securely inside the room.') }}
        </div>
      </div>

      <div class="mt-3 d-flex flex-wrap gap-3">
        <div class="room-shortcuts">
          <div class="item"><span class="stu-kbd">{{ $isRtl ? '→' : '←' }}</span> {{ __('Previous') }}</div>
          <div class="item"><span class="stu-kbd">{{ $isRtl ? '←' : '→' }}</span> {{ __('Next') }}</div>
          <div class="item"><span class="stu-kbd">Esc</span> {{ __('Close menu') }}</div>
        </div>

        <div class="ms-auto text-muted small">
          {{ __('Correct answers and points are not displayed for students.') }}
        </div>
      </div>
    </div>

    <div class="room-shell">
      {{-- Left: Navigation --}}
      <div class="q-nav">
        <div class="card student-card">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">{{ __('Questions') }}</div>
              <div class="small text-muted"><span id="qCountText">—</span></div>
            </div>

            <div class="stu-divider"></div>

            <div id="qNavList" class="q-nav-list d-flex flex-wrap gap-2">
              <div class="loading-skeleton w-100" style="height: 40px;"></div>
              <div class="loading-skeleton w-100" style="height: 40px;"></div>
              <div class="loading-skeleton w-100" style="height: 40px;"></div>
            </div>

            <div class="stu-divider"></div>

            <div class="small text-muted">
              <div class="mb-1">✅ {{ __('Green means answered') }}</div>
              <div>🎯 {{ __('Click a number to jump') }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Right: Question Viewer --}}
      <div>
        <div class="card student-card">
          <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
              <div>
                <div class="fw-bold">
                  {{ __('Question') }} <span id="qActiveIndexText">—</span>
                </div>
                <div class="q-meta" id="qMetaText">—</div>
              </div>

              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPrevQ">
                  {{ $isRtl ? '→' : '←' }} {{ __('Previous') }}
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btnAnswer">
                  {{ __('Answer') }}
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnNextQ">
                  {{ __('Next') }} {{ $isRtl ? '←' : '→' }}
                </button>
              </div>
            </div>

            <div class="stu-divider"></div>

            <div id="qViewer">
              <div class="q-card">
                <div class="loading-skeleton mb-3" style="height: 18px; width: 60%;"></div>
                <div class="loading-skeleton mb-2" style="height: 16px; width: 92%;"></div>
                <div class="loading-skeleton mb-2" style="height: 16px; width: 86%;"></div>
                <div class="loading-skeleton mb-2" style="height: 44px; width: 100%;"></div>
                <div class="loading-skeleton mb-2" style="height: 44px; width: 100%;"></div>
                <div class="loading-skeleton mb-2" style="height: 44px; width: 100%;"></div>
              </div>
            </div>

            <div class="text-muted small mt-3">
              {{ __('Correct answers and points are not displayed for students.') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    @push('scripts')
      <script>
        (function () {
          const attemptSession = @json($attempt->active_session_token ?? null);
          const saveUrl = @json($attempt ? route('student.attempts.save', $attempt->id) : null);
          const submitUrl = @json($attempt ? route('student.attempts.submit', $attempt->id) : null);
          const questionsEndpoint = @json($questionsEndpoint);
          const initialRemainingSeconds = @json(is_numeric($remainingSeconds) ? (int) $remainingSeconds : null);
          const isRtl = @json((bool) $isRtl);

          const elTimerText = document.getElementById('roomTimerText');
          const elQCountText = document.getElementById('qCountText');
          const elQNavList = document.getElementById('qNavList');
          const elQViewer = document.getElementById('qViewer');
          const elQActiveIndexText = document.getElementById('qActiveIndexText');
          const elQMetaText = document.getElementById('qMetaText');
          const btnPrevQ = document.getElementById('btnPrevQ');
          const btnNextQ = document.getElementById('btnNextQ');
          const btnAnswer = document.getElementById('btnAnswer');

          let questions = [];
          let activeIndex = 0;
          let remaining = initialRemainingSeconds;
          let isSaving = false;

          function pad2(n) { return String(n).padStart(2, '0'); }
          function formatTime(seconds) {
            if (seconds === null || typeof seconds === 'undefined') return '—';
            const s = Math.max(0, parseInt(seconds, 10) || 0);
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = s % 60;
            if (h > 0) return `${h}:${pad2(m)}:${pad2(sec)}`;
            return `${m}:${pad2(sec)}`;
          }
          function tickTimer() {
            if (remaining === null) { elTimerText.textContent = '—'; return; }
            elTimerText.textContent = formatTime(remaining);
            remaining = Math.max(0, remaining - 1);
          }

          function safeText(v) { if (v === null || typeof v === 'undefined') return ''; return String(v); }

          function getPrompt(q) {
            const ar = q.prompt_ar ?? q.promptAr ?? q.promptAR ?? null;
            const en = q.prompt_en ?? q.promptEn ?? null;
            return safeText(ar && ar.trim() ? ar : en);
          }

          function getOptionText(opt) {
            const ar = opt.content_ar ?? opt.contentAr ?? opt.contentAR ?? null;
            const en = opt.content_en ?? opt.contentEn ?? null;
            return safeText(ar && ar.trim() ? ar : en);
          }

          function normalizeQuestions(payload) {
            let arr = payload;
            if (payload && typeof payload === 'object' && !Array.isArray(payload)) {
              arr = payload.data ?? payload.questions ?? payload.items ?? payload;
            }
            if (!Array.isArray(arr)) return [];

            return arr.map(function (q, i) {
              const qId = q.id ?? q.question_id ?? q.questionId ?? null;
              const orderIndex = q.order_index ?? q.orderIndex ?? (i + 1);

              const optsRaw = q.options ?? q.choices ?? [];
              const options = Array.isArray(optsRaw) ? optsRaw.map(function (o) {
                return {
                  id: o.id ?? o.option_id ?? o.optionId ?? null,
                  content_ar: o.content_ar ?? o.contentAr ?? null,
                  content_en: o.content_en ?? o.contentEn ?? null,
                  order_index: o.order_index ?? o.orderIndex ?? null,
                };
              }) : [];

              // Parse saved answer safely
              let saved = q.saved_answer ?? q.savedAnswer ?? q.student_response ?? q.studentResponse ?? null;
              try {
                if (typeof saved === 'string' && saved.startsWith('{')) {
                  const parsed = JSON.parse(saved);
                  saved = parsed.value ?? parsed.answer ?? parsed.selected ?? saved;
                }
              } catch (e) { }

              return {
                id: qId,
                order_index: orderIndex,
                type: q.type ?? null,
                difficulty: q.difficulty ?? null,
                prompt_ar: q.prompt_ar ?? q.promptAr ?? null,
                prompt_en: q.prompt_en ?? q.promptEn ?? null,
                options: options,
                saved_answer: saved
              };
            }).sort(function (a, b) {
              const ax = parseInt(a.order_index, 10) || 0;
              const bx = parseInt(b.order_index, 10) || 0;
              return ax - bx;
            });
          }

          function isAnswered(q) {
            const v = q.saved_answer;
            if (v === null || typeof v === 'undefined') return false;
            // Additional check for empty string
            if (typeof v === 'string' && v.trim() === '') return false;
            return true;
          }

          function renderNav() {
            elQNavList.innerHTML = '';
            const frag = document.createDocumentFragment();

            questions.forEach(function (q, idx) {
              const pill = document.createElement('button');
              pill.type = 'button';
              pill.className = 'q-pill';
              pill.textContent = String(idx + 1);

              if (idx === activeIndex) pill.classList.add('is-active');
              if (isAnswered(q)) pill.classList.add('is-answered');

              pill.addEventListener('click', function () { setActiveIndex(idx); });
              frag.appendChild(pill);
            });

            elQNavList.appendChild(frag);
            elQCountText.textContent = `${questions.length} ${questions.length === 1 ? '{{ __('question') }}' : '{{ __('questions') }}'}`;
          }

          function renderViewer() {
            const q = questions[activeIndex];
            if (!q) return;

            const prompt = getPrompt(q);
            elQActiveIndexText.textContent = `${activeIndex + 1} / ${questions.length}`;

            const parts = [];
            if (q.type) parts.push(String(q.type));
            if (q.difficulty) parts.push(String(q.difficulty));
            elQMetaText.textContent = parts.length ? parts.join(' • ') : '—';

            const wrapper = document.createElement('div');
            wrapper.className = 'q-card';

            const promptEl = document.createElement('div');
            promptEl.className = 'q-prompt mb-2';
            promptEl.textContent = prompt || '—';
            wrapper.appendChild(promptEl);

            const opts = Array.isArray(q.options) ? q.options.slice().sort(function (a, b) {
              const ax = parseInt(a.order_index, 10) || 0;
              const bx = parseInt(b.order_index, 10) || 0;
              return ax - bx;
            }) : [];

            if (!opts.length) {
              const empty = document.createElement('div');
              empty.className = 'text-muted';
              empty.textContent = '{{ __('No options found for this question.') }}';
              wrapper.appendChild(empty);
            } else {
              const list = document.createElement('div');
              list.className = 'd-grid gap-2 mt-3';

              const selected = q.saved_answer;

              opts.forEach(function (opt, j) {
                const optRow = document.createElement('label');
                optRow.className = 'opt-item d-flex gap-2 align-items-start';

                const input = document.createElement('input');
                input.className = 'form-check-input opt-radio mt-1';
                input.type = 'radio';
                input.name = 'q_' + String(q.id ?? activeIndex);
                input.value = String(opt.id ?? j);

                let isSelected = false;
                if (selected !== null && typeof selected !== 'undefined') {
                  isSelected = String(selected) === String(opt.id);
                }

                if (isSelected) {
                  input.checked = true;
                  optRow.classList.add('is-selected');
                }

                const txt = document.createElement('div');
                txt.className = 'flex-grow-1';
                txt.textContent = getOptionText(opt);

                optRow.appendChild(input);
                optRow.appendChild(txt);

                optRow.addEventListener('change', function () {
                  q.saved_answer = String(opt.id ?? j);
                  renderNav(); // Update visual state locally
                  // Remove 'is-selected' from others
                  const siblings = list.querySelectorAll('.opt-item');
                  siblings.forEach(el => el.classList.remove('is-selected'));
                  optRow.classList.add('is-selected');
                });

                list.appendChild(optRow);
              });

              wrapper.appendChild(list);
            }

            elQViewer.innerHTML = '';
            elQViewer.appendChild(wrapper);

            btnPrevQ.disabled = activeIndex <= 0;
            // btnNextQ is now just navigation, user might want to use "Answer" button to proceed
            btnNextQ.disabled = activeIndex >= (questions.length - 1);

            // Update Answer/Finish button state and text
            if (activeIndex === (questions.length - 1)) {
              btnAnswer.textContent = '{{ __('Finish Exam') }}';
              btnAnswer.classList.remove('btn-primary');
              btnAnswer.classList.add('btn-success');
            } else {
              btnAnswer.textContent = '{{ __('Answer') }}';
              btnAnswer.classList.remove('btn-success');
              btnAnswer.classList.add('btn-primary');
            }
          }

          function setActiveIndex(idx) {
            const next = Math.max(0, Math.min(idx, questions.length - 1));
            activeIndex = next;
            renderNav();
            renderViewer();
            try {
              const pills = elQNavList.querySelectorAll('.q-pill');
              if (pills && pills[activeIndex]) {
                pills[activeIndex].scrollIntoView({ block: 'nearest', inline: 'nearest' });
              }
            } catch (e) { }
          }

          function goPrev() { setActiveIndex(activeIndex - 1); }
          function goNext() { setActiveIndex(activeIndex + 1); }

          async function onAnswerClick() {
            if (isSaving) return;
            const q = questions[activeIndex];
            if (!q) return;

            // If it's the last question, we just confirm finish? 
            // The user said "Click to answer, and if last question, finish exam"
            // So we should save the answer first if selected.

            if (activeIndex === (questions.length - 1)) {
              // Last question
              if (q.saved_answer) {
                await saveAnswer(q);
              }
              if (confirm('{{ __('Are you sure you want to finish the exam?') }}')) {
                submitExam();
              }
              return;
            }

            // Normal question: Save and Next
            if (!q.saved_answer) {
              // If no answer selected, just go next? Or block?
              // Usually allow skip. passing to next.
              goNext();
            } else {
              await saveAnswer(q);
              goNext();
            }
          }

          async function saveAnswer(q) {
            if (!saveUrl) return;
            isSaving = true;
            btnAnswer.disabled = true;

            try {
              const res = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                  'X-ATTEMPT-SESSION': attemptSession
                },
                body: JSON.stringify({
                  question_id: q.id,
                  response: { value: q.saved_answer }
                })
              });

              if (!res.ok) {
                console.error('Failed to save');
                // Optional: show toast
              }
            } catch (e) {
              console.error(e);
            } finally {
              isSaving = false;
              btnAnswer.disabled = false;
            }
          }

          async function submitExam() {
            if (!submitUrl) return;
            isSaving = true;
            btnAnswer.disabled = true;
            btnAnswer.textContent = '{{ __('Submitting...') }}';

            try {
              const res = await fetch(submitUrl, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                  'X-ATTEMPT-SESSION': attemptSession
                }
              });

              if (res.ok) {
                window.location.href = "{{ route('student.exams.index') }}";
              } else {
                alert('{{ __('Failed to submit exam. Please try again.') }}');
                btnAnswer.disabled = false;
                btnAnswer.textContent = '{{ __('Finish Exam') }}';
              }
            } catch (e) {
              console.error(e);
              alert('{{ __('Error submitting exam.') }}');
              btnAnswer.disabled = false;
            } finally {
              isSaving = false;
            }
          }

          btnPrevQ.addEventListener('click', goPrev);
          btnNextQ.addEventListener('click', goNext);
          btnAnswer.addEventListener('click', onAnswerClick);

          document.addEventListener('keydown', function (e) {
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) return;

            if (!isRtl) {
              if (e.key === 'ArrowLeft') goPrev();
              if (e.key === 'ArrowRight') goNext();
            } else {
              if (e.key === 'ArrowRight') goPrev();
              if (e.key === 'ArrowLeft') goNext();
            }
          });

          function renderError(msg) {
            elQNavList.innerHTML = '';
            elQViewer.innerHTML = '';
            elQCountText.textContent = '—';
            const card = document.createElement('div');
            card.className = 'alert alert-danger';
            card.textContent = msg;
            elQViewer.appendChild(card);
          }

          async function loadQuestions() {
            if (!questionsEndpoint) { renderError('{{ __('Questions endpoint is missing.') }}'); return; }

            try {
              const res = await fetch(questionsEndpoint, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
              });

              if (!res.ok) {
                renderError('{{ __('Failed to load questions.') }}' + ' (' + res.status + ')');
                return;
              }

              const payload = await res.json();
              questions = normalizeQuestions(payload);

              if (!questions.length) {
                renderError('{{ __('No questions available for this exam.') }}');
                return;
              }

              activeIndex = 0;
              renderNav();
              renderViewer();
            } catch (e) {
              renderError('{{ __('Failed to load questions. Please refresh the page.') }}');
            }
          }

          if (remaining !== null && typeof remaining !== 'undefined') {
            elTimerText.textContent = formatTime(remaining);
            setInterval(tickTimer, 1000);
          } else {
            elTimerText.textContent = '—';
          }

          loadQuestions();
        })();
      </script>
    @endpush

  @else
    {{-- =========================
    EXAM DETAILS VIEW
    ========================= --}}
    <div class="card student-card mb-4">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
          <div class="stu-page-title h3 mb-1">{{ $title }}</div>
          <div class="stu-subtitle">{{ __('No grades are shown to students.') }}</div>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('student.exams.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to Exams') }}
          </a>
        </div>
      </div>
    </div>

    <div class="card student-card mb-3">
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-center">
          @if($duration)
            <span class="badge badge-soft-primary">⏱ {{ $duration }} {{ __('min') }}</span>
          @endif
          @if(!is_null($attemptsLimit))
            <span class="badge badge-soft-secondary">🎯 {{ __('Attempts') }}: {{ $attemptsLimit }}</span>
          @endif
          @if(!is_null($attemptsRemaining))
            <span class="badge badge-soft-success">✅ {{ __('Remaining') }}: {{ $attemptsRemaining }}</span>
          @endif
          <span class="badge badge-soft-primary">🔒 {{ __('Correct answers are hidden') }}</span>
        </div>

        <div class="mt-2 text-muted">
          {{ __('Start from the instructions page. If you have an active attempt, you can continue it.') }}
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12 col-lg-8">
        <div class="card student-card">
          <div class="card-body">
            <div class="h5 fw-bold mb-2">{{ __('Overview') }}</div>

            <div class="sp-card-title mb-2">{{ __('Instructions') }}</div>

            <ul class="instruction-list mb-0" style="padding-inline-start: 1.2rem;">
              <li style="margin-bottom: .5rem; color: rgba(15,23,42,.82);">
                {{ __('Do not refresh the page during the exam.') }}
              </li>
              <li style="margin-bottom: .5rem; color: rgba(15,23,42,.82);">
                {{ __('Your answers are saved automatically.') }}
              </li>
              <li style="margin-bottom: .5rem; color: rgba(15,23,42,.82);">
                {{ __('If time ends, your attempt will be submitted automatically.') }}
              </li>
              <li style="margin-bottom: .5rem; color: rgba(15,23,42,.82);">
                {{ __('Grades and correct answers are not shown to students.') }}
              </li>
            </ul>

            <hr class="soft-divider my-3" style="border-top: 1px dashed rgba(15,23,42,.14);">

            <div class="d-flex flex-wrap gap-2 align-items-center">
              @if($roomUrl)
                <a href="{{ $roomUrl }}" class="btn btn-primary btn-wide" style="min-width: 190px;">
                  {{ __('Continue Exam') }}
                </a>
              @else
                <form method="POST" action="{{ $startUrl }}" class="m-0">
                  @csrf
                  <button type="submit" class="btn btn-primary btn-wide" style="min-width: 190px;" @if(!$canStart) disabled
                  @endif>
                    {{ __('Start Exam') }}
                  </button>
                </form>
              @endif

              @if(!$roomUrl && !$canStart)
                <div class="d-inline-block text-danger small ms-2">
                  {{ __('Max attempts reached') }}
                </div>
              @endif
            </div>

            <div class="text-muted small mt-3">
              {{ __('No grades will be shown after submission.') }}
            </div>
          </div>
        </div>

        <div class="card student-card mt-3">
          <div class="card-body">
            <div class="h5 fw-bold mb-2">{{ __('Good to know') }}</div>
            <div class="text-muted">
              {{ __('During the exam, avoid refreshing the page or closing the tab. If time ends, your attempt may be submitted automatically.') }}
            </div>

            <div class="mt-3 d-flex flex-wrap gap-2">
              <span class="badge badge-soft-secondary">⚡ {{ __('Auto-save') }}</span>
              <span class="badge badge-soft-secondary">🧠 {{ __('Focus') }}</span>
              <span class="badge badge-soft-secondary">🧭 {{ __('Navigate by question numbers') }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card student-card">
          <div class="card-body">
            <div class="h5 fw-bold mb-2">{{ __('Exam Info') }}</div>
            <ul class="list-unstyled mb-0 small text-muted">
              <li class="mb-2">🆔 <span class="text-dark">{{ $examId ?? '—' }}</span></li>
              <li class="mb-2">⏱ <span class="text-dark">{{ $duration ? $duration . ' ' . __('min') : '—' }}</span></li>
              <li class="mb-2">🎯 <span class="text-dark">{{ $attemptsLimit ?? '—' }}</span></li>
              <li class="mb-2">🔒 <span class="text-dark">{{ __('Hidden answers') }}</span></li>
            </ul>
          </div>
        </div>


      </div>
    </div>
  @endif
@endsection