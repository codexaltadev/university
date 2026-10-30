{{--
    /committees and /{entity}/committees (FR-COM-06). Contract: $groups (list of [category: ?Term, committees]),
    $categories (in use), $selected (?Term), $layout (table / cards / list), $base (microsite path or '')
--}}
<x-theme::layout :title="__('committee::committee.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('committee::committee.plural') }}@if ($selected) — {{ $selected->name }}@endif</h1>

        @if ($categories->count() > 1)
            <nav aria-label="{{ __('committee::committee.fields.category') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px)">
                <a href="{{ url($base.'/committees') }}" @class(['chip', 'chip-active' => ! $selected])>{{ __('committee::committee.all') }}</a>
                @foreach ($categories as $category)
                    <a href="{{ url($base.'/committees').'?category='.$category->slug() }}" @class(['chip', 'chip-active' => $selected?->is($category)])>{{ $category->name }}</a>
                @endforeach
            </nav>
        @endif

        @forelse ($groups as $group)
            <section style="margin-top:var(--space-8, 32px)">
                @if ($groups->count() > 1 || $group['category'])
                    <h2>{{ $group['category']?->name ?? __('committee::committee.other') }}</h2>
                @endif
                @if ($layout === 'table')
                    <div tabindex="0" style="overflow-x:auto">
                        <table class="table" style="width:100%">
                            <thead><tr>
                                <th scope="col">{{ __('committee::committee.fields.name') }}</th>
                                <th scope="col">{{ __('committee::committee.chair') }}</th>
                                <th scope="col">{{ __('committee::committee.members') }}</th>
                                <th scope="col">{{ __('committee::committee.term') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($group['committees'] as $committee)
                                    @php($chair = $committee->people('chairperson')->first() ?? $committee->people('convener')->first())
                                    <tr>
                                        <td><a href="{{ url($base.'/committees/'.$committee->slug()) }}">{{ $committee->name }}</a></td>
                                        <td>{{ $chair?->name() }}</td>
                                        <td>{{ $committee->people()->count() }}</td>
                                        <td>{{ $committee->termLabel() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:{{ $layout === 'list' ? '1fr' : 'repeat(auto-fill,minmax(280px,1fr))' }}">
                        @foreach ($group['committees'] as $committee)
                            @php($chair = $committee->people('chairperson')->first() ?? $committee->people('convener')->first())
                            <article style="padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
                                <h3 style="margin:0"><a href="{{ url($base.'/committees/'.$committee->slug()) }}">{{ $committee->name }}</a></h3>
                                @if ($chair)<p style="margin:4px 0">{{ __('committee::committee.chair') }}: {{ $chair->name() }}</p>@endif
                                <p style="margin:4px 0;color:var(--color-muted, #6b7280)">{{ trans_choice('committee::committee.member_count', $n = $committee->people()->count(), ['count' => $n]) }}@if ($term = $committee->termLabel()) · {{ $term }}@endif</p>
                                @if ($layout === 'list' && filled($committee->description))
                                    <p style="margin:6px 0 0">{{ \Illuminate\Support\Str::words(strip_tags((string) $committee->description), 40) }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @empty
            <p style="margin-top:var(--space-8, 32px)">{{ __('committee::committee.none') }}</p>
        @endforelse
    </div>
</x-theme::layout>
