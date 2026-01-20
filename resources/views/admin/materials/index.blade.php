{{-- resources/views/admin/materials/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Subjects Management'))
@section('page_title', __('Subjects'))
@section('page_subtitle')
  {{ __('Manage the central content bank subjects (global).') }}
@endsection

@section('page_actions')
  <a href="{{ route('admin.materials.create') }}" class="btn btn-success btn-sm">
    {{ __('Create Subject') }}
  </a>
@endsection

@section('content')
  @push('head')
    <style>
      .table td, .table th { vertical-align: middle; }
      .truncate {
        max-width: 280px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .btn-icon {
        padding: .35rem .55rem;
        border-radius: 10px;
      }
    </style>
  @endpush

  <div class="card admin-card">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="text-muted small">
          {{ __('Total:') }} <span class="fw-semibold">{{ method_exists($materials, 'total') ? $materials->total() : '' }}</span>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
          {{ __('Back to Dashboard') }}
        </a>
      </div>

      <div class="table-responsive">
        <table class="table admin-table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>{{ __('Name (EN)') }}</th>
              <th>{{ __('Name (AR)') }}</th>
              <th class="text-nowrap">{{ __('Sections') }}</th>
              <th class="text-nowrap">{{ __('Created') }}</th>
              <th class="text-nowrap text-end">{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($materials as $material)
              <tr>
                <td class="truncate" title="{{ $material->name_en }}">{{ $material->name_en }}</td>
                <td class="truncate" title="{{ $material->name_ar }}">{{ $material->name_ar }}</td>

                <td class="text-nowrap">
                  <span class="badge text-bg-primary">{{ $material->sections_count }}</span>
                </td>

                <td class="text-nowrap">
                  {{ optional($material->created_at)->format('Y-m-d') }}
                </td>

                <td class="text-end">
                  <div class="d-inline-flex gap-2">
                    <a href="{{ route('admin.materials.edit', $material) }}" class="btn btn-outline-primary btn-sm btn-icon">
                      {{ __('Edit') }}
                    </a>

                    <form action="{{ route('admin.materials.destroy', $material) }}" method="POST" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button
                        type="submit"
                        class="btn btn-outline-danger btn-sm btn-icon"
                        onclick="return confirm('{{ __('Are you sure you want to delete this subject?') }}')"
                      >
                        {{ __('Delete') }}
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                  {{ __('No subjects found.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center mt-3">
        {{ $materials->links() }}
      </div>
    </div>
  </div>
@endsection
