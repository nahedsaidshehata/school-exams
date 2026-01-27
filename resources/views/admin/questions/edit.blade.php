{{-- resources/views/admin/questions/edit.blade.php --}}
@extends('layouts.admin')

@section('title', __('Edit Question'))
@section('page_title', __('Edit Question'))
@section('page_subtitle')
  {{ __('Update question details and options.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.questions.show', $question->id) }}" class="btn btn-outline-primary btn-sm">
    {{ __('View') }}
  </a>
  <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .req {
        color: #dc3545;
        font-weight: 700;
      }

      .form-hint {
        color: #6c757d;
        font-size: .9rem;
      }

      .admin-form-card .card-header {
        background: rgba(13, 110, 253, .06);
        border-bottom: 1px solid rgba(0, 0, 0, .06);
        font-weight: 700;
      }

      .option-item,
      .box {
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 14px;
        padding: 14px;
        background: #fff;
      }

      .option-title {
        font-weight: 800;
        margin: 0;
      }

      .option-actions .btn {
        padding: .32rem .55rem;
        border-radius: 10px;
      }

      .radio-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border: 1px dashed rgba(0, 0, 0, .18);
        border-radius: 12px;
        background: rgba(25, 135, 84, .05);
      }

      .en-field {
        display: none;
      }

      .type-box {
        display: none;
      }

      .mini-btn {
        padding: .25rem .45rem;
      }
    </style>
  @endpush

  @php
    $metaArr = isset($meta) && is_array($meta) ? $meta : (is_array($question->metadata) ? $question->metadata : []);
    // dump($metaArr);

    // Existing options
    $existingOptions = [];
    if (old('options')) {
      $existingOptions = array_values(old('options'));
    } elseif ($question->options && $question->options->count()) {
      $existingOptions = $question->options->map(function ($o) {
        return [
          'content_en' => $o->content_en,
          'content_ar' => $o->content_ar,
          'is_correct' => (bool) $o->is_correct,
          'order_index' => $o->order_index,
        ];
      })->values()->all();
    }

    $promptEnVal = old('prompt_en', $question->prompt_en);
    $hasEnglishInOptions = collect($existingOptions)->pluck('content_en')->filter()->isNotEmpty();
    $showEnglishByDefault = (!empty($promptEnVal) || $hasEnglishInOptions);

    $qtArVal = old('question_text_ar', $metaArr['question_text_ar'] ?? '');
    $qtEnVal = old('question_text_en', $metaArr['question_text_en'] ?? '');

    // Classification Prep
    $classification = $metaArr['classification'] ?? [];
    $clsCats = $classification['categories'] ?? [];
    // Fallbacks
    $clsCatAAr = $clsCats[0]['label_ar'] ?? 'التصنيف (أ)';
    $clsCatAEn = $clsCats[0]['label_en'] ?? 'Category A';
    $clsCatBAr = $clsCats[1]['label_ar'] ?? 'التصنيف (ب)';
    $clsCatBEn = $clsCats[1]['label_en'] ?? 'Category B';
  @endphp

  <div class="row g-3">
    <div class="col-12 col-xl-10">
      <div class="card admin-card admin-form-card">
        <div class="card-header">{{ __('Question Information') }}</div>

        <div class="card-body">
          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $e)
                  <li>{{ $e }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('admin.questions.update', $question->id) }}" method="POST" id="questionForm"
            class="needs-validation" novalidate>
            @csrf
            @method('PUT')

            <div class="row g-3">
              <div class="col-12">
                <label for="lesson_id" class="form-label">
                  {{ __('Lesson') }} <span class="req">*</span>
                </label>
                <select id="lesson_id" name="lesson_id" class="form-select @error('lesson_id') is-invalid @enderror"
                  required>
                  <option value="">{{ __('Select Lesson') }}</option>
                  @foreach($lessons as $lesson)
                    <option value="{{ $lesson->id }}" {{ old('lesson_id', $question->lesson_id) == $lesson->id ? 'selected' : '' }}>
                      {{ $lesson->section->material->name_en ?? '' }} —
                      {{ $lesson->section->title_en ?? $lesson->section->title_ar ?? '' }} —
                      {{ $lesson->title_en ?? $lesson->title_ar ?? '' }}
                    </option>
                  @endforeach
                </select>
                @error('lesson_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="type" class="form-label">
                  {{ __('Question Type') }} <span class="req">*</span>
                </label>
                <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required
                  onchange="handleTypeChange(true)">
                  <option value="">{{ __('Select Type') }}</option>
                  @foreach(['MCQ' => 'Multiple Choice (MCQ)', 'TF' => 'True/False', 'ESSAY' => 'Essay', 'CLASSIFICATION' => 'Classification', 'REORDER' => 'Reorder', 'FILL_BLANK' => 'Fill in the Blank'] as $k => $v)
                    <option value="{{ $k }}" {{ old('type', $question->type) == $k ? 'selected' : '' }}>
                      {{ __($v) }}
                    </option>
                  @endforeach
                </select>
                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="difficulty" class="form-label">
                  {{ __('Difficulty') }} <span class="req">*</span>
                </label>
                <select id="difficulty" name="difficulty" class="form-select @error('difficulty') is-invalid @enderror"
                  required>
                  <option value="">{{ __('Select Difficulty') }}</option>
                  @foreach(['EASY' => 'Easy', 'MEDIUM' => 'Medium', 'HARD' => 'Hard'] as $k => $v)
                    <option value="{{ $k }}" {{ old('difficulty', strtoupper($question->difficulty)) == $k ? 'selected' : '' }}>
                      {{ __($v) }}
                    </option>
                  @endforeach
                </select>
                @error('difficulty') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12">
                <div class="d-flex align-items-center gap-2">
                  <input type="checkbox" class="form-check-input m-0" id="toggleEnglishFields" {{ $showEnglishByDefault ? 'checked' : '' }}>
                  <label for="toggleEnglishFields" class="form-check-label">
                    {{ __('Show English fields (optional)') }}
                  </label>
                  <span class="text-muted small">— {{ __('Keep disabled for Arabic-only workflow') }}</span>
                </div>
              </div>

              {{-- Question Text (stored in metadata) --}}
              <div class="col-12 col-md-6 en-field">
                <label class="form-label">Question Text (EN)</label>
                <textarea name="question_text_en" rows="3" class="form-control">{{ $qtEnVal }}</textarea>
                <div class="form-hint">For AI questions, question text is stored in metadata.</div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">نص السؤال (AR)</label>
                <textarea name="question_text_ar" rows="3" class="form-control">{{ $qtArVal }}</textarea>
                <div class="form-hint">For AI questions, question text is stored in metadata.</div>
              </div>

              {{-- Prompt --}}
              <div class="col-12 col-md-6 en-field">
                <label for="prompt_en" class="form-label">
                  {{ __('Question Prompt (English)') }} <span class="text-muted small">({{ __('optional') }})</span>
                </label>
                <textarea id="prompt_en" name="prompt_en" rows="3"
                  class="form-control @error('prompt_en') is-invalid @enderror">{{ old('prompt_en', $question->prompt_en) }}</textarea>
                @error('prompt_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="prompt_ar" class="form-label">
                  {{ __('Question Prompt (Arabic)') }} <span class="req">*</span>
                </label>
                <textarea id="prompt_ar" name="prompt_ar" rows="3"
                  class="form-control @error('prompt_ar') is-invalid @enderror"
                  required>{{ old('prompt_ar', $question->prompt_ar) }}</textarea>
                @error('prompt_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            {{-- MCQ/TF options box (existing) --}}
            <div id="optionsContainer" class="type-box mt-4">
              <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                <div>
                  <h3 class="h6 mb-1">{{ __('Answer Options') }}</h3>
                  <div class="form-hint">
                    {{ __('Add 2-6 options for MCQ or 2 options for True/False. Mark exactly ONE as correct.') }}
                  </div>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addOption()" id="addOptionBtn">
                  {{ __('Add Option') }}
                </button>
              </div>

              <div id="optionsList" class="d-grid gap-2"></div>
            </div>

            {{-- REORDER (Updated to use metadata) --}}
            <div id="reorderContainer" class="type-box mt-4">
              <div class="box">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h3 class="h6 mb-0">REORDER Items</h3>
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="addReorderRow()">إضافة عنصر
                    +</button>
                </div>
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th style="width:120px;">ترتيب</th>
                        <th>العنصر (AR)</th>
                        <th class="en-field">Item (EN)</th>
                        <th style="width:90px;">حذف</th>
                      </tr>
                    </thead>
                    <tbody id="reorderBody">
                      @php
                        $reorderItems = $metaArr['reorder_items'] ?? $metaArr['reorderItems'] ?? [];
                      @endphp
                      @foreach($reorderItems as $i => $it)
                        <tr>
                          <td class="d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary mini-btn"
                              onclick="rowUp(this)">▲</button>
                            <button type="button" class="btn btn-outline-secondary mini-btn"
                              onclick="rowDown(this)">▼</button>
                          </td>
                          <td>
                            <input type="text" class="form-control form-control-sm"
                              name="metadata[reorder_items][{{ $i }}][text_ar]"
                              value="{{ $it['text_ar'] ?? $it['ar'] ?? '' }}">
                          </td>
                          <td class="en-field">
                            <input type="text" class="form-control form-control-sm"
                              name="metadata[reorder_items][{{ $i }}][text_en]"
                              value="{{ $it['text_en'] ?? $it['en'] ?? '' }}">
                          </td>
                          <td><button type="button" class="btn btn-outline-danger btn-sm"
                              onclick="removeTr(this)">حذف</button></td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            {{-- CLASSIFICATION --}}
            <div id="classificationContainer" class="type-box mt-4">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h3 class="h6 mb-0">{{ __('Classification Groups') }}</h3>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addClassificationItem()">
                  {{ __('Add Item') }}
                </button>
              </div>

              {{-- Categories --}}
              <div class="row g-3 mb-4">
                @php
                  $clsCats = $metaArr['classification']['categories'] ?? [];
                  // Ensure we have 2 cats
                  if (count($clsCats) < 2) {
                    $clsCats = [
                      ['id' => 'A', 'label_ar' => 'التصنيف (أ)', 'label_en' => 'Category A'],
                      ['id' => 'B', 'label_ar' => 'التصنيف (ب)', 'label_en' => 'Category B']
                    ];
                  }
                  // Normalize if keyed by letter instead of index
                  $catA = null;
                  $catB = null;
                  foreach ($clsCats as $c) {
                    if (($c['id'] ?? '') == 'A')
                      $catA = $c;
                    if (($c['id'] ?? '') == 'B')
                      $catB = $c;
                  }
                  if (!$catA && isset($clsCats[0]))
                    $catA = $clsCats[0];
                  if (!$catB && isset($clsCats[1]))
                    $catB = $clsCats[1];
                @endphp

                {{-- Category A --}}
                <div class="col-12 col-md-6">
                  <div class="card h-100">
                    <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                      <span class="fw-bold">{{ __('Category A') }}</span>
                      <span class="badge bg-secondary">A</span>
                    </div>
                    <div class="card-body">
                      <input type="hidden" name="metadata[classification][categories][0][id]" value="A">
                      <div class="mb-2">
                        <label class="form-label small text-muted">{{ __('Label (English)') }}</label>
                        <input type="text" class="form-control form-control-sm"
                          name="metadata[classification][categories][0][label_en]"
                          value="{{ $catA['label_en'] ?? 'Category A' }}" required>
                      </div>
                      <div>
                        <label class="form-label small text-muted">{{ __('Label (Arabic)') }}</label>
                        <input type="text" class="form-control form-control-sm"
                          name="metadata[classification][categories][0][label_ar]"
                          value="{{ $catA['label_ar'] ?? 'التصنيف (أ)' }}" required>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- Category B --}}
                <div class="col-12 col-md-6">
                  <div class="card h-100">
                    <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                      <span class="fw-bold">{{ __('Category B') }}</span>
                      <span class="badge bg-secondary">B</span>
                    </div>
                    <div class="card-body">
                      <input type="hidden" name="metadata[classification][categories][1][id]" value="B">
                      <div class="mb-2">
                        <label class="form-label small text-muted">{{ __('Label (English)') }}</label>
                        <input type="text" class="form-control form-control-sm"
                          name="metadata[classification][categories][1][label_en]"
                          value="{{ $catB['label_en'] ?? 'Category B' }}" required>
                      </div>
                      <div>
                        <label class="form-label small text-muted">{{ __('Label (Arabic)') }}</label>
                        <input type="text" class="form-control form-control-sm"
                          name="metadata[classification][categories][1][label_ar]"
                          value="{{ $catB['label_ar'] ?? 'التصنيف (ب)' }}" required>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <h6 class="mb-2">{{ __('Items to Classify') }}</h6>
              <div class="table-responsive border rounded">
                <table class="table table-sm align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th>{{ __('Item (EN)') }}</th>
                      <th>{{ __('Item (AR)') }}</th>
                      <th style="width: 150px;">{{ __('Correct Category') }}</th>
                      <th style="width: 80px;"></th>
                    </tr>
                  </thead>
                  <tbody id="classificationItemsList">
                    @php
                      $cItems = $metaArr['classification']['items'] ?? [];
                    @endphp
                    @foreach($cItems as $idx => $item)
                      <tr>
                        <td>
                          <input type="text" class="form-control form-control-sm"
                            name="metadata[classification][items][{{ $idx }}][text_en]" value="{{ $item['text_en'] ?? '' }}"
                            required placeholder="Item text EN">
                        </td>
                        <td>
                          <input type="text" class="form-control form-control-sm"
                            name="metadata[classification][items][{{ $idx }}][text_ar]" value="{{ $item['text_ar'] ?? '' }}"
                            required placeholder="Item text AR">
                        </td>
                        <td>
                          <select class="form-select form-select-sm"
                            name="metadata[classification][items][{{ $idx }}][correct_category]">
                            <option value="A" {{ ($item['correct_category'] ?? '') === 'A' ? 'selected' : '' }}>Category A
                            </option>
                            <option value="B" {{ ($item['correct_category'] ?? '') === 'B' ? 'selected' : '' }}>Category B
                            </option>
                          </select>
                        </td>
                        <td class="text-end">
                          <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                            {{ __('Remove') }}
                          </button>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
            {{-- Legacy blocks (Removed) --}}

        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
          <button type="submit" class="btn btn-success">{{ __('Save Changes') }}</button>
          <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
        </form>

      </div>
    </div>
  </div>
  </div>

  @push('scripts')
    <script>
      let optionCount = 0;
      const existingOptions = @json($existingOptions);

      function escapeHtml(str) {
        return String(str ?? '')
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');
      }

      function setEnglishVisibility() {
        const toggle = document.getElementById('toggleEnglishFields');
        const show = !!toggle?.checked;

        document.querySelectorAll('.en-field').forEach(el => el.style.display = show ? '' : 'none');
        document.querySelectorAll('.en-option-field').forEach(el => el.style.display = show ? '' : 'none');
      }

      function showOnly(boxId) {
        document.querySelectorAll('.type-box').forEach(b => b.style.display = 'none');
        const el = document.getElementById(boxId);
        if (el) el.style.display = 'block';
        setEnglishVisibility();
      }

      function handleTypeChange(isEditInit = false) {
        const type = document.getElementById('type').value;

        if (type === 'MCQ' || type === 'TF') {
          showOnly('optionsContainer');
          const optionsList = document.getElementById('optionsList');
          // Only clear if switching away from MCQ/TF or if it's a fresh init (but we handle init differently below)
          // Actually, for edit init, we want to keep them if they exist.
          if (isEditInit === false) {
            optionsList.innerHTML = '';
            optionCount = 0;
          }

          if (type === 'TF') {
            const btn = document.getElementById('addOptionBtn');
            if (btn) btn.style.display = 'none';

            if (isEditInit && existingOptions.length === 2) {
              // Render existing TF options if any
              // (We normally rely on PHP loop for existing, but here we use JS for MCQ/TF)
              optionsList.innerHTML = '';
              optionCount = 0;
              existingOptions.forEach((o) => addOption(o.content_en, o.content_ar, !!o.is_correct));
            } elseif(!isEditInit) {
              addOption('True', 'صحيح', true);
              addOption('False', 'خطأ', false);
            }
          } else {
            // MCQ
            const btn = document.getElementById('addOptionBtn');
            if (btn) btn.style.display = 'inline-block';

            if (isEditInit && existingOptions.length) {
              optionsList.innerHTML = '';
              optionCount = 0;
              existingOptions.forEach((o) => addOption(o.content_en, o.content_ar, !!o.is_correct));
            } elseif(!isEditInit) {
              addOption();
              addOption();
            }
          }
        } else if (type === 'REORDER') {
          showOnly('reorderContainer');
        } else if (type === 'CLASSIFICATION') {
          showOnly('classificationContainer');
        } else {
          document.querySelectorAll('.type-box').forEach(b => b.style.display = 'none');
        }
      }

      function addOption(defaultEn = '', defaultAr = '', isCorrect = false) {
        const type = document.getElementById('type').value;

        if (type === 'MCQ' && optionCount >= 6) {
          alert('Maximum 6 options allowed for MCQ');
          return;
        }
        if (type === 'TF' && optionCount >= 2) return;

        optionCount++;

        const showCorrectRadio = (type === 'MCQ' || type === 'TF');

        const optionHtml = `
                <div class="option-item">
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <h4 class="option-title">Option ${optionCount}</h4>
                    <div class="option-actions">
                      ${(type === 'MCQ')
            ? `<button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOption(this)">Remove</button>`
            : ``}
                    </div>
                  </div>

                  <div class="row g-2">
                    <div class="col-12 col-md-6 en-option-field">
                      <label class="form-label">Content (English) <span class="text-muted small">(optional)</span></label>
                      <input type="text" class="form-control" name="options[${optionCount}][content_en]" value="${escapeHtml(defaultEn)}">
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label">Content (Arabic) <span class="req">*</span></label>
                      <input type="text" class="form-control" name="options[${optionCount}][content_ar]" value="${escapeHtml(defaultAr)}" required>
                    </div>

                    ${showCorrectRadio ? `
                    <div class="col-12">
                      <div class="radio-wrap">
                        <input class="form-check-input m-0 correct-radio" type="radio" name="correct_option" value="${optionCount}" ${isCorrect ? 'checked' : ''} required>
                        <span>This is the correct answer</span>
                      </div>
                    </div>
                    ` : ''}
                  </div>

                  <input type="hidden" name="options[${optionCount}][is_correct]" value="${isCorrect ? '1' : '0'}" class="is-correct-hidden">
                  <input type="hidden" name="options[${optionCount}][order_index]" value="${optionCount}">
                </div>
              `;

        document.getElementById('optionsList').insertAdjacentHTML('beforeend', optionHtml);
        setEnglishVisibility();
      }

      function removeOption(btn) {
        const type = document.getElementById('type').value;
        if ((type === 'MCQ') && optionCount <= 2) {
          alert('Minimum 2 options required');
          return;
        }
        const item = btn.closest('.option-item');
        if (item) item.remove();
        optionCount--;
      }

      document.getElementById('questionForm').addEventListener('submit', function () {
        const type = document.getElementById('type').value;

        if (type === 'MCQ' || type === 'TF') {
          document.querySelectorAll('.is-correct-hidden').forEach(input => input.value = '0');

          const selectedRadio = document.querySelector('input[name="correct_option"]:checked');
          if (selectedRadio) {
            const idx = selectedRadio.value;
            const hiddenInput = document.querySelector(`input[name="options[${idx}][is_correct]"]`);
            if (hiddenInput) hiddenInput.value = '1';
          }
        }
      });

      // REORDER helpers
      function removeTr(btn) { const tr = btn.closest('tr'); if (tr) tr.remove(); }
      function rowUp(btn) { const tr = btn.closest('tr'); if (!tr) return; const prev = tr.previousElementSibling; if (prev) tr.parentNode.insertBefore(tr, prev); }
      function rowDown(btn) { const tr = btn.closest('tr'); if (!tr) return; const next = tr.nextElementSibling; if (next) tr.parentNode.insertBefore(next, tr); }

      function addReorderRow() {
        const body = document.getElementById('reorderBody');
        const idx = body.querySelectorAll('tr').length;

        const tr = document.createElement('tr');
        tr.innerHTML = `
                  <td class="d-flex gap-1">
                  <button type="button" class="btn btn-outline-secondary mini-btn" onclick="rowUp(this)">▲</button>
                  <button type="button" class="btn btn-outline-secondary mini-btn" onclick="rowDown(this)">▼</button>
                </td>
                <td><input type="text" class="form-control form-control-sm" name="metadata[reorder_items][${idx}][text_ar]" value=""></td>
                <td class="en-field"><input type="text" class="form-control form-control-sm" name="metadata[reorder_items][${idx}][text_en]" value=""></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTr(this)">حذف</button></td>
                `;
        body.appendChild(tr);
        setEnglishVisibility();
      }

      // CLASSIFICATION helpers
      let clsItemCount = {{ count($classification['items'] ?? []) }};
      function addClassificationItem(defEn = '', defAr = '', defCat = 'A') {
        const tbody = document.getElementById('classificationItemsList');
        if (!tbody) return;

        const tr = document.createElement('tr');
        const index = clsItemCount++; // Use strictly increasing counter to avoid index collision

        tr.innerHTML = `
                <td>
                  <input type="text" class="form-control form-control-sm" name="metadata[classification][items][${index}][text_en]" value="${escapeHtml(defEn)}" required placeholder="Item text EN">
                </td>
                <td>
                  <input type="text" class="form-control form-control-sm" name="metadata[classification][items][${index}][text_ar]" value="${escapeHtml(defAr)}" required placeholder="Item text AR">
                </td>
                <td>
                  <select class="form-select form-select-sm" name="metadata[classification][items][${index}][correct_category]">
                    <option value="A" ${defCat === 'A' ? 'selected' : ''}>Category A</option>
                    <option value="B" ${defCat === 'B' ? 'selected' : ''}>Category B</option>
                  </select>
                </td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                    {{ __('Remove') }}
                  </button>
                </td>
              `;
        tbody.appendChild(tr);
      }

      document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('toggleEnglishFields');
        toggle?.addEventListener('change', setEnglishVisibility);
        setEnglishVisibility();

        const type = document.getElementById('type').value;
        // On init, pass true to avoid clearing existing data
        if (type) handleTypeChange(true);
        
        // Force check for classification visibility
        if(type === 'CLASSIFICATION') {
            document.getElementById('classificationContainer').style.display = 'block';
        }
      });
    </script>
  @endpush
@endsection