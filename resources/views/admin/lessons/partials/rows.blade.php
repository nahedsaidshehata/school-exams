@forelse($lessons as $lesson)
  <tr>
    <td class="truncate" title="{{ $lesson->title_en }}">{{ $lesson->title_en }}</td>
    <td class="truncate" title="{{ $lesson->title_ar }}">{{ $lesson->title_ar }}</td>

    <td class="truncate" title="{{ $lesson->section->title_en ?? '' }}">
      {{ $lesson->section->title_en ?? 'N/A' }}
    </td>

    <td class="truncate" title="{{ $lesson->section->material->name_en ?? '' }}">
      {{ $lesson->section->material->name_en ?? 'N/A' }}
    </td>

    <td class="text-nowrap">
      <span class="badge badge-soft">{{ $lesson->learning_outcomes_count ?? 0 }}</span>
    </td>

    <td class="text-nowrap">
      {{ optional($lesson->created_at)->format('Y-m-d') }}
    </td>

    <td class="text-end">
      <div class="d-inline-flex gap-2">
        <a href="{{ route('admin.lessons.show', $lesson->id) }}" class="btn btn-outline-primary btn-sm btn-icon">
          {{ __('View') }}
        </a>

        <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-outline-primary btn-sm btn-icon">
          {{ __('Edit') }}
        </a>

        <form action="{{ route('admin.lessons.destroy', $lesson) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button
            type="submit"
            class="btn btn-outline-danger btn-sm btn-icon"
            onclick="return confirm('{{ __('Are you sure you want to delete this lesson?') }}')"
          >
            {{ __('Delete') }}
          </button>
        </form>
      </div>
    </td>
  </tr>
@empty
  <tr>
    <td colspan="7" class="text-center py-4 text-muted">
      {{ __('No lessons found.') }}
    </td>
  </tr>
@endforelse
