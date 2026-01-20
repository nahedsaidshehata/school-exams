@extends('layouts.student')

@section('title', __('Help'))

@section('content')
<div class="card student-card">
    <div class="card-body">
        <h2 class="h4 mb-3">{{ __('Help & Support') }}</h2>
        
        <div class="mb-4">
            <h3 class="h6">{{ __('How to take an exam') }}</h3>
            <ol>
                <li>{{ __('Navigate to the Exams page from the sidebar') }}</li>
                <li>{{ __('Find an available exam and click "View Exam"') }}</li>
                <li>{{ __('Read the instructions carefully') }}</li>
                <li>{{ __('Click "Start Exam" when ready') }}</li>
                <li>{{ __('Answer all questions before time runs out') }}</li>
                <li>{{ __('Click "Submit" when finished') }}</li>
            </ol>
        </div>

        <div class="mb-4">
            <h3 class="h6">{{ __('Important Notes') }}</h3>
            <ul>
                <li>{{ __('Your answers are saved automatically') }}</li>
                <li>{{ __('Do not refresh the page during an exam') }}</li>
                <li>{{ __('Exams will auto-submit when time expires') }}</li>
                <li>{{ __('Grades are not shown to students') }}</li>
            </ul>
        </div>

        <div class="alert alert-info">
            <strong>{{ __('Need more help?') }}</strong><br>
            {{ __('Contact your school administrator for additional support.') }}
        </div>
    </div>
</div>
@endsection
