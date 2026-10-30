{{-- /programs, layout "table" (FR-PROG-12): one row per program with its facts. Contract: $programs, $choices, $selected, $options --}}
<x-theme::layout :title="__('program::program.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('program::program.plural') }}</h1>
        @include('program::partials.filters')
        @if ($programs->isEmpty())
            <p class="program-empty">{{ __('program::program.no_results') }}</p>
        @else
            <div tabindex="0" style="overflow-x:auto">
                <table class="program-table" style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="text-align:left;border-bottom:2px solid var(--color-border)">
                            <th scope="col">{{ __('program::program.fields.name') }}</th>
                            <th scope="col">{{ __('program::program.fields.department') }}</th>
                            <th scope="col">{{ __('program::program.facts.level') }}</th>
                            <th scope="col">{{ __('program::program.facts.duration') }}</th>
                            <th scope="col">{{ __('program::program.facts.credits') }}</th>
                            <th scope="col">{{ __('program::program.facts.shift') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programs as $program)
                            <tr class="program-row" style="border-bottom:1px solid var(--color-border)">
                                <td><a href="{{ $program->url() }}">{{ $program->name }}</a></td>
                                <td>{{ $program->department?->short_name }}</td>
                                <td>{{ $program->level?->name }}</td>
                                <td>{{ $program->durationText() }}</td>
                                <td>{{ \Modules\Program\Models\Program::plainNumber($program->total_credits) }}</td>
                                <td>{{ $program->shiftLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-theme::layout>
