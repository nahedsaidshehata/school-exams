@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $exam->title_en }}</h2>
        <a href="{{ route('school.exams.index') }}" class="btn btn-secondary">Back to Exams</a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Exam Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Title (Arabic):</strong> {{ $exam->title_ar }}</p>
                    <p><strong>Duration:</strong> {{ $exam->duration_minutes }} minutes</p>
                    <p><strong>Questions:</strong> {{ $exam->examQuestions->count() }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Start:</strong> {{ $exam->starts_at->format('M d, Y H:i') }}</p>
                    <p><strong>End:</strong> {{ $exam->ends_at->format('M d, Y H:i') }}</p>
                    <p><strong>Max Attempts:</strong> {{ $exam->max_attempts }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Questions Preview</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>Note:</strong> Correct answers and points are not visible to school users.
            </div>

            @foreach($exam->examQuestions as $examQuestion)
                <div class="mb-4 p-3 border rounded">
                    <h6>Question {{ $examQuestion->order_index }}</h6>
                    <p><strong>Type:</strong> <span class="badge badge-info">{{ $examQuestion->question->type }}</span></p>
                    <p><strong>Difficulty:</strong> <span class="badge badge-secondary">{{ $examQuestion->question->difficulty }}</span></p>
                    <p><strong>Prompt (EN):</strong> {{ $examQuestion->question->prompt_en }}</p>
                    <p><strong>Prompt (AR):</strong> {{ $examQuestion->question->prompt_ar }}</p>

                    @if($examQuestion->question->type === 'MCQ' || $examQuestion->question->type === 'TF')
                        <p><strong>Options:</strong></p>
                        <ul>
                            @foreach($examQuestion->question->options as $option)
                                <li>{{ $option->content_en }} / {{ $option->content_ar }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
