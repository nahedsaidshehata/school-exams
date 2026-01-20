{{-- resources/views/admin/lessons/ai/questions/review.blade.php --}}
@extends('layouts.admin')

@section('title', __('AI Questions Review'))

@php
  $locale = app()->getLocale();
  $isRtl = in_array($locale, ['ar', 'fa', 'ur']);
  $dir = $isRtl ? 'rtl' : 'ltr';

  // $lesson, $langMode ('ar'|'en'|'both'), $draftQuestions (array), $draftJson (string)
@endphp

@section('content')
<div class="container-fluid" dir="{{ $dir }}">

  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1">مراجعة الأسئلة المُولّدة</h1>
      <div class="text-muted small">
        اللغة: <strong>{{ strtoupper($langMode ?? 'AR') }}</strong>
        — العدد: <strong>{{ is_array($draftQuestions ?? null) ? count($draftQuestions) : 0 }}</strong>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('admin.lessons.ai.questions.create', $lesson) }}" class="btn btn-outline-secondary">
        رجوع
      </a>
    </div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <form method="POST" action="{{ route('admin.lessons.ai.questions.store', $lesson) }}" id="reviewForm">
    @csrf
    <input type="hidden" name="draft_json" id="draft_json" value='{{ $draftJson ?? "" }}'>

    {{-- Bulk bar --}}
    <div class="card mb-3">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
          <input type="checkbox" class="form-check-input" id="selectAll">
          <label for="selectAll" class="small mb-0">تحديد الكل</label>
          <span class="text-muted small">— المحدد: <strong id="selectedCount">0</strong></span>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
          <select class="form-select form-select-sm" id="bulkDifficulty" style="min-width: 160px;">
            <option value="">تغيير Difficulty للمحدد…</option>
            <option value="easy">Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
          </select>

          <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn">
            حذف المحدد
          </button>

          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rebuildDraftJson(true)">
            تحديث JSON يدويًا
          </button>

          <button type="submit" class="btn btn-sm btn-success">
            حفظ الأسئلة
          </button>
        </div>
      </div>
    </div>

    <div id="questionsRoot">
      @foreach(($draftQuestions ?? []) as $index => $q)
        @php
          $qid = $q['id'] ?? ('q_' . $index);
          $type = $q['type'] ?? 'MCQ';
          $showAr = ($langMode === 'ar' || $langMode === 'both');
          $showEn = ($langMode === 'en' || $langMode === 'both');
          $difficulty = $q['difficulty'] ?? 'medium';
          if (!in_array($difficulty, ['easy','medium','hard'], true)) $difficulty = 'medium';
        @endphp

        <div class="card mb-3 question-card"
             data-qid="{{ $qid }}"
             data-qtype="{{ $type }}">

          <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
              <input type="checkbox" class="form-check-input q-select">
              <span class="badge bg-primary">{{ $type }}</span>

              <span class="text-muted small q-number">#{{ $index + 1 }}</span>

              {{-- ✅ Type is FIXED now (no dropdown) --}}

              {{-- Difficulty --}}
              <select class="form-select form-select-sm q-difficulty" style="min-width: 140px;">
                <option value="easy" @selected($difficulty==='easy')>Easy</option>
                <option value="medium" @selected($difficulty==='medium')>Medium</option>
                <option value="hard" @selected($difficulty==='hard')>Hard</option>
              </select>
            </div>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveCardUp(this)">▲</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveCardDown(this)">▼</button>

              <button type="button" class="btn btn-sm btn-outline-primary" onclick="duplicateQuestion(this)">
                Duplicate
              </button>

              <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuestion(this)">
                حذف
              </button>
            </div>
          </div>

          <div class="card-body">
            {{-- Question Text --}}
            <div class="row g-2 mb-3">
              @if($showAr)
              <div class="col-md-6">
                <label class="form-label">نص السؤال (AR)</label>
                <input type="text" class="form-control q-text-ar" value="{{ $q['text_ar'] ?? '' }}" placeholder="اكتب نص السؤال بالعربية">
              </div>
              @endif

              @if($showEn)
              <div class="col-md-6">
                <label class="form-label">Question Text (EN)</label>
                <input type="text" class="form-control q-text-en" value="{{ $q['text_en'] ?? '' }}" placeholder="Type question text in English">
              </div>
              @endif
            </div>

            {{-- Prompt --}}
            <div class="row g-2 mb-3">
              @if($showAr)
              <div class="col-md-6">
                <label class="form-label">Prompt (AR)</label>
                <input type="text" class="form-control q-prompt-ar" value="{{ $q['prompt_ar'] ?? '' }}" placeholder="تعليمات الإجابة بالعربية">
              </div>
              @endif

              @if($showEn)
              <div class="col-md-6">
                <label class="form-label">Prompt (EN)</label>
                <input type="text" class="form-control q-prompt-en" value="{{ $q['prompt_en'] ?? '' }}" placeholder="Answer instructions in English">
              </div>
              @endif
            </div>

            {{-- TYPE UI --}}
            @if($type === 'MCQ')
              @php $options = $q['options'] ?? []; @endphp
              <div class="border rounded p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0">MCQ Options</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMcqOption(this)">
                    + إضافة خيار
                  </button>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th style="width:80px;">صحيح؟</th>
                        @if($showAr)<th>الخيار (AR)</th>@endif
                        @if($showEn)<th>Option (EN)</th>@endif
                        <th style="width:90px;">حذف</th>
                      </tr>
                    </thead>
                    <tbody class="mcq-options">
                      @foreach($options as $oi => $opt)
                        <tr class="mcq-row">
                          <td>
                            <input type="radio" class="form-check-input mcq-correct" {{ !empty($opt['is_correct']) ? 'checked' : '' }}>
                          </td>

                          @if($showAr)
                          <td>
                            <input type="text" class="form-control form-control-sm mcq-ar" value="{{ $opt['text_ar'] ?? '' }}">
                          </td>
                          @endif

                          @if($showEn)
                          <td>
                            <input type="text" class="form-control form-control-sm mcq-en" value="{{ $opt['text_en'] ?? '' }}">
                          </td>
                          @endif

                          <td>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">حذف</button>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif

            @if($type === 'TF')
              @php $options = $q['options'] ?? []; @endphp
              <div class="border rounded p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0">True / False</h6>
                  <span class="text-muted small">ثابت: خياران فقط (صحيح / خطأ)</span>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th style="width:80px;">صحيح؟</th>
                        @if($showAr)<th>الخيار (AR)</th>@endif
                        @if($showEn)<th>Option (EN)</th>@endif
                      </tr>
                    </thead>
                    <tbody class="tf-options">
                      @foreach($options as $oi => $opt)
                        <tr class="tf-row">
                          <td>
                            <input type="radio" class="form-check-input tf-correct" {{ !empty($opt['is_correct']) ? 'checked' : '' }}>
                          </td>

                          @if($showAr)
                          <td>
                            <input type="text" class="form-control form-control-sm tf-ar" value="{{ $opt['text_ar'] ?? '' }}" readonly>
                          </td>
                          @endif

                          @if($showEn)
                          <td>
                            <input type="text" class="form-control form-control-sm tf-en" value="{{ $opt['text_en'] ?? '' }}" readonly>
                          </td>
                          @endif
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>

                <div class="text-muted small mt-2">
                  يمكنك فقط تحديد الإجابة الصحيحة (صحيح أو خطأ).
                </div>
              </div>
            @endif

            @if($type === 'ESSAY')
              <div class="border rounded p-3">
                <h6 class="mb-2">Essay</h6>
                <div class="text-muted small">
                  لا توجد خيارات.
                </div>
              </div>
            @endif

            @if($type === 'REORDER')
              @php $items = $q['reorder_items'] ?? []; @endphp
              <div class="border rounded p-3 reorder-box">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0">REORDER Items</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="addReorderItem(this)">
                    + إضافة عنصر
                  </button>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th style="width:120px;">ترتيب</th>
                        @if($showAr)<th>العنصر (AR)</th>@endif
                        @if($showEn)<th>Item (EN)</th>@endif
                        <th style="width:90px;">حذف</th>
                      </tr>
                    </thead>
                    <tbody class="reorder-items">
                      @foreach($items as $ri => $it)
                        <tr class="reorder-row">
                          <td class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveRowUp(this)">▲</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveRowDown(this)">▼</button>
                          </td>

                          @if($showAr)
                          <td><input type="text" class="form-control form-control-sm reorder-ar" value="{{ $it['text_ar'] ?? '' }}"></td>
                          @endif

                          @if($showEn)
                          <td><input type="text" class="form-control form-control-sm reorder-en" value="{{ $it['text_en'] ?? '' }}"></td>
                          @endif

                          <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">حذف</button></td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif

            @if($type === 'CLASSIFICATION')
              @php
                $cls = $q['classification'] ?? [];
                $categories = $cls['categories'] ?? [
                  ['id'=>'A','label_ar'=>'التصنيف (أ)','label_en'=>'Category A'],
                  ['id'=>'B','label_ar'=>'التصنيف (ب)','label_en'=>'Category B'],
                ];
                $items = $cls['items'] ?? [];
              @endphp

              <div class="border rounded p-3 classification-box">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                  <h6 class="mb-0">CLASSIFICATION</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="addClassificationItem(this)">
                    + إضافة عنصر
                  </button>
                </div>

                {{-- Categories --}}
                <div class="row g-2 mb-3">
                  <div class="col-md-6">
                    <div class="card">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                          <strong>Category A</strong>
                          <span class="badge bg-secondary">A</span>
                        </div>
                        <div class="mt-2">
                          @if($showAr)
                            <label class="form-label small">Label (AR)</label>
                            <input type="text" class="form-control form-control-sm cls-cat-ar" data-catid="A"
                                   value="{{ $categories[0]['label_ar'] ?? 'التصنيف (أ)' }}">
                          @endif
                          @if($showEn)
                            <label class="form-label small mt-2">Label (EN)</label>
                            <input type="text" class="form-control form-control-sm cls-cat-en" data-catid="A"
                                   value="{{ $categories[0]['label_en'] ?? 'Category A' }}">
                          @endif
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="card">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                          <strong>Category B</strong>
                          <span class="badge bg-secondary">B</span>
                        </div>
                        <div class="mt-2">
                          @if($showAr)
                            <label class="form-label small">Label (AR)</label>
                            <input type="text" class="form-control form-control-sm cls-cat-ar" data-catid="B"
                                   value="{{ $categories[1]['label_ar'] ?? 'التصنيف (ب)' }}">
                          @endif
                          @if($showEn)
                            <label class="form-label small mt-2">Label (EN)</label>
                            <input type="text" class="form-control form-control-sm cls-cat-en" data-catid="B"
                                   value="{{ $categories[1]['label_en'] ?? 'Category B' }}">
                          @endif
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- Items --}}
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        @if($showAr)<th>العنصر (AR)</th>@endif
                        @if($showEn)<th>Item (EN)</th>@endif
                        <th style="width:220px;">التصنيف الصحيح</th>
                        <th style="width:90px;">حذف</th>
                      </tr>
                    </thead>
                    <tbody class="classification-items">
                      @foreach($items as $ci => $it)
                        <tr class="classification-row">
                          @if($showAr)
                          <td><input type="text" class="form-control form-control-sm cls-item-ar" value="{{ $it['text_ar'] ?? '' }}"></td>
                          @endif

                          @if($showEn)
                          <td><input type="text" class="form-control form-control-sm cls-item-en" value="{{ $it['text_en'] ?? '' }}"></td>
                          @endif

                          <td>
                            <select class="form-select form-select-sm cls-correct">
                              <option value="A" {{ ($it['correct_category'] ?? 'A') === 'A' ? 'selected' : '' }}>A</option>
                              <option value="B" {{ ($it['correct_category'] ?? 'A') === 'B' ? 'selected' : '' }}>B</option>
                            </select>
                          </td>

                          <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">حذف</button></td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>

              </div>
            @endif

          </div>
        </div>
      @endforeach
    </div>

  </form>

</div>

<script>
  const LANG_MODE = @json($langMode ?? 'ar');

  function uuid(){
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'q_' + Math.random().toString(16).slice(2) + '_' + Date.now();
  }

  function removeRow(btn){
    const tr = btn.closest('tr');
    if(tr) tr.remove();
  }

  function moveRowUp(btn){
    const tr = btn.closest('tr');
    if(!tr) return;
    const prev = tr.previousElementSibling;
    if(prev) tr.parentNode.insertBefore(tr, prev);
  }

  function moveRowDown(btn){
    const tr = btn.closest('tr');
    if(!tr) return;
    const next = tr.nextElementSibling;
    if(next) tr.parentNode.insertBefore(next, tr);
  }

  function moveCardUp(btn){
    const card = btn.closest('.question-card');
    if(!card) return;
    const prev = card.previousElementSibling;
    if(prev) card.parentNode.insertBefore(card, prev);
    renumber();
  }

  function moveCardDown(btn){
    const card = btn.closest('.question-card');
    if(!card) return;
    const next = card.nextElementSibling;
    if(next) card.parentNode.insertBefore(next, card);
    renumber();
  }

  function removeQuestion(btn){
    const card = btn.closest('.question-card');
    if(card) card.remove();
    renumber();
    updateSelectedCount();
  }

  function duplicateQuestion(btn){
    const card = btn.closest('.question-card');
    if(!card) return;

    const clone = card.cloneNode(true);
    clone.setAttribute('data-qid', uuid());

    // reset checkbox
    const cb = clone.querySelector('.q-select');
    if(cb) cb.checked = false;

    // Ensure radio groups are independent (we don't use name now, so ok)
    // But we must ensure only one correct radio remains checked within each card:
    // nothing special needed.

    // rebind buttons remain via inline onclick (copied)
    card.parentNode.insertBefore(clone, card.nextElementSibling);

    renumber();
    updateSelectedCount();
  }

  function addMcqOption(btn){
    const card = btn.closest('.question-card');
    if(!card) return;
    const tbody = card.querySelector('.mcq-options');
    if(!tbody) return;

    const showAr = (LANG_MODE === 'ar' || LANG_MODE === 'both');
    const showEn = (LANG_MODE === 'en' || LANG_MODE === 'both');

    const tr = document.createElement('tr');
    tr.className = 'mcq-row';

    tr.innerHTML = `
      <td><input type="radio" class="form-check-input mcq-correct"></td>
      ${showAr ? `<td><input type="text" class="form-control form-control-sm mcq-ar" value=""></td>` : ``}
      ${showEn ? `<td><input type="text" class="form-control form-control-sm mcq-en" value=""></td>` : ``}
      <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">حذف</button></td>
    `;
    tbody.appendChild(tr);
  }

  function addReorderItem(btn){
    const card = btn.closest('.question-card');
    if(!card) return;
    const tbody = card.querySelector('.reorder-items');
    if(!tbody) return;

    const showAr = (LANG_MODE === 'ar' || LANG_MODE === 'both');
    const showEn = (LANG_MODE === 'en' || LANG_MODE === 'both');

    const tr = document.createElement('tr');
    tr.className = 'reorder-row';
    tr.innerHTML = `
      <td class="d-flex gap-1">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveRowUp(this)">▲</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveRowDown(this)">▼</button>
      </td>
      ${showAr ? `<td><input type="text" class="form-control form-control-sm reorder-ar" value=""></td>` : ``}
      ${showEn ? `<td><input type="text" class="form-control form-control-sm reorder-en" value=""></td>` : ``}
      <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">حذف</button></td>
    `;
    tbody.appendChild(tr);
  }

  function addClassificationItem(btn){
    const card = btn.closest('.question-card');
    if(!card) return;
    const tbody = card.querySelector('.classification-items');
    if(!tbody) return;

    const showAr = (LANG_MODE === 'ar' || LANG_MODE === 'both');
    const showEn = (LANG_MODE === 'en' || LANG_MODE === 'both');

    const tr = document.createElement('tr');
    tr.className = 'classification-row';
    tr.innerHTML = `
      ${showAr ? `<td><input type="text" class="form-control form-control-sm cls-item-ar" value=""></td>` : ``}
      ${showEn ? `<td><input type="text" class="form-control form-control-sm cls-item-en" value=""></td>` : ``}
      <td>
        <select class="form-select form-select-sm cls-correct">
          <option value="A" selected>A</option>
          <option value="B">B</option>
        </select>
      </td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">حذف</button></td>
    `;
    tbody.appendChild(tr);
  }

  function renumber(){
    const cards = document.querySelectorAll('#questionsRoot .question-card');
    cards.forEach((c, i) => {
      const num = c.querySelector('.q-number');
      if(num) num.textContent = '#' + (i+1);
    });
  }

  function updateSelectedCount(){
    const cards = document.querySelectorAll('.question-card');
    let n = 0;
    cards.forEach(c => {
      const cb = c.querySelector('.q-select');
      if(cb && cb.checked) n++;
    });
    document.getElementById('selectedCount').textContent = n;
  }

  // Bulk handlers
  document.getElementById('selectAll').addEventListener('change', function(){
    const checked = this.checked;
    document.querySelectorAll('.q-select').forEach(cb => cb.checked = checked);
    updateSelectedCount();
  });

  document.addEventListener('change', function(e){
    if(e.target && e.target.classList.contains('q-select')){
      updateSelectedCount();
    }
  });

  document.getElementById('bulkDeleteBtn').addEventListener('click', function(){
    const cards = Array.from(document.querySelectorAll('.question-card'));
    cards.forEach(c => {
      const cb = c.querySelector('.q-select');
      if(cb && cb.checked) c.remove();
    });
    renumber();
    updateSelectedCount();
  });

  document.getElementById('bulkDifficulty').addEventListener('change', function(){
    const val = this.value;
    if(!val) return;
    document.querySelectorAll('.question-card').forEach(c => {
      const cb = c.querySelector('.q-select');
      if(cb && cb.checked){
        const dd = c.querySelector('.q-difficulty');
        if(dd) dd.value = val;
      }
    });
    // reset dropdown
    this.value = '';
  });

  function rebuildDraftJson(showAlert = false){
    const root = document.getElementById('questionsRoot');
    const cards = root ? root.querySelectorAll('.question-card') : [];
    const questions = [];

    const showAr = (LANG_MODE === 'ar' || LANG_MODE === 'both');
    const showEn = (LANG_MODE === 'en' || LANG_MODE === 'both');

    cards.forEach(card => {
      const type = card.getAttribute('data-qtype') || 'MCQ';
      const q = {
        id: card.getAttribute('data-qid'),
        type,
        difficulty: (card.querySelector('.q-difficulty') ? card.querySelector('.q-difficulty').value : 'medium'),
        text_ar: null, text_en: null,
        prompt_ar: null, prompt_en: null
      };

      if(showAr){
        const el = card.querySelector('.q-text-ar');
        const pl = card.querySelector('.q-prompt-ar');
        q.text_ar = el ? el.value : null;
        q.prompt_ar = pl ? pl.value : null;
      }
      if(showEn){
        const el = card.querySelector('.q-text-en');
        const pl = card.querySelector('.q-prompt-en');
        q.text_en = el ? el.value : null;
        q.prompt_en = pl ? pl.value : null;
      }

      // Enforce AR only / EN only nulling
      if(LANG_MODE === 'ar'){ q.text_en = null; q.prompt_en = null; }
      if(LANG_MODE === 'en'){ q.text_ar = null; q.prompt_ar = null; }

      if(type === 'MCQ'){
        const rows = card.querySelectorAll('.mcq-row');
        const opts = [];
        rows.forEach(r => {
          const isCorrect = !!(r.querySelector('.mcq-correct') && r.querySelector('.mcq-correct').checked);
          const opt = { text_ar: null, text_en: null, is_correct: isCorrect };

          if(showAr){
            const a = r.querySelector('.mcq-ar');
            opt.text_ar = a ? a.value : null;
          }
          if(showEn){
            const e = r.querySelector('.mcq-en');
            opt.text_en = e ? e.value : null;
          }
          if(LANG_MODE === 'ar') opt.text_en = null;
          if(LANG_MODE === 'en') opt.text_ar = null;

          opts.push(opt);
        });

        // guarantee at least one correct
        if(opts.length){
          const anyCorrect = opts.some(o => o.is_correct);
          if(!anyCorrect) opts[0].is_correct = true;
        }

        q.options = opts;
      }

      if(type === 'TF'){
        const rows = card.querySelectorAll('.tf-row');
        const opts = [];
        rows.forEach(r => {
          const isCorrect = !!(r.querySelector('.tf-correct') && r.querySelector('.tf-correct').checked);
          const opt = { text_ar: null, text_en: null, is_correct: isCorrect };

          if(showAr){
            const a = r.querySelector('.tf-ar');
            opt.text_ar = a ? a.value : null;
          }
          if(showEn){
            const e = r.querySelector('.tf-en');
            opt.text_en = e ? e.value : null;
          }
          if(LANG_MODE === 'ar') opt.text_en = null;
          if(LANG_MODE === 'en') opt.text_ar = null;

          opts.push(opt);
        });

        // guarantee one correct
        if(opts.length){
          const anyCorrect = opts.some(o => o.is_correct);
          if(!anyCorrect) opts[0].is_correct = true;
        }

        q.options = opts;
      }

      if(type === 'REORDER'){
        const rows = card.querySelectorAll('.reorder-row');
        const items = [];
        rows.forEach(r => {
          const it = { text_ar: null, text_en: null };
          if(showAr){
            const a = r.querySelector('.reorder-ar');
            it.text_ar = a ? a.value : null;
          }
          if(showEn){
            const e = r.querySelector('.reorder-en');
            it.text_en = e ? e.value : null;
          }
          if(LANG_MODE === 'ar') it.text_en = null;
          if(LANG_MODE === 'en') it.text_ar = null;
          items.push(it);
        });
        q.reorder_items = items;
      }

      if(type === 'CLASSIFICATION'){
        const categories = [
          { id: 'A', label_ar: null, label_en: null },
          { id: 'B', label_ar: null, label_en: null }
        ];

        const catA_ar = card.querySelector('.cls-cat-ar[data-catid="A"]');
        const catB_ar = card.querySelector('.cls-cat-ar[data-catid="B"]');
        const catA_en = card.querySelector('.cls-cat-en[data-catid="A"]');
        const catB_en = card.querySelector('.cls-cat-en[data-catid="B"]');

        if(showAr){
          categories[0].label_ar = catA_ar ? catA_ar.value : null;
          categories[1].label_ar = catB_ar ? catB_ar.value : null;
        }
        if(showEn){
          categories[0].label_en = catA_en ? catA_en.value : null;
          categories[1].label_en = catB_en ? catB_en.value : null;
        }

        if(LANG_MODE === 'ar'){ categories[0].label_en = null; categories[1].label_en = null; }
        if(LANG_MODE === 'en'){ categories[0].label_ar = null; categories[1].label_ar = null; }

        const rows = card.querySelectorAll('.classification-row');
        const items = [];
        rows.forEach(r => {
          const it = { text_ar: null, text_en: null, correct_category: 'A' };
          if(showAr){
            const a = r.querySelector('.cls-item-ar');
            it.text_ar = a ? a.value : null;
          }
          if(showEn){
            const e = r.querySelector('.cls-item-en');
            it.text_en = e ? e.value : null;
          }
          const sel = r.querySelector('.cls-correct');
          it.correct_category = sel ? sel.value : 'A';

          if(LANG_MODE === 'ar') it.text_en = null;
          if(LANG_MODE === 'en') it.text_ar = null;

          items.push(it);
        });

        q.classification = { categories, items };
      }

      questions.push(q);
    });

    const draft = { lang_mode: LANG_MODE, questions };
    document.getElementById('draft_json').value = JSON.stringify(draft);

    if(showAlert){
      alert('تم تحديث draft_json ✅');
    }
  }

  // Always rebuild on submit
  document.getElementById('reviewForm').addEventListener('submit', function(){
    rebuildDraftJson(false);
  });

  // Initial
  renumber();
  updateSelectedCount();
</script>
@endsection
