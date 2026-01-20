{{-- resources/views/admin/lessons/ai/questions/create.blade.php --}}
@extends('layouts.admin')

@section('title', __('AI Question Generator'))
@section('page_title', __('AI Question Generator'))
@section('page_subtitle')
  {{ __('Generate draft questions for this lesson, then review and save.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back to Lesson') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .req { color: #dc3545; font-weight: 700; }
      .hint { color:#6c757d; font-size:.9rem; }
      .card-header-strong {
        background: rgba(13,110,253,.06);
        border-bottom: 1px solid rgba(0,0,0,.06);
        font-weight: 700;
      }
      .lo-chip {
        display:inline-flex;
        align-items:center;
        gap:.4rem;
        border:1px solid rgba(0,0,0,.10);
        background: rgba(0,0,0,.02);
        padding:.25rem .5rem;
        border-radius:999px;
        margin:.15rem .15rem 0 0;
        font-size:.85rem;
      }
      .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
      .type-pill {
        border:1px solid rgba(0,0,0,.10);
        background:#fff;
        border-radius:999px;
        padding:.35rem .65rem;
        display:flex;
        align-items:center;
        gap:.5rem;
      }
      .dist-box {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 12px;
        padding: 12px;
        background: #fff;
      }
    </style>
  @endpush

  @php
    $loCount = $lesson->learningOutcomes->count();
    $hasAnyContent = !empty($lesson->content_ar) || !empty($lesson->content_en);

    // ✅ extracted_text (not text_extracted) + SUCCESS only
    $hasExtracted = false;
    foreach(($lesson->attachments ?? collect()) as $att) {
      if(($att->extraction_status ?? '') === 'SUCCESS' && !empty($att->extracted_text)) { $hasExtracted = true; break; }
    }

    $oldTypes = old('types', ['MCQ','TF']);

    // ✅ Controller expects: lang_mode in [ar,en,both]
    $oldLang = old('lang_mode','ar');

    $oldCount = old('count', 10);
    $oldEasy = old('difficulty_easy', 40);
    $oldMed  = old('difficulty_medium', 30);
    $oldHard = old('difficulty_hard', 30);

    $types = [
      'MCQ' => 'MCQ (Multiple Choice)',
      'TF' => 'True / False',
      'ESSAY' => 'Essay',
      'CLASSIFICATION' => 'Classification',
      'REORDER' => 'Reorder',
      // 'FILL_BLANK' => 'Fill in the Blank', // ⛔ remove for now unless controller supports it
    ];
  @endphp

  <div class="row g-3">
    <div class="col-12 col-lg-10 col-xl-9">

      {{-- Lesson summary --}}
      <div class="card admin-card mb-3">
        <div class="card-header card-header-strong d-flex flex-wrap align-items-center justify-content-between gap-2">
          <span>{{ __('Lesson') }}</span>
          <span class="badge text-bg-secondary">#{{ $lesson->id }}</span>
        </div>
        <div class="card-body">
          <div class="mb-2">
            <div class="fw-semibold">{{ $lesson->title_ar }}</div>
            <div class="text-muted small">{{ $lesson->title_en }}</div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <span class="badge text-bg-{{ $loCount ? 'success' : 'warning' }}">
              {{ __('Learning Outcomes') }}: {{ $loCount }}
            </span>

            <span class="badge text-bg-{{ $hasAnyContent ? 'success' : 'warning' }}">
              {{ __('Text Content') }}: {{ $hasAnyContent ? __('Available') : __('Missing') }}
            </span>

            <span class="badge text-bg-{{ $hasExtracted ? 'success' : 'secondary' }}">
              {{ __('Extracted Text') }}: {{ $hasExtracted ? __('Available') : __('None') }}
            </span>
          </div>

          @if($loCount)
            <div class="mt-3">
              <div class="text-muted small mb-1">{{ __('Selected outcomes for this lesson:') }}</div>
              @foreach($lesson->learningOutcomes as $o)
                <span class="lo-chip" title="{{ $o->title_en }}">
                  <span class="mono">{{ $o->code ?? '—' }}</span>
                  <span>{{ $o->title_ar }}</span>
                </span>
              @endforeach
            </div>
          @else
            <div class="alert alert-warning mt-3 mb-0">
              {{ __('No learning outcomes selected for this lesson. Please select outcomes first to improve generation quality.') }}
            </div>
          @endif
        </div>
      </div>

      {{-- Generate form --}}
      <div class="card admin-card">
        <div class="card-header card-header-strong">
          {{ __('Generation Settings') }}
        </div>

        <div class="card-body">
          <form method="POST" action="{{ route('admin.lessons.ai.questions.generate', $lesson) }}" class="needs-validation" novalidate>
            @csrf

            <div class="row g-3">
              {{-- ✅ Language mode --}}
              <div class="col-12 col-md-6">
                <label class="form-label">
                  {{ __('Question Language') }} <span class="req">*</span>
                </label>

                <select name="lang_mode" class="form-select @error('lang_mode') is-invalid @enderror" required>
                  <option value="ar" @selected($oldLang==='ar')>{{ __('Arabic only') }}</option>
                  <option value="en" @selected($oldLang==='en')>{{ __('English only') }}</option>
                  <option value="both" @selected($oldLang==='both')>{{ __('Arabic + English') }}</option>
                </select>

                @error('lang_mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="hint mt-1">{{ __('You can generate in Arabic, English, or both.') }}</div>
              </div>

              {{-- Count --}}
              <div class="col-12 col-md-6">
                <label class="form-label">
                  {{ __('Number of Questions') }} <span class="req">*</span>
                </label>
                <input type="number"
                       name="count"
                       min="1"
                       max="50"
                       value="{{ $oldCount }}"
                       class="form-control @error('count') is-invalid @enderror"
                       required>
                @error('count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="hint mt-1">{{ __('Max 50 per generation.') }}</div>
              </div>

              {{-- Types --}}
              <div class="col-12">
                <label class="form-label">
                  {{ __('Question Types') }} <span class="req">*</span>
                </label>

                <div class="d-flex flex-wrap gap-2">
                  @foreach($types as $key => $label)
                    <label class="type-pill">
                      <input type="checkbox"
                             name="types[]"
                             value="{{ $key }}"
                             class="form-check-input m-0"
                             @checked(in_array($key, $oldTypes))>
                      <span>{{ $label }}</span>
                    </label>
                  @endforeach
                </div>

                @error('types') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                @error('types.*') <div class="text-danger small mt-2">{{ $message }}</div> @enderror

                <div class="hint mt-2">
                  {{ __('Choose one or more types. This phase uses mock generation until a real AI provider is connected.') }}
                </div>
              </div>

              {{-- Difficulty distribution --}}
              <div class="col-12">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                  <label class="form-label mb-0">
                    {{ __('Difficulty Distribution') }} <span class="req">*</span>
                  </label>
                  <span class="hint">{{ __('Must sum to 100%.') }}</span>
                </div>

                <div class="dist-box mt-2">
                  <div class="row g-2">
                    <div class="col-12 col-md-4">
                      <label class="form-label">{{ __('Easy %') }}</label>
                      <input type="number" name="difficulty_easy" min="0" max="100"
                             value="{{ $oldEasy }}"
                             class="form-control @error('difficulty_easy') is-invalid @enderror" required>
                      @error('difficulty_easy') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                      <label class="form-label">{{ __('Medium %') }}</label>
                      <input type="number" name="difficulty_medium" min="0" max="100"
                             value="{{ $oldMed }}"
                             class="form-control @error('difficulty_medium') is-invalid @enderror" required>
                      @error('difficulty_medium') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-4">
                      <label class="form-label">{{ __('Hard %') }}</label>
                      <input type="number" name="difficulty_hard" min="0" max="100"
                             value="{{ $oldHard }}"
                             class="form-control @error('difficulty_hard') is-invalid @enderror" required>
                      @error('difficulty_hard') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                  </div>

                  <div class="hint mt-2">
                    {{ __('Example: 40 / 30 / 30. The AI will try to match this distribution approximately.') }}
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <button class="btn btn-primary">
                {{ __('Generate Draft') }}
              </button>
              <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-outline-secondary">
                {{ __('Cancel') }}
              </a>
            </div>

            <div class="text-muted small mt-3">
              {{ __('Note: This phase generates a draft first. You will review and edit before saving to the database.') }}
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
@endsection
