{{-- resources/views/admin/lessons/edit.blade.php --}}
@extends('layouts.admin')

@section('title', __('Edit Lesson'))
@section('page_title', __('Edit Lesson'))
@section('page_subtitle')
    {{ __('Update lesson details and its parent section.') }}
@endsection

@section('page_actions')
    <a href="{{ route('admin.lessons.index') }}" class="btn btn-outline-secondary btn-sm">
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

            .lo-box {
                border: 1px solid rgba(0, 0, 0, .08);
                border-radius: 12px;
                padding: 12px;
                max-height: 320px;
                overflow: auto;
                background: #fff;
            }

            .lo-item {
                border-bottom: 1px dashed rgba(0, 0, 0, .08);
                padding: 8px 0;
            }

            .lo-item:last-child {
                border-bottom: 0;
            }

            .lo-meta {
                font-size: .85rem;
                color: #6c757d;
            }

            .lo-title {
                font-weight: 600;
            }

            .lo-tools .btn {
                padding: .25rem .5rem;
                border-radius: 10px;
            }

            .content-card .card-header {
                background: rgba(25, 135, 84, .06);
                border-bottom: 1px solid rgba(0, 0, 0, .06);
                font-weight: 700;
            }

            .content-help {
                font-size: .9rem;
                color: #6c757d;
            }

            .attach-card .card-header {
                background: rgba(108, 117, 125, .08);
                border-bottom: 1px solid rgba(0, 0, 0, .06);
                font-weight: 700;
            }

            .badge-soft {
                border: 1px solid rgba(0, 0, 0, .08);
                background: rgba(0, 0, 0, .03);
                color: #444;
            }

            .mono {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            }
        </style>
    @endpush

    @php
        $selectedOutcomeIds = old('learning_outcome_ids', optional($lesson->learningOutcomes)->pluck('id')->toArray() ?? []);
        $attachments = $lesson->attachments ?? collect();

        // ✅ Correct route names (from your route:list)
        $attachmentsStoreRoute = 'admin.lessons.attachments.store';
        $attachmentsDestroyRoute = 'admin.lessons.attachments.destroy';
    @endphp

    <div class="row g-3">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card admin-card admin-form-card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <span>{{ __('Lesson Information') }}</span>
                    <span class="badge text-bg-secondary">#{{ $lesson->id }}</span>
                </div>

                <div class="card-body">
                    <form action="{{ route('admin.lessons.update', $lesson) }}" method="POST" class="needs-validation"
                          novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="section_id" class="form-label">
                                    {{ __('Section') }} <span class="req">*</span>
                                </label>
                                <select
                                    id="section_id"
                                    name="section_id"
                                    class="form-select @error('section_id') is-invalid @enderror"
                                    required
                                >
                                    <option value="">{{ __('Select Section') }}</option>
                                    @foreach($sections as $section)
                                        <option
                                            value="{{ $section->id }}" {{ old('section_id', $lesson->section_id) == $section->id ? 'selected' : '' }}>
                                            {{ __('Subject') }}: {{ $section->material->name_en }} — {{ __('Section') }}
                                            : {{ $section->title_en }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('section_id')
                                <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-hint mt-1">{{ __('Choose the parent section for this lesson.') }}</div>
                            </div>
                            <div class="col-12 col-md-12">
                                <label for="title_en" class="form-label">
                                    {{ __('Grade') }} <span class="req"></span>
                                </label>
                                <input
                                    type="number"
                                    id="grade"
                                    name="grade"
                                    value="{{ old('grade', $lesson->grade) }}"
                                    class="form-control @error('grade') is-invalid @enderror"
                                    required
                                    autocomplete="off">
                                @error('grade')
                                <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="title_en" class="form-label">
                                    {{ __('Title (English)') }} <span class="req"></span>
                                </label>
                                <input
                                    type="text"
                                    id="title_en"
                                    name="title_en"
                                    value="{{ old('title_en', $lesson->title_en) }}"
                                    class="form-control @error('title_en') is-invalid @enderror"
                                    autocomplete="off"
                                >
                                @error('title_en')
                                <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="title_ar" class="form-label">
                                    {{ __('Title (Arabic)') }} <span class="req"></span>
                                </label>
                                <input
                                    type="text"
                                    id="title_ar"
                                    name="title_ar"
                                    value="{{ old('title_ar', $lesson->title_ar) }}"
                                    class="form-control @error('title_ar') is-invalid @enderror"
                                    autocomplete="off"
                                >
                                @error('title_ar')
                                <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- ✅ Lesson Content (Optional) --}}
                        <div class="card admin-card content-card mb-3 mt-4">
                            <div
                                class="card-header fw-bold d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span>{{ __('Lesson Content (Optional)') }}</span>
                                <span
                                    class="text-muted small">{{ __('Arabic only, English only, or both — as needed.') }}</span>
                            </div>
                            <div class="card-body">
                                @if(empty($lesson->content_ar) && empty($lesson->content_en))
                                    <div class="alert alert-info">
                                        {{ __('No lesson content yet. Adding content will improve AI question generation quality.') }}
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('Content (Arabic)') }} <span
                                                class="text-muted small">({{ __('Optional') }})</span></label>
                                        <textarea
                                            name="content_ar"
                                            rows="10"
                                            class="form-control @error('content_ar') is-invalid @enderror"
                                            placeholder="{{ __('Add lesson explanation in Arabic...') }}"
                                        >{{ old('content_ar', $lesson->content_ar) }}</textarea>
                                        @error('content_ar')
                                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label">{{ __('Content (English)') }} <span
                                                class="text-muted small">({{ __('Optional') }})</span></label>
                                        <textarea
                                            name="content_en"
                                            rows="10"
                                            class="form-control @error('content_en') is-invalid @enderror"
                                            placeholder="{{ __('Add lesson explanation in English...') }}"
                                        >{{ old('content_en', $lesson->content_en) }}</textarea>
                                        @error('content_en')
                                        <div class="invalid-feedback">{{ $message }}</div> @enderror

                                        @if(!empty($lesson->content_updated_at))
                                            <div class="text-muted small mt-1">
                                                {{ __('Last updated') }}: <span
                                                    class="mono">{{ $lesson->content_updated_at }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ✅ Learning Outcomes inside form --}}
                        <div class="card admin-card mb-3 mt-4">
                            <div
                                class="card-header fw-bold d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span>{{ __('Learning Outcomes') }}</span>
                                <span
                                    class="text-muted small">{{ __('Search & select outcomes for this lesson.') }}</span>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-12 col-md-7">
                                        <input id="loSearch" type="text" class="form-control"
                                               placeholder="{{ __('Search (code / title)') }}">
                                    </div>
                                    <div class="col-12 col-md-5 lo-tools d-flex gap-2 justify-content-md-end">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="loSelectAll">
                                            {{ __('Select all') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-dark btn-sm" id="loClear">
                                            {{ __('Clear') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="lo-box" id="loBox">
                                    @forelse($learningOutcomes as $o)
                                        @php
                                            $checked = in_array($o->id, $selectedOutcomeIds);
                                        @endphp
                                        <div class="lo-item"
                                             data-text="{{ strtolower(($o->code ?? '').' '.$o->title_en.' '.$o->title_ar) }}">
                                            <label class="d-flex gap-2 align-items-start mb-0">
                                                <input class="form-check-input lo-check mt-1"
                                                       type="checkbox"
                                                       name="learning_outcome_ids[]"
                                                       value="{{ $o->id }}"
                                                    @checked($checked)>
                                                <div>
                                                    <div class="lo-title">{{ $o->title_ar }}</div>
                                                    <div class="lo-meta">
                                                        <span class="me-2">{{ $o->code ? $o->code : '—' }}</span>
                                                        <span>{{ $o->title_en }}</span>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-muted">{{ __('No learning outcomes found.') }}</div>
                                    @endforelse
                                </div>

                                @error('learning_outcome_ids')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-success">
                                {{ __('Update Lesson') }}
                            </button>
                            <a href="{{ route('admin.lessons.index') }}" class="btn btn-outline-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ✅ Lesson Attachments (Upload + status) --}}
            <div class="card admin-card attach-card mt-3">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span>{{ __('Lesson Content Files (PDF / Word / Images)') }}</span>
                    <span
                        class="text-muted small">{{ __('OCR is enabled. The system will try to extract text automatically.') }}</span>
                </div>
                <div class="card-body">

                    {{-- success message from upload/delete --}}
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- show ONLY upload-related errors --}}
                    @if($errors->has('files') || $errors->has('files.*'))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->get('files') as $msgs)
                                    @foreach($msgs as $m)
                                        <li>{{ $m }}</li>
                                    @endforeach
                                @endforeach
                                @foreach($errors->get('files.*') as $msgs)
                                    @foreach($msgs as $m)
                                        <li>{{ $m }}</li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ✅ Single correct upload form --}}
                    @if(\Illuminate\Support\Facades\Route::has($attachmentsStoreRoute))
                        <form method="POST"
                              action="{{ route($attachmentsStoreRoute, $lesson) }}"
                              enctype="multipart/form-data"
                              class="mb-3">
                            @csrf

                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-9">
                                    <label class="form-label mb-1">{{ __('Upload files') }}</label>
                                    <input
                                        type="file"
                                        name="files[]"
                                        class="form-control @error('files.*') is-invalid @enderror"
                                        multiple
                                        accept=".pdf,.docx,.jpg,.jpeg,.png,.webp"
                                    >
                                    @error('files.*')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-hint mt-1">
                                        {{ __('Allowed: PDF, DOCX, JPG, PNG, WEBP. Max 100MB each.') }}
                                    </div>
                                </div>

                                <div class="col-12 col-md-3 d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Upload') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif

                    @if($attachments->count() === 0)
                        <div class="alert alert-info mb-0">
                            {{ __('No files uploaded yet. You can upload a PDF/Word/images as lesson explanation.') }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                <tr>
                                    <th>{{ __('File') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th style="width: 220px;">{{ __('Actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($attachments as $att)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $att->original_name }}</div>
                                            <div
                                                class="text-muted small">{{ number_format(($att->size_bytes ?? 0) / 1024, 1) }}
                                                KB
                                            </div>
                                        </td>
                                        <td class="text-muted small">{{ $att->mime_type }}</td>
                                        <td>
                                            @php
                                                $status = $att->extraction_status ?? 'IDLE';
                                                $badgeClass = 'badge-soft';
                                                if($status === 'SUCCESS') $badgeClass = 'text-bg-success';
                                                elseif($status === 'FAILED') $badgeClass = 'text-bg-danger';
                                                elseif($status === 'PROCESSING') $badgeClass = 'text-bg-warning';
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $status }}</span>

                                            @if($status === 'FAILED' && !empty($att->extraction_error))
                                                <div class="text-danger small mt-1">{{ $att->extraction_error }}</div>
                                            @endif
                                        </td>
                                        <td class="d-flex flex-wrap gap-2">
                                            @if(($att->disk ?? '') === 'public' && ($att->path ?? null))
                                                <a class="btn btn-outline-secondary btn-sm"
                                                   href="{{ asset('storage/'.$att->path) }}" target="_blank">
                                                    {{ __('View') }}
                                                </a>
                                            @endif

                                            {{-- ✅ Correct destroy route --}}
                                            <form method="POST"
                                                  action="{{ route($attachmentsDestroyRoute, [$lesson, $att]) }}"
                                                  onsubmit="return confirm('{{ __('Delete this file?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="btn btn-outline-danger btn-sm">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="text-muted small mt-3">
                {{ __('Tip: Changing the section changes how the lesson is grouped under subjects.') }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const search = document.getElementById('loSearch');
            const box = document.getElementById('loBox');
            if (!search || !box) return;

            const items = Array.from(box.querySelectorAll('.lo-item'));

            function applyFilter() {
                const q = (search.value || '').trim().toLowerCase();
                items.forEach(el => {
                    const hay = (el.getAttribute('data-text') || '');
                    el.style.display = (!q || hay.includes(q)) ? '' : 'none';
                });
            }

            document.getElementById('loSelectAll')?.addEventListener('click', function () {
                items.forEach(el => {
                    if (el.style.display === 'none') return;
                    const cb = el.querySelector('.lo-check');
                    if (cb) cb.checked = true;
                });
            });

            document.getElementById('loClear')?.addEventListener('click', function () {
                box.querySelectorAll('.lo-check').forEach(cb => cb.checked = false);
            });

            search.addEventListener('input', applyFilter);
        })();
    </script>
@endpush
