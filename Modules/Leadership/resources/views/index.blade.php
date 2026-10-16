{{--
    /leadership (FR-LEAD-03). Contract: $groups (list of [group: ?Term, positions: list of [position, terms]]), $layout (grid / cards / list)
--}}
<x-theme::layout :title="__('leadership::leadership.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('leadership::leadership.plural') }}</h1>

        @forelse ($groups as $group)
            <section class="leadership-group" style="margin-top:var(--space-8, 32px)">
                @if ($groups->count() > 1 || $group['group'])
                    <h2>{{ $group['group']?->name ?? __('leadership::leadership.other') }}</h2>
                @endif
                <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:{{ $layout === 'list' ? '1fr' : 'repeat(auto-fill,minmax('.($layout === 'cards' ? '300px' : '220px').',1fr))' }}">
                    @foreach ($group['positions'] as $item)
                        @foreach ($item['terms'] as $term)
                            @include('leadership::partials.person', ['term' => $term, 'position' => $item['position'], 'layout' => $layout])
                        @endforeach
                    @endforeach
                </div>
            </section>
        @empty
            <p style="margin-top:var(--space-8, 32px)">{{ __('leadership::leadership.none') }}</p>
        @endforelse

        <p style="margin-top:var(--space-10, 40px)"><a href="{{ url('/'.\Modules\Leadership\Models\LeadershipTerm::PATH.'/former') }}">{{ __('leadership::leadership.former') }}</a></p>
    </div>
</x-theme::layout>
