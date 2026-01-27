{{-- resources/views/admin/questions/create.blade.php --}}
@extends('layouts.admin')

@section('title', __('Create Question'))
@section('page_title', __('Create New Question'))
@section('page_subtitle')
  {{ __('Add a new question to the central question bank.') }}
@endsection

@section('page_actions')
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

      .option-item {
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
    </style>
  @endpush

  <div class="row g-3">
    <div class="col-12 col-xl-10">
      <div class="card admin-card admin-form-card">
        <div class="card-header">
          {{ __('Question Information') }}
        </div>
        <div class="card-body">
          <form action="{{ route('admin.questions.store') }}" method="POST" id="questionForm" class="needs-validation"
            novalidate>
            @csrf

            <div class="row g-3">
              <div class="col-12">
                <label for="lesson_id" class="form-label">
                  {{ __('Lesson') }} <span class="req">*</span>
                </label>
                <select id="lesson_id" name="lesson_id" class="form-select @error('lesson_id') is-invalid @enderror"
                  required>
                  <option value="">{{ __('Select Lesson') }}</option>
                  @foreach($lessons as $lesson)
                    <option value="{{ $lesson->id }}" {{ old('lesson_id') == $lesson->id ? 'selected' : '' }}>
                      {{ $lesson->section->material->name_en }} — {{ $lesson->section->title_en }} — {{ $lesson->title_en }}
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
                  onchange="handleTypeChange()">
                  <option value="">{{ __('Select Type') }}</option>
                  <option value="MCQ" {{ old('type') == 'MCQ' ? 'selected' : '' }}>{{ __('Multiple Choice (MCQ)') }}
                  </option>
                  <option value="TF" {{ old('type') == 'TF' ? 'selected' : '' }}>{{ __('True/False') }}</option>
                  <option value="ESSAY" {{ old('type') == 'ESSAY' ? 'selected' : '' }}>{{ __('Essay') }}</option>
                  <option value="CLASSIFICATION" {{ old('type') == 'CLASSIFICATION' ? 'selected' : '' }}>
                    {{ __('Classification') }}
                  </option>
                  <option value="REORDER" {{ old('type') == 'REORDER' ? 'selected' : '' }}>{{ __('Reorder') }}</option>
                  <option value="FILL_BLANK" {{ old('type') == 'FILL_BLANK' ? 'selected' : '' }}>
                    {{ __('Fill in the Blank') }}
                  </option>
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
                  <option value="EASY" {{ old('difficulty') == 'EASY' ? 'selected' : '' }}>{{ __('Easy') }}</option>
                  <option value="MEDIUM" {{ old('difficulty') == 'MEDIUM' ? 'selected' : '' }}>{{ __('Medium') }}</option>
                  <option value="HARD" {{ old('difficulty') == 'HARD' ? 'selected' : '' }}>{{ __('Hard') }}</option>
                </select>
                @error('difficulty') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="prompt_en" class="form-label">
                  {{ __('Question Prompt (English)') }} <span class="req">*</span>
                </label>
                <textarea id="prompt_en" name="prompt_en" rows="4"
                  class="form-control @error('prompt_en') is-invalid @enderror" required>{{ old('prompt_en') }}</textarea>
                @error('prompt_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12 col-md-6">
                <label for="prompt_ar" class="form-label">
                  {{ __('Question Prompt (Arabic)') }} <span class="req">*</span>
                </label>
                <textarea id="prompt_ar" name="prompt_ar" rows="4"
                  class="form-control @error('prompt_ar') is-invalid @enderror" required>{{ old('prompt_ar') }}</textarea>
                @error('prompt_ar') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>

            {{-- Options --}}
            <div id="optionsContainer" class="mt-4" style="display: none;">
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

            {{-- Classification Container --}}
            <div id="classificationContainer" class="mt-4" style="display: none;">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h3 class="h6 mb-0">{{ __('Classification Groups') }}</h3>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addClassificationItem()">
                  {{ __('Add Item') }}
                </button>
              </div>

              {{-- Categories --}}
              <div class="row g-3 mb-4">
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
                          value="{{ old('metadata.classification.categories.0.label_en', 'Category A') }}" required>
                      </div>
                      <div>
                        <label class="form-label small text-muted">{{ __('Label (Arabic)') }}</label>
                        <input type="text" class="form-control form-control-sm"
                          name="metadata[classification][categories][0][label_ar]"
                          value="{{ old('metadata.classification.categories.0.label_ar', 'التصنيف (أ)') }}" required>
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
                          value="{{ old('metadata.classification.categories.1.label_en', 'Category B') }}" required>
                      </div>
                      <div>
                        <label class="form-label small text-muted">{{ __('Label (Arabic)') }}</label>
                        <input type="text" class="form-control form-control-sm"
                          name="metadata[classification][categories][1][label_ar]"
                          value="{{ old('metadata.classification.categories.1.label_ar', 'التصنيف (ب)') }}" required>
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
                  <tbody id="classificationItemsList"></tbody>
                </table>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <button type="submit" class="btn btn-success">
                {{ __('Create Question') }}
              </button>
              <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary">
                {{ __('Cancel') }}
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      let optionCount = 0;
      let clsItemCount = 0;

      function handleTypeChange() {
        const type = document.getElementById('type').value;
        const container = document.getElementById('optionsContainer');
        const optionsList = document.getElementById('optionsList');

        if (type === 'MCQ' || type === 'TF' || type === 'REORDER') {
          container.style.display = 'block';
          document.getElementById('classificationContainer').style.display = 'none';

          optionsList.innerHTML = '';
          optionCount = 0;

          if (type === 'TF') {
            addOption('True', 'صحيح');
            addOption('False', 'خطأ');
            document.getElementById('addOptionBtn').style.display = 'none';
          } else if (type === 'REORDER') {
            document.getElementById('addOptionBtn').style.display = 'inline-block';
            addOption();
            addOption();
          } else {
            document.getElementById('addOptionBtn').style.display = 'inline-block';
            addOption();
            addOption();
          }
        } else if (type === 'CLASSIFICATION') {
          container.style.display = 'none';
          document.getElementById('classificationContainer').style.display = 'block';

          optionsList.innerHTML = '';
          optionCount = 0;

          const clsList = document.getElementById('classificationItemsList');
          if (clsList.children.length === 0) {
            addClassificationItem('Item 1', 'عنصر 1');
            addClassificationItem('Item 2', 'عنصر 2');
          }

        } else {
          container.style.display = 'none';
          document.getElementById('classificationContainer').style.display = 'none';
          optionsList.innerHTML = '';
          optionCount = 0;
        }
      }

      function addOption(defaultEn = '', defaultAr = '') {
        const type = document.getElementById('type').value;
        if (type === 'MCQ' && optionCount >= 6) {
          alert('Maximum 6 options allowed for MCQ');
          return;
        }
        if (type === 'TF' && optionCount >= 2) {
          return;
        }

        optionCount++;

        const showCorrectRadio = (type === 'MCQ' || type === 'TF');

        const optionHtml = `
                <div class="option-item">
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <h4 class="option-title">Option ${optionCount}</h4>
                    <div class="option-actions">
                      ${type === 'MCQ' || type === 'REORDER'
            ? `<button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOption(this)">Remove</button>`
            : ``}
                    </div>
                  </div>

                  <div class="row g-2">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Content (English) <span class="req">*</span></label>
                      <input type="text" class="form-control" name="options[${optionCount}][content_en]" value="${escapeHtml(defaultEn)}" required>
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label">Content (Arabic) <span class="req">*</span></label>
                      <input type="text" class="form-control" name="options[${optionCount}][content_ar]" value="${escapeHtml(defaultAr)}" required>
                    </div>

                    ${showCorrectRadio ? `
                    <div class="col-12">
                      <div class="radio-wrap">
                        <input class="form-check-input m-0" type="radio" name="correct_option" value="${optionCount}" required>
                        <span>This is the correct answer</span>
                      </div>
                    </div>
                    ` : ''}
                  </div>

                  <input type="hidden" name="options[${optionCount}][is_correct]" value="0" class="is-correct-hidden">
                  <input type="hidden" name="options[${optionCount}][order_index]" value="${optionCount}">
                </div>
              `;

        document.getElementById('optionsList').insertAdjacentHTML('beforeend', optionHtml);
      }

      function removeOption(btn) {
        if (optionCount <= 2) {
          alert('Minimum 2 options required');
          return;
        }
        const item = btn.closest('.option-item');
        if (item) item.remove();
        optionCount--;
      }

      function addClassificationItem(defEn = '', defAr = '', defCat = 'A') {
        const tbody = document.getElementById('classificationItemsList');
        const tr = document.createElement('tr');

        const index = clsItemCount++;

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

      function escapeHtml(str) {
        return String(str ?? '')
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');
      }

      document.getElementById('questionForm').addEventListener('submit', function (e) {
        const type = document.getElementById('type').value;

        if (type === 'MCQ' || type === 'TF') {
          const selectedRadio = document.querySelector('input[name="correct_option"]:checked');

          document.querySelectorAll('.is-correct-hidden').forEach(function (input) {
            input.value = '0';
          });

          if (selectedRadio) {
            const selectedIndex = selectedRadio.value;
            const hiddenInput = document.querySelector(`input[name="options[${selectedIndex}][is_correct]"]`);
            if (hiddenInput) {
              hiddenInput.value = '1';
            }
          }
        }
      });

      document.addEventListener('DOMContentLoaded', function () {
        const type = document.getElementById('type').value;
        if (type) {
          handleTypeChange();
        }
      });
    </script>
  @endpush
@endsection