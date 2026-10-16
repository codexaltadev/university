{{-- /links and /{entity}/links (FR-LINK-02). Contract: $groups (list of ['group' => ?Term, 'links' => Link list]), $layout (tiles / list) --}}
<x-theme::layout :title="__('links::links.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('links::links.plural') }}</h1>
        @forelse ($groups as $group)
            <section style="margin-top:var(--space-8, 32px)">
                @if ($group['group'] || count($groups) > 1)<h2>{{ $group['group']?->name ?? __('links::links.other') }}</h2>@endif
                <div style="{{ $layout === 'tiles' ? 'display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(180px,1fr))' : '' }}">
                    @foreach ($group['links'] as $link)
                        @include('links::partials.link', ['link' => $link, 'layout' => $layout])
                    @endforeach
                </div>
            </section>
        @empty
            <p style="margin-top:var(--space-8, 32px)">{{ __('links::links.none') }}</p>
        @endforelse
    </div>
</x-theme::layout>
