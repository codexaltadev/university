{{-- /departments: one Faculty or all (FR-DEPT-06). Contract: $faculties, $currentFaculty --}}
<nav class="department-filter" aria-label="{{ __('department::department.fields.faculty') }}" style="display:flex;flex-wrap:wrap;gap:.5rem;margin-block:var(--space-4, 16px)">
    <a href="{{ url('/departments') }}" @if (! $currentFaculty) aria-current="page" style="font-weight:700" @endif>{{ __('department::department.all_faculties') }}</a>
    @foreach ($faculties as $faculty)
        <a href="{{ url('/departments').'?faculty='.urlencode($faculty->short_name) }}" @if ($currentFaculty?->is($faculty)) aria-current="page" style="font-weight:700" @endif>{{ $faculty->short_name }}</a>
    @endforeach
</nav>
