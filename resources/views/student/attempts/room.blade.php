{{-- resources/views/student/attempts/room.blade.php --}}
@extends('layouts.student')

@section('title', __('Exam Room'))

@php
  $examObj = $exam ?? $examData ?? null;
  $attemptObj = $attempt ?? $attemptData ?? null;

  $examId = $examId ?? ($examObj->id ?? $examObj['id'] ?? ($attemptObj->exam_id ?? $attemptObj['exam_id'] ?? null));
  $attemptId = $attemptId ?? ($attemptObj->id ?? $attemptObj['id'] ?? request()->route('attempt') ?? null);

  $endpoints = $endpoints ?? [];

  $questionsUrl = $endpoints['questions'] ?? ($examId ? route('student.exams.questions', ['exam_id' => $examId]) : null);

  $saveUrl = $endpoints['save'] ?? ($attemptId ? route('student.attempts.save', ['attempt' => $attemptId]) : null);
  $heartbeatUrl = $endpoints['heartbeat'] ?? ($attemptId ? route('student.attempts.heartbeat', ['attempt' => $attemptId]) : null);
  $submitUrl = $endpoints['submit'] ?? ($attemptId ? route('student.attempts.submit', ['attempt' => $attemptId]) : null);

  $attemptSession = $attemptSession
    ?? ($attemptObj->session_token ?? $attemptObj['session_token'] ?? $attemptObj->attempt_session ?? $attemptObj['attempt_session'] ?? null);

  $examTitle = $examObj->title ?? $examObj['title'] ?? __('Exam');

  $locale = app()->getLocale();
  $isRtl = in_array($locale, ['ar', 'fa', 'ur']);
@endphp

@section('content')
  @push('head')
    <style>
      .exam-room-topbar{
        background: linear-gradient(135deg, rgba(13,110,253,.10), rgba(25,135,84,.08));
        border: 1px solid var(--stu-border);
        border-radius: var(--stu-radius);
        padding: 14px;
      }

      .status-indicator{
        display:inline-flex; align-items:center; gap: 8px;
        padding: 8px 10px;
        border-radius: 999px;
        border: 1px solid rgba(15,23,42,.10);
        background: rgba(255,255,255,.92);
        font-weight: 900;
        font-size: .85rem;
        color: rgba(15,23,42,.78);
      }
      .status-indicator .dot{ width: 10px; height: 10px; border-radius: 999px; background: rgba(15,23,42,.28); }
      .status-indicator.saving .dot{ background: rgba(245,158,11,.95); }
      .status-indicator.saved .dot{ background: rgba(25,135,84,.95); }

      .timer-pill{
        display:inline-flex; align-items:center; gap: 8px;
        padding: 8px 10px;
        border-radius: 999px;
        border: 1px solid rgba(15,23,42,.10);
        background: rgba(255,255,255,.92);
        font-variant-numeric: tabular-nums;
        font-weight: 950;
        letter-spacing: .2px;
      }
      .timer-pill.normal{ color: rgba(15,23,42,.80); }
      .timer-pill.warning{ color: #8a5a00; border-color: rgba(245,158,11,.28); background: rgba(245,158,11,.14); }
      .timer-pill.danger{ color: #dc3545; border-color: rgba(220,53,69,.24); background: rgba(220,53,69,.10); }

      .modern-progress{
        height: 10px;
        border-radius: 999px;
        background: rgba(15,23,42,.08);
        overflow: hidden;
      }
      .modern-progress .progress-bar{
        border-radius: 999px;
        background: rgba(13,110,253,.95);
      }

      .question-nav-sidebar .question-grid{
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 8px;
      }
      @media (max-width: 1200px){
        .question-nav-sidebar .question-grid{ grid-template-columns: repeat(5, 1fr); }
      }
      @media (max-width: 576px){
        .question-nav-sidebar .question-grid{ grid-template-columns: repeat(6, 1fr); }
      }

      .question-btn{
        height: 40px;
        border-radius: 14px;
        border: 1px solid rgba(15,23,42,.12);
        background: #fff;
        font-weight: 950;
        transition: transform .12s ease, background .12s ease, border-color .12s ease;
      }
      .question-btn:hover{ background: rgba(13,110,253,.06); border-color: rgba(13,110,253,.22); transform: translateY(-1px); }
      .question-btn.current{ background: rgba(13,110,253,.12); border-color: rgba(13,110,253,.30); color: #0d6efd; }
      .question-btn.answered{ background: rgba(25,135,84,.10); border-color: rgba(25,135,84,.22); color: #198754; }
      .question-btn.unanswered{ background: #fff; }
      .question-btn.flagged{ box-shadow: inset 0 0 0 2px rgba(245,158,11,.32); }

      .chip-like{
        display:inline-flex;
        align-items:center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        border: 1px solid rgba(15,23,42,.12);
        background: #fff;
        font-weight: 800;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease, transform .1s ease;
      }
      .chip-like:hover{
        background: rgba(13,110,253,.06);
        border-color: rgba(13,110,253,.20);
        transform: translateY(-1px);
      }
      .chip-like.active{
        background: rgba(13,110,253,.10);
        border-color: rgba(13,110,253,.24);
        color: #0d6efd;
      }

      .nav-legend .legend-item{
        display:inline-flex; align-items:center; gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(15,23,42,.08);
        background: rgba(255,255,255,.9);
        font-weight: 800;
        font-size: .82rem;
      }
      .legend-dot{ width: 10px; height: 10px; border-radius: 999px; display:inline-block; background: rgba(15,23,42,.20); }
      .legend-dot.current{ background: rgba(13,110,253,.95); }
      .legend-dot.answered{ background: rgba(25,135,84,.95); }
      .legend-dot.unanswered{ background: rgba(15,23,42,.28); }
      .legend-dot.flagged{ background: rgba(245,158,11,.95); }

      .question-card{
        border: 1px solid rgba(15,23,42,.08);
        border-radius: var(--stu-radius);
        padding: 14px;
        background: #fff;
      }
      .question-title .q-ar{
        font-weight: 950;
        font-size: 1.08rem;
        letter-spacing: .2px;
        line-height: 1.3;
      }

      .option-card{
        display:flex;
        gap: 10px;
        align-items:flex-start;
        padding: 12px;
        border-radius: 16px;
        border: 1px solid rgba(15,23,42,.10);
        background: #fff;
        cursor: pointer;
        transition: transform .12s ease, background .12s ease, border-color .12s ease;
      }
      .option-card:hover{ background: rgba(13,110,253,.05); border-color: rgba(13,110,253,.22); transform: translateY(-1px); }
      .option-card.selected{ background: rgba(13,110,253,.10); border-color: rgba(13,110,253,.30); }

      .exam-room-hints{
        display:flex; flex-wrap:wrap; gap: 10px; align-items:center; justify-content:space-between;
        margin-top: 10px;
      }
      .exam-room-hints .hint{ color: rgba(15,23,42,.62); font-size: .92rem; }

      .stu-kbd{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width: 34px;
        padding: 6px 8px;
        border-radius: 10px;
        border: 1px solid rgba(15,23,42,.12);
        background: rgba(255,255,255,.92);
        font-weight: 900;
        font-size: .85rem;
      }

      .focus-toggle{ display:inline-flex; align-items:center; gap: 8px; }
    </style>
  @endpush

  <div
    x-data="ExamRoom()"
    x-init="init()"
    class="exam-room"
    data-exam-id="{{ $examId }}"
    data-attempt-id="{{ $attemptId }}"
    data-questions-url="{{ $questionsUrl }}"
    data-save-url="{{ $saveUrl }}"
    data-heartbeat-url="{{ $heartbeatUrl }}"
    data-submit-url="{{ $submitUrl }}"
    data-attempt-session="{{ $attemptSession }}"
    data-is-rtl="{{ $isRtl ? '1' : '0' }}"
  >
    {{-- Top bar --}}
    <div class="exam-room-topbar">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div style="min-width: 260px;">
          <div class="h6 mb-0" style="font-weight: 950;">{{ $examTitle }}</div>
          <div class="text-muted small">
            {{ __('No grades or correct answers are shown to students.') }}
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
          <button type="button" class="btn btn-outline-secondary focus-toggle"
                  @click="toggleFocusMode()" :disabled="submitting">
            <span x-text="focusMode ? '🧘 {{ __('Focus On') }}' : '🧩 {{ __('Focus Off') }}'"></span>
          </button>

          <div class="status-indicator" :class="saving ? 'saving' : (savedOnce ? 'saved' : '')" role="status" aria-live="polite">
            <span class="dot"></span>
            <span x-text="saving ? '{{ __('Saving...') }}' : (savedOnce ? '{{ __('Saved') }}' : '{{ __('Ready') }}')"></span>
          </div>

          <div class="timer-pill" :class="timeLeft <= 300 ? 'danger' : (timeLeft <= 900 ? 'warning' : 'normal')">
            ⏱ <span x-text="formatTime(timeLeft)"></span>
          </div>

          <button class="btn btn-danger"
                  type="button"
                  :disabled="submitting || locked"
                  @click="confirmSubmit()">
            {{ __('Submit') }}
          </button>
        </div>
      </div>

      {{-- Progress bar --}}
      <div class="exam-progress-container mt-3">
        <div class="d-flex align-items-center justify-content-between mb-1 small text-muted">
          <div>
            <strong x-text="answeredCount"></strong>/<span x-text="totalQuestions"></span> {{ __('answered') }}
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" type="button" @click="jumpToUnanswered()" :disabled="locked || totalQuestions === 0">
              {{ __('Jump to Unanswered') }}
            </button>
          </div>
        </div>

        <div class="progress modern-progress" role="progressbar" aria-label="progress">
          <div class="progress-bar" :style="`width:${progressPercent()}%`"></div>
        </div>

        <div class="exam-room-hints">
          <div class="hint">🔒 {{ __('Correct answers and points are hidden.') }}</div>
          <div class="d-flex flex-wrap gap-2">
            <span class="hint"><span class="stu-kbd">↑</span><span class="stu-kbd">↓</span> {{ __('Navigate') }}</span>
            <span class="hint"><span class="stu-kbd">Esc</span> {{ __('Close menu') }}</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Main grid --}}
    <div class="row g-3 mt-2">
      {{-- Sidebar nav --}}
      <div class="col-12 col-lg-4 col-xl-3">
        <div class="card student-card question-nav-sidebar sticky-top" style="top: 90px;">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="h6 mb-0" style="font-weight: 950;">{{ __('Questions') }}</div>
              <span class="badge badge-soft-secondary" x-text="totalQuestions"></span>
            </div>

            {{-- Filters --}}
            <div class="d-flex flex-wrap gap-2 mb-3">
              <button type="button" class="chip-like" :class="navFilter==='all'?'active':''" @click="navFilter='all'">
                {{ __('All') }}
              </button>
              <button type="button" class="chip-like" :class="navFilter==='unanswered'?'active':''" @click="navFilter='unanswered'">
                {{ __('Unanswered') }}
              </button>
              <button type="button" class="chip-like" :class="navFilter==='flagged'?'active':''" @click="navFilter='flagged'">
                {{ __('Flagged') }}
              </button>
            </div>

            {{-- Legend --}}
            <div class="nav-legend small text-muted mb-2">
              <div class="d-flex flex-wrap gap-2">
                <span class="legend-item"><span class="legend-dot current"></span> {{ __('Current') }}</span>
                <span class="legend-item"><span class="legend-dot answered"></span> {{ __('Answered') }}</span>
                <span class="legend-item"><span class="legend-dot unanswered"></span> {{ __('Unanswered') }}</span>
                <span class="legend-item"><span class="legend-dot flagged"></span> {{ __('Flagged') }}</span>
              </div>
            </div>

            {{-- Grid --}}
            <div class="question-grid">
              <template x-for="(q, idx) in filteredNavQuestions()" :key="q._key">
                <button type="button"
                        class="question-btn"
                        :class="navBtnClass(q, idx)"
                        @click="goTo(idx)"
                        :disabled="locked"
                        :title="`${idx+1}`">
                  <span x-text="idx+1"></span>
                </button>
              </template>
            </div>

            <div class="text-muted small mt-3" x-show="locked">
              🔒 {{ __('Attempt is locked. You can no longer edit answers.') }}
            </div>
          </div>
        </div>
      </div>

      {{-- Questions area --}}
      <div class="col-12 col-lg-8 col-xl-9">
        <div class="card student-card">
          <div class="card-body">
            <template x-if="loading">
              <div class="text-center py-5">
                <div class="spinner-border" role="status" aria-label="loading"></div>
                <div class="text-muted mt-2">{{ __('Loading questions...') }}</div>
              </div>
            </template>

            <template x-if="errorMsg">
              <div class="alert alert-danger" x-text="errorMsg"></div>
            </template>

            <template x-if="!loading && !errorMsg">
              <div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge badge-soft-primary">
                      {{ __('Question') }} <span x-text="currentIndex + 1"></span>/<span x-text="totalQuestions"></span>
                    </span>

                    <span class="badge" :class="isAnswered(currentQuestion()) ? 'badge-soft-success' : 'badge-soft-secondary'">
                      <span x-text="isAnswered(currentQuestion()) ? '{{ __('Answered') }}' : '{{ __('Unanswered') }}'"></span>
                    </span>

                    <span class="badge badge-soft-warning" x-show="isFlagged(currentQuestion())">
                      🚩 {{ __('Flagged') }}
                    </span>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                    <button type="button"
                            class="btn btn-sm btn-outline-warning"
                            @click="toggleFlag(currentQuestion())"
                            :disabled="locked || !currentQuestion()">
                      <span x-text="isFlagged(currentQuestion()) ? '🚩 {{ __('Unflag') }}' : '🚩 {{ __('Flag') }}'"></span>
                    </button>

                    <div class="btn-group" role="group">
                      <button type="button" class="btn btn-sm btn-outline-secondary" @click="prev()" :disabled="locked || currentIndex<=0">
                        {{ $isRtl ? '→' : '←' }} {{ __('Prev') }}
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-secondary" @click="next()" :disabled="locked || currentIndex>=totalQuestions-1">
                        {{ __('Next') }} {{ $isRtl ? '←' : '→' }}
                      </button>
                    </div>
                  </div>
                </div>

                <div class="question-card">
                  <div class="question-title">
                    <div class="q-ar" x-text="currentQuestion()?.text_ar || currentQuestion()?.text || ''"></div>
                    <div class="q-en text-muted small mt-1" x-show="(currentQuestion()?.text_en || '').length"
                         x-text="currentQuestion()?.text_en"></div>
                  </div>

                  <template x-if="isMcq(currentQuestion())">
                    <div class="options-list mt-3">
                      <template x-for="opt in currentQuestion().options" :key="opt._key">
                        <label class="option-card" :class="isSelected(currentQuestion(), opt) ? 'selected' : ''">
                          <input class="form-check-input mt-1"
                                 type="radio"
                                 :name="'q_'+currentQuestion().id"
                                 :value="opt.value"
                                 :checked="isSelected(currentQuestion(), opt)"
                                 @change="onMcqChange(currentQuestion(), opt)"
                                 :disabled="locked">
                          <div class="option-text">
                            <div class="opt-ar" style="font-weight: 850;" x-text="opt.label_ar || opt.label || opt.text_ar || opt.text || ''"></div>
                            <div class="opt-en small text-muted mt-1"
                                 x-show="(opt.label_en || opt.text_en || '').length"
                                 x-text="opt.label_en || opt.text_en"></div>
                          </div>
                        </label>
                      </template>
                    </div>
                  </template>

                  <template x-if="isReorder(currentQuestion())">
                    <div class="mt-3">
                      <div class="alert alert-info small">
                        {{ __('Use the Up/Down buttons to reorder the items.') }}
                      </div>
                      <div class="reorder-list" :data-reorder-question="currentQuestion().id">
                        <template x-for="(opt, idx) in currentQuestion().options" :key="opt._key">
                          <div class="reorder-item d-flex align-items-center gap-2 mb-2 p-2 border rounded" :data-option-id="opt.value">
                            <div class="flex-grow-1">
                              <div class="fw-bold" x-text="opt.label_ar || opt.label || opt.text_ar || opt.text || ''"></div>
                              <div class="small text-muted" x-show="(opt.label_en || opt.text_en || '').length" x-text="opt.label_en || opt.text_en"></div>
                            </div>
                            <div class="btn-group-vertical" role="group">
                              <button type="button" class="btn btn-sm btn-outline-secondary" @click="moveReorderUp(currentQuestion(), idx)" :disabled="locked || idx === 0">↑</button>
                              <button type="button" class="btn btn-sm btn-outline-secondary" @click="moveReorderDown(currentQuestion(), idx)" :disabled="locked || idx === currentQuestion().options.length - 1">↓</button>
                            </div>
                          </div>
                        </template>
                      </div>
                    </div>
                  </template>

                  <template x-if="isEssay(currentQuestion())">
                    <div class="mt-3">
                      <textarea class="form-control"
                                rows="6"
                                placeholder="{{ __('Type your answer here...') }}"
                                x-model="answers[currentQuestion().id]"
                                @input="onEssayInput(currentQuestion())"
                                :disabled="locked"></textarea>
                      <div class="text-muted small mt-2">
                        {{ __('Your answer is saved automatically.') }}
                      </div>
                    </div>
                  </template>

                  <template x-if="currentQuestion() && !isMcq(currentQuestion()) && !isEssay(currentQuestion()) && !isReorder(currentQuestion())">
                    <div class="alert alert-warning mt-3">
                      {{ __('Unsupported question type. Please contact your teacher.') }}
                    </div>
                  </template>
                </div>

                <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
                  <button type="button" class="btn btn-outline-secondary" @click="jumpToUnanswered()" :disabled="locked">
                    {{ __('Jump to Unanswered') }}
                  </button>

                  <button type="button" class="btn btn-danger" @click="confirmSubmit()" :disabled="submitting || locked">
                    {{ __('Submit Attempt') }}
                  </button>
                </div>

                <div class="text-muted small mt-3">
                  {{ __('No scores will be shown after submission.') }}
                </div>
              </div>
            </template>
          </div>
        </div>

        <div class="alert alert-info mt-3" x-show="locked">
          {{ __('This attempt has been submitted or locked. Editing is disabled.') }}
        </div>
      </div>
    </div>
  </div>

  {{-- ✅ JS: same logic كما هو (UI only tweaks above) --}}
  @push('scripts')
  <script>
    function ExamRoom() {
      return {
        loading: true,
        errorMsg: '',
        locked: false,
        submitting: false,
        saving: false,
        savedOnce: false,

        questions: [],
        currentIndex: 0,
        answers: {},
        navFilter: 'all',
        flagged: {},

        focusMode: false,

        timeLeft: 0,
        tickInterval: null,
        heartbeatInterval: null,
        autosaveInterval: null,
        stopEverything: false,

        get totalQuestions() { return this.questions.length; },
        get answeredCount() {
          let c = 0;
          for (const q of this.questions) {
            const v = this.answers[q.id];
            if (v !== undefined && v !== null && String(v).trim() !== '') c++;
          }
          return c;
        },

        toastSuccess(msg){ if (window.StudentUI?.success) StudentUI.success(msg); },
        toastError(msg){ if (window.StudentUI?.error) StudentUI.error(msg); },
        toastInfo(msg){ if (window.StudentUI?.info) StudentUI.info(msg); },

        formatTime(sec){
          if (window.StudentUI?.formatTime) return StudentUI.formatTime(sec);
          sec = Math.max(0, parseInt(sec||0,10));
          const m = Math.floor(sec/60), s = sec%60;
          return `${m}:${String(s).padStart(2,'0')}`;
        },

        progressPercent(){
          if (!this.totalQuestions) return 0;
          return Math.round((this.answeredCount / this.totalQuestions) * 100);
        },

        attr(name){ return this.$root?.dataset?.[name] || ''; },
        get endpoints(){
          return {
            questions: this.attr('questionsUrl'),
            save: this.attr('saveUrl'),
            heartbeat: this.attr('heartbeatUrl'),
            submit: this.attr('submitUrl'),
          };
        },
        get attemptSession(){ return this.attr('attemptSession'); },
        get isRtl(){ return this.attr('isRtl') === '1'; },

        focusStorageKey(){
          const attemptId = this.attr('attemptId') || 'unknown';
          return `student_focus_${attemptId}`;
        },
        applyFocusMode(){
          try{
            if (this.focusMode) document.body.classList.add('exam-focus');
            else document.body.classList.remove('exam-focus');
          }catch(e){}
        },
        loadFocusMode(){
          try{
            const raw = localStorage.getItem(this.focusStorageKey());
            this.focusMode = raw ? (raw === '1') : false;
          }catch(e){ this.focusMode = false; }
          this.applyFocusMode();
        },
        toggleFocusMode(){
          this.focusMode = !this.focusMode;
          try{ localStorage.setItem(this.focusStorageKey(), this.focusMode ? '1' : '0'); }catch(e){}
          this.applyFocusMode();
          this.toastInfo(this.focusMode ? '{{ __('Focus mode enabled') }}' : '{{ __('Focus mode disabled') }}');
        },

        async init() {
          try {
            this.loadFocusMode();
            this.loadFlagged();
            await this.loadQuestions();
            this.startTimerLoops();
            this.bindSafetyGuards();
            this.bindKeyboardNav();
            this.loading = false;
          } catch (e) {
            this.loading = false;
            this.errorMsg = e?.message || 'Failed to initialize exam room.';
          }
        },

        bindSafetyGuards(){
          const self = this;
          function handler(e){
            if (self.locked) return;
            e.preventDefault();
            e.returnValue = '';
            return '';
          }
          window.addEventListener('beforeunload', handler);
          this.$watch('locked', function(v){
            if (v) { try { window.removeEventListener('beforeunload', handler); } catch(e) {} }
          });
        },

        bindKeyboardNav(){
          const self = this;
          document.addEventListener('keydown', function(e){
            if (self.locked) return;
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable)) return;

            if (e.key === 'ArrowUp') { e.preventDefault(); self.prev(); }
            if (e.key === 'ArrowDown') { e.preventDefault(); self.next(); }

            if (!self.isRtl) {
              if (e.key === 'ArrowLeft') { e.preventDefault(); self.prev(); }
              if (e.key === 'ArrowRight') { e.preventDefault(); self.next(); }
            } else {
              if (e.key === 'ArrowRight') { e.preventDefault(); self.prev(); }
              if (e.key === 'ArrowLeft') { e.preventDefault(); self.next(); }
            }
          });
        },

        headers() {
          const h = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          };
          const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          if (token) h['X-CSRF-TOKEN'] = token;
          if (this.attemptSession) h['X-ATTEMPT-SESSION'] = this.attemptSession;
          return h;
        },

        async safeFetch(url, options) {
          if (this.stopEverything) throw new Error('Stopped');
          const res = await fetch(url, options);
          if (res.status === 409 || res.status === 403) {
            this.lockAndStop(res.status);
          }
          return res;
        },

        lockAndStop(code) {
          this.locked = true;
          this.stopAllIntervals();
          this.stopEverything = true;
          this.toastError(code === 409
            ? '{{ __('Your attempt is no longer active (409).') }}'
            : '{{ __('Access denied or session expired (403).') }}'
          );
        },

        stopAllIntervals() {
          if (this.tickInterval) clearInterval(this.tickInterval);
          if (this.heartbeatInterval) clearInterval(this.heartbeatInterval);
          if (this.autosaveInterval) clearInterval(this.autosaveInterval);
          this.tickInterval = this.heartbeatInterval = this.autosaveInterval = null;
        },

        async loadQuestions() {
          const url = this.endpoints.questions;
          if (!url) throw new Error('Questions endpoint missing.');

          const res = await this.safeFetch(url, { method: 'GET', headers: this.headers() });
          if (!res.ok) throw new Error('Failed to load questions.');

          const data = await res.json();
          const questions = Array.isArray(data) ? data : (data.questions || data.data || []);
          const serverAnswers = data.answers || data.student_answers || {};
          const timeLeft = data.time_left ?? data.attempt?.time_left ?? data.attempt?.remaining_seconds ?? null;

          this.questions = (questions || []).map((q, i) => this.normalizeQuestion(q, i));
          this.answers = this.normalizeAnswers(serverAnswers);

          this.timeLeft = Number.isFinite(+timeLeft) ? parseInt(timeLeft, 10) : (this.timeLeft || 0);

          const locked = data.locked ?? data.attempt?.locked ?? data.attempt?.is_locked ?? false;
          if (locked) {
            this.locked = true;
            this.stopAllIntervals();
            this.stopEverything = true;
          }

          this.currentIndex = Math.min(this.currentIndex, Math.max(0, this.questions.length - 1));
        },

        normalizeQuestion(raw, idx) {
          const q = Object.assign({}, raw);
          q.id = q.id ?? q.question_id ?? `q_${idx}`;
          q._key = `k_${q.id}_${idx}`;

          q.text_ar = q.text_ar ?? q.title_ar ?? q.question_ar ?? q.text ?? q.title ?? '';
          q.text_en = q.text_en ?? q.title_en ?? q.question_en ?? '';

          q.type = (q.type ?? q.question_type ?? q.kind ?? '').toString().toLowerCase();

          const opts = q.options ?? q.choices ?? q.answers ?? [];
          q.options = (opts || []).map((o, j) => ({
            _key: `o_${q.id}_${j}`,
            value: o.value ?? o.id ?? o.key ?? o.code ?? (o.label ?? o.text ?? j),
            label_ar: o.label_ar ?? o.text_ar ?? o.label ?? o.text ?? '',
            label_en: o.label_en ?? o.text_en ?? '',
          }));

          return q;
        },

        normalizeAnswers(serverAnswers) {
          if (Array.isArray(serverAnswers)) {
            const out = {};
            for (const row of serverAnswers) {
              const qid = row.question_id ?? row.id;
              const v = row.answer ?? row.value ?? row.student_response ?? row.response ?? '';
              if (qid) out[qid] = this.extractAnswerValue(v);
            }
            return out;
          }
          if (serverAnswers && typeof serverAnswers === 'object') {
            const out = {};
            for (const [qid, v] of Object.entries(serverAnswers)) {
              out[qid] = this.extractAnswerValue(v);
            }
            return out;
          }
          return {};
        },

        extractAnswerValue(v) {
          try {
            if (typeof v === 'string' && v.trim().startsWith('{')) {
              const j = JSON.parse(v);
              if (Array.isArray(j.order)) return '__reordered__';
              return j.answer ?? j.value ?? j.text ?? v;
            }
            if (v && typeof v === 'object') {
              if (Array.isArray(v.order)) return '__reordered__';
              return v.answer ?? v.value ?? v.text ?? '__saved__';
            }
          } catch(e) {}
          return v;
        },

        currentQuestion(){ return this.questions[this.currentIndex] || null; },

        isMcq(q){
          if (!q) return false;
          return ['mcq','multiple_choice','multiple-choice','choice','select'].includes(q.type) || (q.options && q.options.length && !this.isReorder(q));
        },
        isEssay(q){
          if (!q) return false;
          return ['essay','text','written','open'].includes(q.type);
        },
        isReorder(q){
          if (!q) return false;
          return ['reorder','re_order','order','sorting','sequence'].includes(q.type);
        },

        isAnswered(q){
          if (!q) return false;
          const v = this.answers[q.id];
          return v !== undefined && v !== null && String(v).trim() !== '';
        },

        flagStorageKey(){
          const attemptId = this.attr('attemptId') || 'unknown';
          return `student_flags_${attemptId}`;
        },
        loadFlagged(){
          try {
            const raw = localStorage.getItem(this.flagStorageKey());
            this.flagged = raw ? JSON.parse(raw) : {};
          } catch(e) { this.flagged = {}; }
        },
        saveFlagged(){
          try { localStorage.setItem(this.flagStorageKey(), JSON.stringify(this.flagged || {})); } catch(e) {}
        },
        isFlagged(q){
          if (!q) return false;
          return !!this.flagged[q.id];
        },
        toggleFlag(q){
          if (!q || this.locked) return;
          this.flagged[q.id] = !this.flagged[q.id];
          this.saveFlagged();
          this.toastInfo(this.flagged[q.id] ? '{{ __('Flagged for review') }}' : '{{ __('Removed flag') }}');
        },

        filteredNavQuestions() {
          if (this.navFilter === 'unanswered') return this.questions.filter(q => !this.isAnswered(q));
          if (this.navFilter === 'flagged') return this.questions.filter(q => this.isFlagged(q));
          return this.questions;
        },

        navBtnClass(q, idx) {
          const isCurrent = (this.questions[this.currentIndex]?.id === q.id);
          const answered = this.isAnswered(q);
          const flagged = this.isFlagged(q);

          return [
            isCurrent ? 'current' : '',
            answered ? 'answered' : 'unanswered',
            flagged ? 'flagged' : ''
          ].join(' ');
        },

        goTo(idx) {
          if (this.locked) return;
          idx = parseInt(idx,10);
          if (Number.isNaN(idx) || idx < 0 || idx >= this.totalQuestions) return;
          this.currentIndex = idx;

          const el = this.$root.querySelector('.question-card');
          if (el && window.StudentUI?.scrollTo) StudentUI.scrollTo(el);
        },

        next(){ this.goTo(Math.min(this.totalQuestions-1, this.currentIndex+1)); },
        prev(){ this.goTo(Math.max(0, this.currentIndex-1)); },

        jumpToUnanswered() {
          if (this.locked) return;
          const idx = this.questions.findIndex(q => !this.isAnswered(q));
          if (idx === -1) { this.toastInfo('{{ __('All questions are answered.') }}'); return; }
          this.goTo(idx);
        },

        isSelected(q, opt){
          if (!q || !opt) return false;
          const v = this.answers[q.id];
          return String(v) === String(opt.value);
        },

        onMcqChange(q, opt){
          if (!q || this.locked) return;
          this.answers[q.id] = String(opt.value);
          this.queueSave(q);
        },

        onEssayInput(q){
          if (!q || this.locked) return;
          this.queueSave(q, true);
        },

        moveReorderUp(q, idx){
          if (!q || this.locked || idx <= 0) return;
          const opts = q.options;
          [opts[idx-1], opts[idx]] = [opts[idx], opts[idx-1]];
          this.answers[q.id] = '__reordered__';
          this.queueSave(q, true);
        },

        moveReorderDown(q, idx){
          if (!q || this.locked || idx >= q.options.length - 1) return;
          const opts = q.options;
          [opts[idx], opts[idx+1]] = [opts[idx+1], opts[idx]];
          this.answers[q.id] = '__reordered__';
          this.queueSave(q, true);
        },

        saveDebounce: null,
        queueSave(q, silent=false){
          if (this.locked) return;
          if (this.saveDebounce) clearTimeout(this.saveDebounce);
          this.saveDebounce = setTimeout(() => { this.saveAnswer(q, silent); }, 350);
        },

        buildResponse(q){
          if (!q) return {};
          if (this.isReorder(q)) return { order: this.getReorderOrder(q) };
          if (this.isMcq(q)) return { answer: this.answers[q.id] ?? '' };
          if (this.isEssay(q)) return { text: this.answers[q.id] ?? '' };
          return { value: this.answers[q.id] ?? '' };
        },

        getReorderOrder(q){
          return (q.options || []).map(o => String(o.value)).filter(Boolean);
        },

        async saveAnswer(q, silent=false){
          const url = this.endpoints.save;
          if (!url) {
            if (!silent) this.toastInfo('{{ __('Saved locally') }}');
            this.savedOnce = true;
            return;
          }

          try {
            this.saving = true;

            const payload = { question_id: q.id, response: this.buildResponse(q) };

            const res = await this.safeFetch(url, {
              method: 'POST',
              headers: this.headers(),
              body: JSON.stringify(payload),
            });

            if (!res.ok) throw new Error('Save failed');

            this.savedOnce = true;
            if (!silent) this.toastSuccess('{{ __('Saved') }}');
          } catch (e) {
            if (!this.stopEverything) this.toastError('{{ __('Autosave failed') }}');
          } finally {
            this.saving = false;
          }
        },

        startTimerLoops(){
          this.tickInterval = setInterval(() => {
            if (this.stopEverything || this.locked) return;

            if (this.timeLeft > 0) {
              this.timeLeft--;
              if (this.timeLeft === 300) this.toastInfo('{{ __('Only 5 minutes left!') }}');
              if (this.timeLeft === 0) {
                this.toastInfo('{{ __('Time is up. Submitting...') }}');
                this.submitAttempt();
              }
            }
          }, 1000);

          this.heartbeatInterval = setInterval(() => {
            if (this.stopEverything || this.locked) return;
            this.sendHeartbeat();
          }, 15000);

          this.autosaveInterval = setInterval(() => {
            if (this.stopEverything || this.locked) return;
          }, 20000);
        },

        async sendHeartbeat(){
          const url = this.endpoints.heartbeat;
          if (!url) return;

          try {
            const res = await this.safeFetch(url, {
              method: 'POST',
              headers: this.headers(),
              body: JSON.stringify({ t: Date.now() }),
            });
            if (!res.ok) throw new Error('hb');
          } catch(e) {}
        },

        confirmSubmit(){
          if (this.locked || this.submitting) return;
          const ok = window.StudentUI?.confirm
            ? StudentUI.confirm('{{ __('Are you sure you want to submit?') }}')
            : confirm('{{ __('Are you sure you want to submit?') }}');
          if (ok) this.submitAttempt();
        },

        async submitAttempt(){
          if (this.locked || this.submitting) return;

          const url = this.endpoints.submit;
          if (!url) { this.toastError('{{ __('Submit endpoint missing.') }}'); return; }

          try {
            this.submitting = true;
            this.toastInfo('{{ __('Submitting...') }}');

            const payload = {
              answers: this.answers,
              flagged: Object.keys(this.flagged || {}).filter(k => this.flagged[k]),
            };

            const res = await this.safeFetch(url, {
              method: 'POST',
              headers: this.headers(),
              body: JSON.stringify(payload),
            });

            if (!res.ok) {
              if (res.status === 409 || res.status === 403) return;
              throw new Error('submit failed');
            }

            this.locked = true;
            this.stopAllIntervals();
            this.stopEverything = true;

            try { document.body.classList.remove('exam-focus'); } catch(e) {}

            this.toastSuccess('{{ __('Submitted successfully!') }}');

            setTimeout(() => {
              if (window.StudentUI?.redirect) {
                StudentUI.redirect('{{ route('student.exams.index') }}');
              } else {
                window.location.href = '{{ route('student.exams.index') }}';
              }
            }, 2000);

          } catch (e) {
            if (!this.stopEverything) this.toastError('{{ __('Submit failed. Please try again.') }}');
          } finally {
            this.submitting = false;
          }
        },
      };
    }
  </script>
  @endpush
@endsection
