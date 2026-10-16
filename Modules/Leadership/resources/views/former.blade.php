{{-- /leadership/former (FR-LEAD-05). Contract: $positions (list of [position, terms — latest first]) --}}
<x-theme::layout :title="__('leadership::leadership.former')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/'.\Modules\Leadership\Models\LeadershipTerm::PATH) }}">{{ __('leadership::leadership.plural') }}</a><span aria-hidden="true"> › </span></li>
                <li><span aria-current="page">{{ __('leadership::leadership.former') }}</span></li>
            </ol>
        </nav>
        <h1>{{ __('leadership::leadership.former') }}</h1>

        @forelse ($positions as $item)
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('leadership::leadership.former_of', ['position' => $item['position']->title]) }}</h2>
                <ul style="list-style:none;padding:0;display:grid;gap:var(--space-2, 8px)">
                    @foreach ($item['terms'] as $term)
                        <li style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:var(--space-3, 12px);padding:var(--space-3, 12px) var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)">
                            <a href="{{ url($term->url()) }}">{{ $term->name() }}</a>
                            <span>{{ $term->tenure() }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p style="margin-top:var(--space-8, 32px)">{{ __('leadership::leadership.no_former') }}</p>
        @endforelse
    </div>
</x-theme::layout>
