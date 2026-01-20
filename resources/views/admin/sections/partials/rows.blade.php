@forelse($sections as $section)
  <tr>
    <td class="truncate" title="{{ $section->title_en }}">{{ $section->title_en }}</td>
    <td class="truncate" title="{{ $section->title_ar }}">{{ $section->title_ar }}</td>
    <td class="truncate" title="{{ $section->material->name_en ?? '' }}">
      {{ $section->material->name_en ?? 'N/A' }}
    </td>
    <td class="text-nowrap">
      {{ optional($section->created_at)->format('Y-m-d') }}
    </td>
    <td class="text-end">
      <div class="d-inline-flex gap-2">
        <a href="{{ route('admin.sections.edit', $section) }}" class="btn btn-outline-primary btn-sm btn-icon">
          {{ __('Edit') }}
        </a>

        <form action="{{ route('admin.sections.destroy', $section) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button
            type="submit"
            class="btn btn-outline-danger btn-sm btn-icon"
            onclick="return confirm('{{ __('Are you sure you want to delete this section?') }}')"
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
      {{ __('No sections found.') }}
    </td>
  </tr>
@endforelse
