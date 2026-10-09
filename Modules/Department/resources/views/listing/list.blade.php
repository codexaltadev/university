{{-- /departments, layout "list" (FR-DEPT-09), grouped by Faculty. Contract: $groups, $faculties, $currentFaculty, $options --}}
<x-theme::layout :title="__('department::department.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('department::department.plural') }}</h1>
        @include('department::partials.faculty-filter')
        @foreach ($groups as $group)
            <section class="department-group" style="margin-top:var(--space-8, 32px)">
                <h2>{{ $group['faculty']->name }}</h2>
                <div style="display:grid;gap:var(--space-4, 16px)">
                    @foreach ($group['departments'] as $department)
                        <div style="border-bottom:1px solid var(--color-border);padding-bottom:var(--space-4, 16px)">
                            @include('department::partials.card', ['department' => $department, 'style' => 'list'])
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-theme::layout>
