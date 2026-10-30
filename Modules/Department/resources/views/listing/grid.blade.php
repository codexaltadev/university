{{-- /departments, layout "grid" (FR-DEPT-06/09), grouped by Faculty. Contract: $groups (list of [faculty, departments]), $faculties, $listed (every listed department, whatever the filter), $currentFaculty, $options (per_row, sidebar) --}}
<x-theme::layout :title="__('department::department.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('department::department.plural') }}</h1>
        @include('department::partials.faculty-filter')
        @foreach ($groups as $group)
            <section class="department-group" style="margin-top:var(--space-8, 32px)">
                <h2>{{ $group['faculty']->name }}</h2>
                <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(min(100%, calc(100% / {{ (int) ($options['per_row'] ?? 3) }} - 24px)), 1fr))">
                    @foreach ($group['departments'] as $department)
                        @include('department::partials.card', ['department' => $department, 'style' => 'grid'])
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-theme::layout>
