{{-- resources/views/admin/schools/create.blade.php --}}
@extends('layouts.admin')

@section('title', __('Show School'))
@section('page_title', __('Show  School'))
@section('page_subtitle')
    {{ __('show a school and its associated school account user.') }}
@endsection

@section('page_actions')
    <a href="{{ route('admin.schools.index') }}" class="btn btn-outline-secondary btn-sm">
        {{ __('Back') }}
    </a>
@endsection

@section('content')
    @push('head')
        <style>
            .form-hint {
                color: #6c757d;
                font-size: .9rem;
            }

            .req {
                color: #dc3545;
                font-weight: 700;
            }

            .admin-form-card .card-header {
                background: rgba(13, 110, 253, .06);
                border-bottom: 1px solid rgba(0, 0, 0, .06);
                font-weight: 700;
            }
        </style>
    @endpush

    <div class="row g-3">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card admin-card admin-form-card">
                <div class="card-header">
                    {{ __('School Information') }}
                </div>
                <div class="card-body">
                    <form
                          class="needs-validation" novalidate>
                        @csrf

                        {{-- School info --}}
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="name_en" class="form-label">
                                    {{ __('School Name (English)') }} <span class="req">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name_en"
                                    name="name_en"
                                    value="{{$school->name_en??''}}"
                                    class="form-control @error('name_en') is-invalid @enderror"
                                    required
                                    autocomplete="off"
                                    disabled
                                >
                                @error('name_en')
                                <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="name_ar" class="form-label">
                                    {{ __('School Name (Arabic)') }} <span class="req">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name_ar"
                                    name="name_ar"
                                    value="{{ $school->name_ar??'' }}"
                                    class="form-control @error('name_ar') is-invalid @enderror"
                                    required
                                    autocomplete="off"
                                    disabled
                                >
                                @error('name_ar')
                                <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- School account user --}}
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <div class="fw-bold">{{ __('School Account User') }}</div>
                                <div class="form-hint">{{ __('This user will login under the School role.') }}</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="username" class="form-label">
                                    {{ __('Username') }} <span class="req">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    value="{{ $user->username??'' }}"
                                    class="form-control @error('username') is-invalid @enderror"
                                    required
                                    autocomplete="off"
                                    disabled
                                >
                                @error('username')
                                <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">
                                    {{ __('Email (Optional)') }}
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{$user->email??'' }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    autocomplete="off"
                                    disabled
                                >
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="full_name" class="form-label">
                                    {{ __('Full Name (Optional)') }}
                                </label>
                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    value="{{$user->full_name??'' }}"
                                    class="form-control @error('full_name') is-invalid @enderror"
                                    autocomplete="off"
                                    disabled
                                >
                                @error('full_name')
                                <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6"></div>

{{--                            <div class="col-12 col-md-6">--}}
{{--                                <label for="password" class="form-label">--}}
{{--                                    {{ __('Password') }} <span class="req">*</span>--}}
{{--                                </label>--}}
{{--                                <input--}}
{{--                                    type="password"--}}
{{--                                    id="password"--}}
{{--                                    name="password"--}}
{{--                                    class="form-control @error('password') is-invalid @enderror"--}}
{{--                                    required--}}
{{--                                    autocomplete="new-password"--}}
{{--                                >--}}
{{--                                @error('password')--}}
{{--                                <div class="invalid-feedback">{{ $message }}</div> @enderror--}}
{{--                            </div>--}}

{{--                            <div class="col-12 col-md-6">--}}
{{--                                <label for="password_confirmation" class="form-label">--}}
{{--                                    {{ __('Confirm Password') }} <span class="req">*</span>--}}
{{--                                </label>--}}
{{--                                <input--}}
{{--                                    type="password"--}}
{{--                                    id="password_confirmation"--}}
{{--                                    name="password_confirmation"--}}
{{--                                    class="form-control"--}}
{{--                                    required--}}
{{--                                    autocomplete="new-password"--}}
{{--                                >--}}
{{--                            </div>--}}
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
{{--                            <button type="submit" class="btn btn-success">--}}
{{--                                {{ __('Update School') }}--}}
{{--                            </button>--}}
                            <a href="{{ route('admin.schools.index') }}" class="btn btn-outline-secondary">
                                {{ __('back') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-muted small mt-3">
                {{ __('Tip: Use a unique username for the school account.') }}
            </div>
        </div>
    </div>
@endsection
