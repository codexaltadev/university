{{--
    Key facts block (UI/UX Part B §5.8 v0.6): the numbers on top in a brand band (a figure in data-count for a theme to
    count up; a canvas[data-network] for its background), a bar of the chapters (the one in view marked), each chapter a
    grid of tiles — text, a big number, chips, cards, bars, a timeline by year — and the note. The theme styles facts__*.
    Contract: $facts (Support\FactsPage: highlights(), chapters(), nav(), note()), $data, $locale
--}}
<div class="facts" x-data="{ current: @js($facts->chapters()[0]['id'] ?? '') }">
    @if ($facts->highlights() !== [])
        <div class="facts__hero">
            <canvas class="facts__network" data-network aria-hidden="true"></canvas>
            <dl class="facts__highlights">
                @foreach ($facts->highlights() as $highlight)
                    <div class="facts__highlight">
                        <dt class="facts__highlight-label">{{ $highlight['label'] }}</dt>
                        <dd class="facts__highlight-value">@if ($highlight['count'] !== null)<span data-count="{{ $highlight['count'] }}" data-from="{{ $highlight['from'] }}">{{ $highlight['number'] }}</span>@else{{ $highlight['number'] }}@endif<span class="facts__highlight-rest">{{ $highlight['rest'] }}</span></dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    @if ($facts->nav())
        <nav class="facts__chapters no-print" aria-label="{{ __('page::page.facts.nav') }}">
            @foreach ($facts->chapters() as $chapter)
                @if ($chapter['title'] !== null)
                    <a href="#{{ $chapter['id'] }}" class="facts__chapter-link" x-bind:class="{ 'facts__chapter-link--current': current === @js($chapter['id']) }"
                       x-bind:aria-current="current === @js($chapter['id']) ? 'true' : null">{{ $chapter['title'] }}</a>
                @endif
            @endforeach
        </nav>
    @endif

    @foreach ($facts->chapters() as $chapter)
        <section id="{{ $chapter['id'] }}" class="facts__chapter" x-intersect.margin.0px.0px.-60%.0px="current = @js($chapter['id'])">
            @if ($chapter['title'] !== null)
                <div class="facts__chapter-head">
                    <span class="facts__chapter-index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h2 class="facts__chapter-title">{{ $chapter['title'] }}</h2>
                </div>
            @endif
            <div class="facts__grid">
                @foreach ($chapter['tiles'] as $tile)
                    <article id="{{ $tile['id'] }}" @class(['facts__tile', 'facts__tile--'.$tile['size'], 'facts__tile--'.$tile['display'], 'facts__tile--featured' => $tile['featured']])>
                        <h3 class="facts__label">{{ $tile['label'] }}</h3>
                        @switch($tile['display'])
                            @case('stat')
                                <div class="facts__number">{{ $tile['number'] }}</div>
                                @if ($tile['rest'])<div class="facts__value">{{ $tile['rest'] }}</div>@endif
                                @break
                            @case('chips')
                                <div x-data="{ all: false }">
                                    <ul class="facts__chips" x-bind:class="{ 'facts__chips--all': all }">
                                        @foreach ($tile['items'] as $item)<li>{{ $item }}</li>@endforeach
                                    </ul>
                                    @if (count($tile['items']) > 12)
                                        <button type="button" class="facts__more" x-on:click="all = ! all" x-bind:aria-expanded="all"
                                                x-text="all ? @js(__('page::page.facts.show_less')) : @js(__('page::page.facts.show_all', ['count' => count($tile['items'])]))">{{ __('page::page.facts.show_all', ['count' => count($tile['items'])]) }}</button>
                                    @endif
                                </div>
                                @break
                            @case('cards')
                                <ol class="facts__cards">
                                    @foreach ($tile['items'] as $item)<li>{{ $item }}</li>@endforeach
                                </ol>
                                @break
                            @case('bars')
                                @foreach ($tile['meta'] as $line)<p class="facts__meta">{{ $line }}</p>@endforeach
                                <div class="facts__bars">
                                    @foreach ($tile['bars'] as $bar)
                                        <div class="facts__bar">
                                            <span class="facts__bar-label">{{ $bar['label'] }}</span><b class="facts__bar-value">{{ $bar['value'] }}</b>
                                            <span class="facts__bar-track"><span class="facts__bar-fill" style="width: {{ $bar['percent'] }}%"></span></span>
                                        </div>
                                    @endforeach
                                    @if ($tile['total'])
                                        <div class="facts__bar facts__bar--total"><span class="facts__bar-label">{{ $tile['total']['label'] }}</span><b class="facts__bar-value">{{ $tile['total']['value'] }}</b></div>
                                    @endif
                                </div>
                                @break
                            @case('timeline')
                                <ol class="facts__timeline">
                                    @foreach ($tile['years'] as $year)
                                        <li class="facts__year">
                                            <span class="facts__year-tag">{{ $year['year'] ?? '•' }}</span>
                                            <ul class="facts__year-items">@foreach ($year['items'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                                        </li>
                                    @endforeach
                                </ol>
                                @break
                            @default
                                <div class="facts__value">{{ $tile['html'] }}</div>
                        @endswitch
                        @if (in_array($tile['display'], ['chips', 'cards', 'timeline'], true) && $tile['extra'])
                            <div class="facts__value facts__extra">{{ $tile['extra'] }}</div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

    @if ($facts->note())
        <div class="facts__note">{{ $facts->note() }}</div>
    @endif
</div>
