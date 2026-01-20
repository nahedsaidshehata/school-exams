{{-- resources/views/admin/questions/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Questions Management'))
@section('page_title', __('Questions'))
@section('page_subtitle')
  {{ __('Manage the central question bank (global).') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.questions.create') }}" class="btn btn-success btn-sm">
    {{ __('Create Question') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .table td, .table th { vertical-align: middle; }
      .truncate {
        max-width: 360px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .badge-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .55rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
        border: 1px solid rgba(0,0,0,.08);
        background: rgba(13,110,253,.08);
        color: #0d6efd;
      }
      .badge-soft { background: rgba(25,135,84,.10); color: #198754; }
      .badge-warn { background: rgba(255,193,7,.18); color: #8a6d00; }
      .badge-danger { background: rgba(220,53,69,.12); color: #dc3545; }

      .filter-card {
        border: 1px solid rgba(0,0,0,.06);
        border-radius: 16px;
        padding: 14px;
        background: #fff;
      }
      .filter-actions .btn { border-radius: 12px; }
      .small-help { font-size: .86rem; color: #6c757d; }
      .action-btns .btn { padding: .28rem .5rem; border-radius: 10px; }
    </style>
  @endpush

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card admin-card">
    <div class="card-body">

      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="text-muted small">
          {{ __('Total:') }}
          <span class="fw-semibold">{{ method_exists($questions, 'total') ? $questions->total() : '' }}</span>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
          {{ __('Back to Dashboard') }}
        </a>
      </div>

      {{-- Filters --}}
      <form method="GET" action="{{ route('admin.questions.index') }}" class="filter-card mb-3" id="filtersForm">
        <div class="row g-2 align-items-end">

          <div class="col-12 col-lg-4">
            <label class="form-label">{{ __('Search') }}</label>
            <input type="text" class="form-control" name="q"
                   value="{{ request('q') }}"
                   placeholder="{{ __('Search prompt, lesson, metadata...') }}">
            <div class="small-help mt-1">{{ __('Search in EN/AR prompt, lesson title, metadata.') }}</div>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label">{{ __('Type') }}</label>
            <select class="form-select" name="type" id="typeSelect">
              <option value="">{{ __('All') }}</option>
              @foreach(['MCQ','TF','ESSAY','CLASSIFICATION','REORDER','FILL_BLANK'] as $t)
                <option value="{{ $t }}" {{ request('type')===$t ? 'selected' : '' }}>{{ $t }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label">{{ __('Difficulty') }}</label>
            <select class="form-select" name="difficulty" id="difficultySelect">
              <option value="">{{ __('All') }}</option>
              @foreach(['EASY','MEDIUM','HARD'] as $d)
                <option value="{{ $d }}" {{ strtoupper((string)request('difficulty'))===$d ? 'selected' : '' }}>
                  {{ ucfirst(strtolower($d)) }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Subject (Material in DB) --}}
          <div class="col-6 col-lg-2">
            <label class="form-label">{{ __('Subject') }}</label>
            <select class="form-select" name="material_id" id="subjectSelect">
              <option value="">{{ __('All') }}</option>
              @foreach(($subjects ?? []) as $subj)
                @php
                  $label = $subj->name_en ?? $subj->name_ar ?? 'Subject';
                @endphp
                <option value="{{ $subj->id }}" {{ request('material_id')===$subj->id ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-6 col-lg-2">
            <label class="form-label">{{ __('Section') }}</label>
            <select class="form-select" name="section_id" id="sectionSelect">
              <option value="">{{ __('All') }}</option>
              @foreach(($sections ?? []) as $sec)
                @php
                  // ✅ sections table uses title_en/title_ar
                  $label = $sec->title_en ?? $sec->title_ar ?? 'Section';
                @endphp
                <option value="{{ $sec->id }}" {{ request('section_id')===$sec->id ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">{{ __('Lesson') }}</label>
            <select class="form-select" name="lesson_id" id="lessonSelect">
              <option value="">{{ __('All') }}</option>
              @foreach(($lessons ?? []) as $les)
                @php
                  $label = $les->title_en ?? $les->title_ar ?? 'Lesson';
                @endphp
                <option value="{{ $les->id }}" {{ request('lesson_id')===$les->id ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-lg-8 filter-actions d-flex gap-2 justify-content-end">
            <button type="submit" class="btn btn-primary">
              {{ __('Apply Filters') }}
            </button>
            <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary">
              {{ __('Reset') }}
            </a>
          </div>
        </div>
      </form>

      {{-- Table --}}
      <div class="table-responsive">
        <table class="table admin-table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th class="text-nowrap">{{ __('Type') }}</th>
              <th class="text-nowrap">{{ __('Difficulty') }}</th>
              <th>{{ __('Prompt (EN)') }}</th>
              <th>{{ __('Lesson') }}</th>
              <th class="text-nowrap">{{ __('Options') }}</th>
              <th class="text-nowrap">{{ __('Created') }}</th>
              <th class="text-nowrap text-end">{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($questions as $question)
              <tr>
                <td class="text-nowrap">
                  <span class="badge-pill">{{ $question->type }}</span>
                </td>

                <td class="text-nowrap">
                  @php
                    $diff = strtoupper($question->difficulty ?? '');
                    $diffClass = $diff === 'EASY' ? 'badge-soft' : ($diff === 'HARD' ? 'badge-danger' : 'badge-warn');
                  @endphp
                  <span class="badge-pill {{ $diffClass }}">{{ $question->difficulty }}</span>
                </td>

                <td class="truncate" title="{{ $question->prompt_en }}">
                  {{ \Illuminate\Support\Str::limit($question->prompt_en, 70) }}
                </td>

                <td class="truncate" title="{{ $question->lesson->title_en ?? '' }}">
                  {{ $question->lesson->title_en ?? $question->lesson->title_ar ?? 'N/A' }}
                </td>

                <td class="text-nowrap">
                  {{-- Options count (will be 0 if none were stored in question_options) --}}
                  <span class="badge text-bg-primary">{{ $question->options_count ?? 0 }}</span>
                </td>

                <td class="text-nowrap">
                  {{ optional($question->created_at)->format('Y-m-d') }}
                </td>

                <td class="text-end text-nowrap action-btns">
                  <a href="{{ route('admin.questions.show', $question->id) }}" class="btn btn-outline-primary btn-sm">
                    {{ __('View') }}
                  </a>
                  <a href="{{ route('admin.questions.edit', $question->id) }}" class="btn btn-outline-warning btn-sm">
                    {{ __('Edit') }}
                  </a>
                  <form action="{{ route('admin.questions.destroy', $question->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm"
                      onclick="return confirm('{{ __('Delete this question?') }}')">
                      {{ __('Delete') }}
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                  {{ __('No questions found.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-3">
        {{ $questions->links() }}
      </div>
    </div>
  </div>

  {{-- Cascading filters --}}
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const subjectSelect = document.getElementById('subjectSelect');
        const sectionSelect = document.getElementById('sectionSelect');
        const lessonSelect  = document.getElementById('lessonSelect');

        const filtersUrl = @json(route('admin.questions.filters'));

        function setOptions(selectEl, items, placeholderText, getLabel) {
          const current = selectEl.value;
          selectEl.innerHTML = '';
          const ph = document.createElement('option');
          ph.value = '';
          ph.textContent = placeholderText;
          selectEl.appendChild(ph);

          (items || []).forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = getLabel(item);
            selectEl.appendChild(opt);
          });

          if ([...selectEl.options].some(o => o.value === current)) {
            selectEl.value = current;
          }
        }

        async function refreshBySubject() {
          const materialId = subjectSelect.value || '';
          const url = new URL(filtersUrl, window.location.origin);
          if (materialId) url.searchParams.set('material_id', materialId);

          const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const data = await res.json();

          setOptions(sectionSelect, data.sections, 'All', (s) => (s.title_en || s.title_ar || 'Section'));
          sectionSelect.value = '';

          setOptions(lessonSelect, data.lessons, 'All', (l) => (l.title_en || l.title_ar || 'Lesson'));
          lessonSelect.value = '';
        }

        async function refreshBySection() {
          const materialId = subjectSelect.value || '';
          const sectionId = sectionSelect.value || '';

          const url = new URL(filtersUrl, window.location.origin);
          if (materialId) url.searchParams.set('material_id', materialId);
          if (sectionId) url.searchParams.set('section_id', sectionId);

          const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const data = await res.json();

          if (materialId) {
            setOptions(sectionSelect, data.sections, 'All', (s) => (s.title_en || s.title_ar || 'Section'));
            if (sectionId && [...sectionSelect.options].some(o => o.value === sectionId)) {
              sectionSelect.value = sectionId;
            }
          }

          setOptions(lessonSelect, data.lessons, 'All', (l) => (l.title_en || l.title_ar || 'Lesson'));
          lessonSelect.value = '';
        }

        if (subjectSelect) subjectSelect.addEventListener('change', refreshBySubject);
        if (sectionSelect) sectionSelect.addEventListener('change', refreshBySection);
      });
    </script>
  @endpush
@endsection
