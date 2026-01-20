{{-- resources/views/admin/students/import.blade.php --}}
@extends('layouts.admin')

@section('title', __('Import Students'))
@section('page_title', __('Import Students (XLSX)'))
@section('page_subtitle')
  {{ __('Upload an Excel file to bulk create students for schools.') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary btn-sm">
    {{ __('Back') }}
  </a>
@endsection

@section('content')
  <div class="row g-3">
    <div class="col-12 col-lg-10 col-xl-8">
      <div class="card admin-card">
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

          <div class="d-flex flex-wrap gap-2 mb-3">
            <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.students.import.template') }}">
              {{ __('Download XLSX Template') }}
            </a>
          </div>

          <div class="alert alert-info">
            <div class="fw-semibold mb-1">Columns:</div>
            <div class="small">
              SchoolName (required) • StudentFullName • Academic Year • Grade • Gender (male/female) • SEND (Yes/No) • ParentEmail • Nationality • Email • UserName • Password
            </div>
            <div class="small mt-2">
              If UserName is empty, we auto-generate. If Password is empty, default is <span class="fw-semibold">password</span>.
            </div>
            <div class="small mt-2 text-muted">
              Note: The import accepts both <span class="fw-semibold">Year</span> and <span class="fw-semibold">Academic Year</span> headers (for old templates).
            </div>
          </div>

          <form method="POST" action="{{ route('admin.students.import.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label class="form-label">{{ __('XLSX File') }}</label>
              <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
            </div>
            <button class="btn btn-success">
              {{ __('Import') }}
            </button>
          </form>

        </div>
      </div>
    </div>
  </div>
@endsection
