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
  </tr>
@empty
  <tr>
    <td colspan="4" class="text-center py-4 text-muted">
      {{ __('No schools found.') }}
    </td>
  </tr>
@endforelse
