@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Assigned Exams</h2>

    @if($exams->count() > 0)
        <div class="row">
            @foreach($exams as $exam)
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $exam->title_en }}</h5>
                            <p class="card-text text-muted">{{ $exam->title_ar }}</p>
                            <hr>
                            <p><strong>Duration:</strong> {{ $exam->duration_minutes }} minutes</p>
                            <p><strong>Period:</strong> {{ $exam->starts_at->format('M d, Y H:i') }} - {{ $exam->ends_at->format('M d, Y H:i') }}</p>
                            <p><strong>Questions:</strong> {{ $exam->exam_questions_count }}</p>
                            <a href="{{ route('school.exams.show', $exam->id) }}" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">
            No exams assigned to your school yet.
        </div>
    @endif
</div>
@endsection
