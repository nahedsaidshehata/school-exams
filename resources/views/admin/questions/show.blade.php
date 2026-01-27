{{-- resources/views/admin/questions/show.blade.php.php --}}
@extends('layouts.admin')

@section('title', __('Question Details'))
@section('page_title', __('Question Details'))
@section('page_subtitle')
  {{ __('View question content and options.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.questions.edit', $question->id) }}" class="btn btn-outline-warning btn-sm">
    {{ __('Edit') }}
  </a>
  <a href="{{ route('admin.questions.index') }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back') }}
  </a>
@endsection

@section('content')
  @php
    $meta = is_array($question->metadata) ? $question->metadata : [];
    $type = (string) $question->type;

    $classification = $meta['classification'] ?? [];
    $classCats = $classification['categories'] ?? [];
    $classItems = $classification['items'] ?? [];

    $reorderItems = $meta['reorder_items'] ?? $meta['reorderItems'] ?? [];
  @endphp

  @push('head')
    <style>
      .kv p {
        margin: 0;
      }

      .kv strong {
        display: inline-block;
        min-width: 140px;
      }

      .box {
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 14px;
        padding: 14px;
        background: #fff;
      }

      .opt {
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 12px;
        padding: 12px;
        background: #fff;
      }

      .opt-correct {
        border-color: rgba(25, 135, 84, .35);
        background: rgba(25, 135, 84, .06);
      }

      .badge-pill {
        display: inline-flex;
        align-items: center;
        padding: .35rem .55rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
        border: 1px solid rgba(0, 0, 0, .08);
        background: rgba(13, 110, 253, .08);
        color: #0d6efd;
      }

      .muted-dash {
        color: #adb5bd;
      }
    </style>
  @endpush

  <div class="row g-3">
    <div class="col-12 col-xl-10">

      <div class="card admin-card mb-3">
        <div class="card-body">
          <div class="kv">
            <p><strong>{{ __('Type') }}:</strong> <span class="badge-pill">{{ $question->type }}</span></p>
            <p><strong>{{ __('Difficulty') }}:</strong> <span class="badge-pill">{{ $question->difficulty }}</span></p>
            <p><strong>{{ __('Lesson') }}:</strong>
              {{ $question->lesson->title_en ?? $question->lesson->title_ar ?? 'N/A' }}</p>
            <p class="text-muted small mt-2">
              {{ $question->lesson->section->material->name_en ?? $question->lesson->section->material->name_ar ?? '' }}
              @if($question->lesson?->section)
                — {{ $question->lesson->section->title_en ?? $question->lesson->section->title_ar ?? '' }}
              @endif
            </p>
          </div>
        </div>
      </div>

      <div class="box mb-3">
        <h6 class="mb-2">{{ __('Prompt (EN)') }}</h6>
        <div class="text-muted">{{ $question->prompt_en ?: '—' }}</div>
      </div>

      <div class="box mb-3">
        <h6 class="mb-2">{{ __('Prompt (AR)') }}</h6>
        <div dir="rtl">{{ $question->prompt_ar ?: '—' }}</div>
      </div>

      {{-- ✅ MCQ / TF --}}
      @if(in_array($type, ['MCQ', 'TF']) && $question->options && $question->options->count())
        <div class="box mb-3">
          <h6 class="mb-3">{{ __('Options') }}</h6>

          <div class="d-grid gap-2">
            @foreach($question->options as $idx => $opt)
              @php $isCorrect = (bool) ($opt->is_correct ?? false); @endphp
              <div class="opt {{ $isCorrect ? 'opt-correct' : '' }}">
                <div class="d-flex align-items-center justify-content-between">
                  <strong>{{ __('Option') }} {{ $idx + 1 }}</strong>
                  @if($isCorrect)
                    <span class="badge text-bg-success">✓ {{ __('Correct') }}</span>
                  @else
                    <span class="badge text-bg-secondary">{{ __('Wrong') }}</span>
                  @endif
                </div>

                <div class="row mt-2 g-2">
                  <div class="col-12 col-md-6">
                    <div class="text-muted small mb-1">{{ __('English') }}</div>
                    <div>{{ $opt->content_en ?: '—' }}</div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="text-muted small mb-1">{{ __('Arabic') }}</div>
                    <div dir="rtl">{{ $opt->content_ar ?: '—' }}</div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

        </div>
      @endif

      {{-- ✅ REORDER from metadata --}}
      @if($type === 'REORDER')
        <div class="box mb-3">
          <h6 class="mb-3">REORDER Items</h6>

          @if(is_array($reorderItems) && count($reorderItems))
            <ol dir="rtl" class="mb-0">
              @foreach($reorderItems as $it)
                @php
                  $txt = $it['text_ar'] ?? $it['ar'] ?? $it['text'] ?? '';
                @endphp
                <li>{{ $txt ?: '—' }}</li>
              @endforeach
            </ol>
          @elseif($question->options && $question->options->count())
            <ol dir="rtl" class="mb-0">
              @foreach($question->options as $opt)
                <li>{{ $opt->content_ar ?: '—' }}</li>
              @endforeach
            </ol>
          @else
            <div class="muted-dash">—</div>
          @endif
        </div>
      @endif

      {{-- ✅ CLASSIFICATION from metadata --}}
      @if($type === 'CLASSIFICATION')
        <div class="box">
          <h6 class="mb-3">CLASSIFICATION</h6>

          @php
            // Normalize categories if they come as indexed array
            $catA = null;
            $catB = null;

            // Try to find by ID first
            foreach ($classCats as $c) {
              if (($c['id'] ?? '') === 'A')
                $catA = $c;
              if (($c['id'] ?? '') === 'B')
                $catB = $c;
            }

            // Fallback to index if not found by ID
            if (!$catA && isset($classCats['A']))
              $catA = $classCats['A'];
            if (!$catB && isset($classCats['B']))
              $catB = $classCats['B'];

            if (!$catA && isset($classCats[0]))
              $catA = $classCats[0];
            if (!$catB && isset($classCats[1]))
              $catB = $classCats[1];

            $catA_ar = $catA['label_ar'] ?? $catA['ar'] ?? '';
            $catA_en = $catA['label_en'] ?? $catA['en'] ?? '';

            $catB_ar = $catB['label_ar'] ?? $catB['ar'] ?? '';
            $catB_en = $catB['label_en'] ?? $catB['en'] ?? '';
          @endphp

          <div class="row g-2 mb-3">
            <div class="col-12 col-md-6">
              <div class="opt">
                <strong>Category A</strong>
                <div dir="rtl">{{ $catA_ar ?: '—' }}</div>
                @if($catA_en)
                  <div class="small text-muted mt-1">{{ $catA_en }}</div>
                @endif
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="opt">
                <strong>Category B</strong>
                <div dir="rtl">{{ $catB_ar ?: '—' }}</div>
                @if($catB_en)
                  <div class="small text-muted mt-1">{{ $catB_en }}</div>
                @endif
              </div>
            </div>
          </div>

          <strong>Items</strong>
          <div class="mt-2">
            @if(is_array($classItems) && count($classItems))
              <ul dir="rtl" class="mb-0">
                @foreach($classItems as $it)
                  @php
                    $txt = $it['text_ar'] ?? $it['ar'] ?? $it['text'] ?? '';
                    $corr = $it['correct'] ?? $it['correct_category'] ?? $it['answer'] ?? null;
                  @endphp
                  <li>
                    {{ $txt ?: '—' }}
                    @if($corr)
                      <span class="badge text-bg-light ms-2">{{ $corr }}</span>
                    @endif
                  </li>
                @endforeach
              </ul>
            @else
              <div class="muted-dash">—</div>
            @endif
          </div>
        </div>
      @endif

    </div>
  </div>
@endsection