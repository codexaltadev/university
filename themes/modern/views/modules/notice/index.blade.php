{{--
    Notice board /notices and /{entity}/notices (UI/UX Part B §12.5): page hero with on-board / archive chips and filters,
    then date-box rows. Contract: the module's (notices, categories, category, entities, entity, archive, layout, base),
    $page (NoticeBoardPage: boardUrl, url, entityName, items)
--}}
<x-theme::layout :title="settings('notice.board_title') ?: __('notice::notice.plural')">
    <x-theme::page-hero :title="$archive ? __('notice::notice.archive') : (settings('notice.board_title') ?: __('notice::notice.plural'))"
                        :crumbs="[['label' => __('notice::notice.plural'), 'url' => $page->boardUrl()]]">
        <div class="filters">
            <a href="{{ $page->url(['archive' => null]) }}" @class(['chip', 'chip-active' => ! $archive])>{{ __('notice::notice.on_board') }}</a>
            <a href="{{ $page->url(['archive' => 1]) }}" @class(['chip', 'chip-active' => $archive])>{{ __('notice::notice.archive') }}</a>
        </div>
        <form method="get" action="{{ $page->boardUrl() }}" class="filters" role="search">
            @if ($archive)<input type="hidden" name="archive" value="1">@endif
            <label class="sr-only" for="notice-q">{{ __('notice::notice.search') }}</label>
            <input id="notice-q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('notice::notice.search_placeholder') }}">
            @if ($categories->isNotEmpty())
                <label class="sr-only" for="notice-category">{{ __('notice::notice.fields.category') }}</label>
                <select id="notice-category" name="category">
                    <option value="">{{ __('notice::notice.fields.category') }}: {{ __('notice::notice.all') }}</option>
                    @foreach ($categories as $item)<option value="{{ $item->slug() }}" @selected($category?->is($item))>{{ $item->name }}</option>@endforeach
                </select>
            @endif
            @if ($entities->isNotEmpty())
                <label class="sr-only" for="notice-entity">{{ __('notice::notice.entity') }}</label>
                <select id="notice-entity" name="entity">
                    <option value="">{{ __('notice::notice.entity') }}: {{ __('notice::notice.all') }}</option>
                    @foreach ($entities as $key => $name)<option value="{{ $key }}" @selected($entity === $key)>{{ $name }}</option>@endforeach
                </select>
            @endif
            <button type="submit" class="btn btn--primary">{{ __('notice::notice.search') }}</button>
        </form>
    </x-theme::page-hero>

    <div class="container page-body">
        @if ($notices->isEmpty())
            <p class="empty-state">{{ __('notice::notice.none') }}</p>
        @else
            <ul class="row-list">
                @foreach ($page->items() as $item)
                    <li @class(['is-urgent' => $item['urgent']])>
                        <span class="date-box"><b>{{ $item['day'] }}</b>{{ str($item['month'])->before(',') }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ $item['url'] }}" class="row-list__title stretched">{{ $item['title'] }}</a>
                            @if ($item['urgent'])<span class="badge badge--danger">{{ __('theme::theme.sections.urgent') }}</span>@endif
                            @if ($item['category'])<span class="badge badge--muted">{{ $item['category'] }}</span>@endif
                            <p class="row-list__meta">{{ collect([$item['places'], $item['date']])->filter()->implode(' · ') }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
            {{ $notices->links() }}
        @endif
    </div>
</x-theme::layout>
