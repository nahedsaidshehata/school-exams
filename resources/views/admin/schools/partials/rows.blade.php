{{-- resources/views/admin/schools/partials/rows.blade.php --}}
@forelse($schools as $school)
    <tr>
        <td class="truncate" title="{{ $school->name_en }}">{{ $school->name_en }}</td>
        <td class="truncate" title="{{ $school->name_ar }}">{{ $school->name_ar }}</td>
        <td class="text-nowrap">
            @if(!empty($school->schoolUser->username))
                <span class="badge text-bg-primary">{{ $school->schoolUser->username }}</span>
            @else
                <span class="text-muted">N/A</span>
            @endif
        </td>
        <td class="text-nowrap">
            {{ optional($school->created_at)->format('Y-m-d') }}
        </td>
        <td class="text-end text-nowrap action-btns">
            <a href="{{ route('admin.schools.show', $school->id) }}" class="btn btn-outline-primary btn-sm">
                {{ __('View') }}
            </a>
            <a href="{{ route('admin.schools.edit', $school->id) }}" class="btn btn-outline-warning btn-sm">
                {{ __('Edit') }}
            </a>
            <form action="{{ route('admin.schools.destroy', $school->id) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm"
                        onclick="return confirm('{{ __('Delete this school?') }}')">
                    {{ __('Delete') }}
                </button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="text-center py-4 text-muted">
            {{ __('No schools found.') }}
        </td>
    </tr>
@endforelse
