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
      .req { color: #dc3545; font-weight: 700; }
      .form-hint { color: #6c757d; font-size: .9rem; }
      .admin-form-card .card-header { background: rgba(13,110,253,.06); border-bottom: 1px solid rgba(0,0,0,.06); font-weight: 700; }
      .option-item, .box { border: 1px solid rgba(0,0,0,.08); border-radius: 14px; padding: 14px; background: #fff; }
      .option-title { font-weight: 800; margin: 0; }
      .option-actions .btn { padding: .32rem .55rem; border-radius: 10px; }
      .radio-wrap { display:flex; align-items:center; gap:10px; padding: 10px 12px; border: 1px dashed rgba(0,0,0,.18); border-radius: 12px; background: rgba(25,135,84,.05); }
      .en-field { display: none; }
      .type-box { display:none; }
      .mini-btn { padding: .25rem .45rem; }
    </style>
  @endpush

  @php
    $metaArr = isset($meta) && is_array($meta) ? $meta : (is_array($question->metadata) ? $question->metadata : []);

    // Existing options (DB) OR fallback to metadata.options for AI questions
    $existingOptions = [];
    if (old('options')) {
      $existingOptions = array_values(old('options'));
    } else {
      if ($question->options && $question->options->count()) {
        $existingOptions = $question->options->map(function ($o) {
          return [
            'content_en'  => $o->content_en,
            'content_ar'  => $o->content_ar,
            'is_correct'  => (bool) $o->is_correct,
            'order_index' => $o->order_index,
          ];
        })->values()->all();
      } else {
        $mopts = $metaArr['options'] ?? [];
        if (is_array($mopts)) {
          foreach ($mopts as $o) {
            $existingOptions[] = [
              'content_en' => $o['text_en'] ?? '',
              'content_ar' => $o['text_ar'] ?? '',
              'is_correct' => !empty($o['is_correct']),
              'order_index' => null,
            ];
          }
        }
      }
    }

    $promptEnVal = old('prompt_en', $question->prompt_en);

    $hasEnglishInOptions = false;
    foreach ($existingOptions as $opt) {
      if (!empty($opt['content_en'] ?? '')) { $hasEnglishInOptions = true; break; }
    }

    $showEnglishByDefault = (!empty($promptEnVal) || $hasEnglishInOptions);

    $qtArVal = old('question_text_ar', $metaArr['question_text_ar'] ?? '');
    $qtEnVal = old('question_text_en', $metaArr['question_text_en'] ?? '');

    $reorderItems = old('reorder_items', $metaArr['reorder_items'] ?? []);
    if (!is_array($reorderItems)) $reorderItems = [];

    $classification = $metaArr['classification'] ?? [];
    if (!is_array($classification)) $classification = [];
    $clsCats  = $classification['categories'] ?? [];
    if (!is_array($clsCats)) $clsCats = [];

    $clsCatAAr = old('cls_cat_a_ar', $clsCats[0]['label_ar'] ?? 'التصنيف (أ)');
    $clsCatAEn = old('cls_cat_a_en', $clsCats[0]['label_en'] ?? 'Category A');
    $clsCatBAr = old('cls_cat_b_ar', $clsCats[1]['label_ar'] ?? 'التصنيف (ب)');
    $clsCatBEn = old('cls_cat_b_en', $clsCats[1]['label_en'] ?? 'Category B');

    $clsItems = old('cls_items', $classification['items'] ?? []);
    if (!is_array($clsItems)) $clsItems = [];
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

          <form action="{{ route('admin.questions.update', $question->id) }}" method="POST" id="questionForm" class="needs-validation" novalidate>
            @csrf
            @method('PUT')

            <div class="row g-3">
              <div class="col-12">
                <label for="lesson_id" class="form-label">
                  {{ __('Lesson') }} <span class="req">*</span>
                </label>
                <select id="lesson_id" name="lesson_id" class="form-select @error('lesson_id') is-invalid @enderror" required>
                  <option value="">{{ __('Select Lesson') }}</option>
                  @foreach($lessons as $lesson)
                    <option value="{{ $lesson->id }}" {{ old('lesson_id', $question->lesson_id) == $lesson->id ? 'selected' : '' }}>
                      {{ $lesson->section->material->name_en ?? '' }} — {{ $lesson->section->title_en ?? $lesson->section->title_ar ?? '' }} — {{ $lesson->title_en ?? $lesson->title_ar ?? '' }}
                    </option>
                  @endforeach
                </select>
                @error('lesson_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="type" class="form-label">
                  {{ __('Question Type') }} <span class="req">*</span>
                </label>
                <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required onchange="handleTypeChange(true)">
                  <option value="">{{ __('Select Type') }}</option>
                  @foreach(['MCQ'=>'Multiple Choice (MCQ)','TF'=>'True/False','ESSAY'=>'Essay','CLASSIFICATION'=>'Classification','REORDER'=>'Reorder','FILL_BLANK'=>'Fill in the Blank'] as $k=>$v)
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
                <select id="difficulty" name="difficulty" class="form-select @error('difficulty') is-invalid @enderror" required>
                  <option value="">{{ __('Select Difficulty') }}</option>
                  @foreach(['EASY'=>'Easy','MEDIUM'=>'Medium','HARD'=>'Hard'] as $k=>$v)
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
                <textarea id="prompt_en" name="prompt_en" rows="3" class="form-control @error('prompt_en') is-invalid @enderror">{{ old('prompt_en', $question->prompt_en) }}</textarea>
                @error('prompt_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="prompt_ar" class="form-label">
                  {{ __('Question Prompt (Arabic)') }} <span class="req">*</span>
                </label>
                <textarea id="prompt_ar" name="prompt_ar" rows="3" class="form-control @error('prompt_ar') is-invalid @enderror" required>{{ old('prompt_ar', $question->prompt_ar) }}</textarea>
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

            {{-- REORDER --}}
            <div id="reorderContainer" class="type-box mt-4">
              <div class="box">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h3 class="h6 mb-0">REORDER Items</h3>
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="addReorderRow()">إضافة عنصر +</button>
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
                      @foreach($reorderItems as $i => $it)
                        <tr>
                          <td class="d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary mini-btn" onclick="rowUp(this)">▲</button>
                            <button type="button" class="btn btn-outline-secondary mini-btn" onclick="rowDown(this)">▼</button>
                          </td>
                          <td>
                            <input type="text" class="form-control form-control-sm" name="reorder_items[{{ $i }}][text_ar]" value="{{ $it['text_ar'] ?? '' }}">
                          </td>
                          <td class="en-field">
                            <input type="text" class="form-control form-control-sm" name="reorder_items[{{ $i }}][text_en]" value="{{ $it['text_en'] ?? '' }}">
                          </td>
                          <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTr(this)">حذف</button></td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>

              </div>
            </div>

            {{-- CLASSIFICATION --}}
            <div id="classificationContainer" class="type-box mt-4">
              <div class="box">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h3 class="h6 mb-0">CLASSIFICATION</h3>
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="addClsRow()">إضافة عنصر +</button>
                </div>

                <div class="row g-2 mb-3">
                  <div class="col-md-6">
                    <div class="option-item">
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Category A</strong><span class="badge text-bg-secondary">A</span>
                      </div>
                      <div class="mt-2">
                        <label class="form-label small">Label (AR)</label>
                        <input type="text" class="form-control form-control-sm" name="cls_cat_a_ar" value="{{ $clsCatAAr }}">
                        <div class="en-field mt-2">
                          <label class="form-label small">Label (EN)</label>
                          <input type="text" class="form-control form-control-sm" name="cls_cat_a_en" value="{{ $clsCatAEn }}">
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="option-item">
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Category B</strong><span class="badge text-bg-secondary">B</span>
                      </div>
                      <div class="mt-2">
                        <label class="form-label small">Label (AR)</label>
                        <input type="text" class="form-control form-control-sm" name="cls_cat_b_ar" value="{{ $clsCatBAr }}">
                        <div class="en-field mt-2">
                          <label class="form-label small">Label (EN)</label>
                          <input type="text" class="form-control form-control-sm" name="cls_cat_b_en" value="{{ $clsCatBEn }}">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th>العنصر (AR)</th>
                        <th class="en-field">Item (EN)</th>
                        <th style="width:220px;">التصنيف الصحيح</th>
                        <th style="width:90px;">حذف</th>
                      </tr>
                    </thead>
                    <tbody id="clsBody">
                      @foreach($clsItems as $i => $it)
                        <tr>
                          <td><input type="text" class="form-control form-control-sm" name="cls_items[{{ $i }}][text_ar]" value="{{ $it['text_ar'] ?? '' }}"></td>
                          <td class="en-field"><input type="text" class="form-control form-control-sm" name="cls_items[{{ $i }}][text_en]" value="{{ $it['text_en'] ?? '' }}"></td>
                          <td>
                            <select class="form-select form-select-sm" name="cls_items[{{ $i }}][correct_category]">
                              <option value="A" {{ ($it['correct_category'] ?? 'A') === 'A' ? 'selected' : '' }}>A</option>
                              <option value="B" {{ ($it['correct_category'] ?? 'A') === 'B' ? 'selected' : '' }}>B</option>
                            </select>
                          </td>
                          <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTr(this)">حذف</button></td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
            @php
  $meta = is_array($question->metadata) ? $question->metadata : [];
  $typeVal = old('type', $question->type);

  $classification = old('metadata.classification', $meta['classification'] ?? []);
  $classCats = $classification['categories'] ?? [];
  $classItems = $classification['items'] ?? [];

  $reorderItems = old('metadata.reorder_items', $meta['reorder_items'] ?? ($meta['reorderItems'] ?? []));
@endphp

<div id="typeMetaContainer" class="mt-4">

  {{-- CLASSIFICATION --}}
  <div id="metaClassification" style="display:none;">
    <div class="card admin-card">
      <div class="card-body">
        <h3 class="h6 mb-3">CLASSIFICATION</h3>

        <div class="row g-2">
          <div class="col-12 col-md-6">
            <label class="form-label">Category A (AR)</label>
            <input type="text" class="form-control" name="metadata[classification][categories][A][label_ar]"
              value="{{ $classCats['A']['label_ar'] ?? '' }}" dir="rtl">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Category B (AR)</label>
            <input type="text" class="form-control" name="metadata[classification][categories][B][label_ar]"
              value="{{ $classCats['B']['label_ar'] ?? '' }}" dir="rtl">
          </div>
        </div>

        <hr class="my-3">

        <div class="d-flex justify-content-between align-items-center">
          <strong>Items</strong>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="addClassItem()">+ إضافة عنصر</button>
        </div>

        <div id="classItemsList" class="mt-2 d-grid gap-2"></div>

        <script>
          const classItemsInitial = @json(array_values($classItems ?? []));

          function renderClassItems() {
            const wrap = document.getElementById('classItemsList');
            if (!wrap) return;
            wrap.innerHTML = '';

            classItemsInitial.forEach((it, i) => {
              const text = (it?.text_ar ?? it?.ar ?? it?.text ?? '');
              const corr = (it?.correct ?? it?.correct_category ?? it?.answer ?? '');

              wrap.insertAdjacentHTML('beforeend', `
                <div class="option-item">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Item ${i+1}</strong>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeClassItem(${i})">حذف</button>
                  </div>

                  <div class="row g-2">
                    <div class="col-12 col-md-8">
                      <label class="form-label">النص (AR)</label>
                      <input class="form-control" dir="rtl"
                        name="metadata[classification][items][${i}][text_ar]"
                        value="${escapeHtml(text)}" />
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label">التصنيف الصحيح</label>
                      <select class="form-select"
                        name="metadata[classification][items][${i}][correct]">
                        <option value="">—</option>
                        <option value="A" ${corr==='A'?'selected':''}>A</option>
                        <option value="B" ${corr==='B'?'selected':''}>B</option>
                      </select>
                    </div>
                  </div>
                </div>
              `);
            });
          }

          function addClassItem() {
            classItemsInitial.push({text_ar:'', correct:''});
            renderClassItems();
          }

          function removeClassItem(i) {
            classItemsInitial.splice(i, 1);
            renderClassItems();
          }
        </script>
      </div>
    </div>
  </div>

  {{-- REORDER --}}
  <div id="metaReorder" style="display:none;">
    <div class="card admin-card">
      <div class="card-body">
        <h3 class="h6 mb-3">REORDER Items</h3>

        <div class="d-flex justify-content-between align-items-center">
          <strong>العناصر</strong>
          <button type="button" class="btn btn-outline-primary btn-sm" onclick="addReorderItem()">+ إضافة عنصر</button>
        </div>

        <div id="reorderItemsList" class="mt-2 d-grid gap-2"></div>

        <script>
          const reorderItemsInitial = @json(array_values($reorderItems ?? []));

          function renderReorderItems() {
            const wrap = document.getElementById('reorderItemsList');
            if (!wrap) return;
            wrap.innerHTML = '';

            reorderItemsInitial.forEach((it, i) => {
              const text = (it?.text_ar ?? it?.ar ?? it?.text ?? '');
              wrap.insertAdjacentHTML('beforeend', `
                <div class="option-item">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Step ${i+1}</strong>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeReorderItem(${i})">حذف</button>
                  </div>
                  <input class="form-control" dir="rtl"
                    name="metadata[reorder_items][${i}][text_ar]"
                    value="${escapeHtml(text)}" />
                </div>
              `);
            });
          }

          function addReorderItem() {
            reorderItemsInitial.push({text_ar:''});
            renderReorderItems();
          }

          function removeReorderItem(i) {
            reorderItemsInitial.splice(i, 1);
            renderReorderItems();
          }
        </script>
      </div>
    </div>
  </div>

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

    function showOnly(boxId){
      document.querySelectorAll('.type-box').forEach(b => b.style.display = 'none');
      const el = document.getElementById(boxId);
      if(el) el.style.display = 'block';
      setEnglishVisibility();
    }

    function handleTypeChange(isEditInit = false) {
      const type = document.getElementById('type').value;

      if (type === 'MCQ' || type === 'TF') {
        showOnly('optionsContainer');
        const optionsList = document.getElementById('optionsList');
        optionsList.innerHTML = '';
        optionCount = 0;

        if (type === 'TF') {
          document.getElementById('addOptionBtn').style.display = 'none';

          if (isEditInit && existingOptions.length === 2) {
            existingOptions.forEach((o) => addOption(o.content_en, o.content_ar, !!o.is_correct));
          } else {
            addOption('True', 'صحيح', true);
            addOption('False', 'خطأ', false);
          }
        } else {
          document.getElementById('addOptionBtn').style.display = 'inline-block';

          if (isEditInit && existingOptions.length) {
            existingOptions.forEach((o) => addOption(o.content_en, o.content_ar, !!o.is_correct));
          } else {
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

    document.getElementById('questionForm').addEventListener('submit', function() {
      const type = document.getElementById('type').value;

      if (type === 'MCQ' || type === 'TF') {
        document.querySelectorAll('.is-correct-hidden').forEach(input => input.value = '0');

        const selectedRadio = document.querySelector('input[name="correct_option"]:checked');
        if (selectedRadio) {
          const idx = selectedRadio.value;
          const hiddenInput = document.querySelector(\`input[name="options[\${idx}][is_correct]"]\`);
          if (hiddenInput) hiddenInput.value = '1';
        }
      }
    });

    // REORDER helpers
    function removeTr(btn){ const tr = btn.closest('tr'); if(tr) tr.remove(); }
    function rowUp(btn){ const tr = btn.closest('tr'); if(!tr) return; const prev = tr.previousElementSibling; if(prev) tr.parentNode.insertBefore(tr, prev); }
    function rowDown(btn){ const tr = btn.closest('tr'); if(!tr) return; const next = tr.nextElementSibling; if(next) tr.parentNode.insertBefore(next, tr); }

    function addReorderRow(){
      const body = document.getElementById('reorderBody');
      const idx = body.querySelectorAll('tr').length;
      const showEn = document.getElementById('toggleEnglishFields')?.checked;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="d-flex gap-1">
          <button type="button" class="btn btn-outline-secondary mini-btn" onclick="rowUp(this)">▲</button>
          <button type="button" class="btn btn-outline-secondary mini-btn" onclick="rowDown(this)">▼</button>
        </td>
        <td><input type="text" class="form-control form-control-sm" name="reorder_items[${idx}][text_ar]" value=""></td>
        <td class="en-field"><input type="text" class="form-control form-control-sm" name="reorder_items[${idx}][text_en]" value=""></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTr(this)">حذف</button></td>
      `;
      body.appendChild(tr);
      setEnglishVisibility();
    }

    // CLASSIFICATION helpers
    function addClsRow(){
      const body = document.getElementById('clsBody');
      const idx = body.querySelectorAll('tr').length;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><input type="text" class="form-control form-control-sm" name="cls_items[${idx}][text_ar]" value=""></td>
        <td class="en-field"><input type="text" class="form-control form-control-sm" name="cls_items[${idx}][text_en]" value=""></td>
        <td>
          <select class="form-select form-select-sm" name="cls_items[${idx}][correct_category]">
            <option value="A" selected>A</option>
            <option value="B">B</option>
          </select>
        </td>
        <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTr(this)">حذف</button></td>
      `;
      body.appendChild(tr);
      setEnglishVisibility();
    }

    document.addEventListener('DOMContentLoaded', function() {
      const toggle = document.getElementById('toggleEnglishFields');
      toggle?.addEventListener('change', setEnglishVisibility);
      setEnglishVisibility();

      const type = document.getElementById('type').value;
      if (type) handleTypeChange(true);


    document.addEventListener('DOMContentLoaded', function() {
  // ... كودك الحالي

  // init lists
  if (typeof renderClassItems === 'function') renderClassItems();
  if (typeof renderReorderItems === 'function') renderReorderItems();

  // show/hide meta blocks based on type
  function toggleMetaBlocks() {
    const t = document.getElementById('type')?.value;
    document.getElementById('metaClassification').style.display = (t === 'CLASSIFICATION') ? 'block' : 'none';
    document.getElementById('metaReorder').style.display = (t === 'REORDER') ? 'block' : 'none';
  }

  toggleMetaBlocks();
  document.getElementById('type')?.addEventListener('change', toggleMetaBlocks);
});

    });
  </script>
  @endpush
@endsection
