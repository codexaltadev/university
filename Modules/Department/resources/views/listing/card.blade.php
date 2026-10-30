{{-- /departments, layout "card" (FR-DEPT-09), grouped by Faculty: picture, name and summary on a raised card. Contract: $groups, $faculties, $listed, $currentFaculty, $options --}}
<x-theme::layout :title="__('department::department.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('department::department.plural') }}</h1>
        @include('department::partials.faculty-filter')
        @foreach ($groups as $group)
            <section class="department-group" style="margin-top:var(--space-8, 32px)">
                <h2>{{ $group['faculty']->name }}</h2>
                <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fill, minmax(min(100%, calc(100% / {{ (int) ($options['per_row'] ?? 2) }} - 24px)), 1fr))">
                    @foreach ($group['departments'] as $department)
                        <div style="background:var(--color-bg);box-shadow:var(--shadow-card);border-radius:var(--radius-lg, 16px);padding:var(--space-4, 16px)">
                            @include('department::partials.card', ['department' => $department, 'style' => 'card'])
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-theme::layout>
