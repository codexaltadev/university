{{--
    Program finder (theme section): a search box that opens the program listing with ?q=, and the admin's shortcuts.
    Sits over the bottom of a hero above it. Contract: $section (title, content: placeholder, action_url, links [label, url])
--}}
<div class="container finder">
    <form action="{{ $section->content['action_url'] ?? url('/programs') }}" method="get" role="search" class="card finder__box">
        <label for="{{ $section->anchor }}-q" class="sr-only">{{ $section->title ?: __('theme::theme.sections.find_program') }}</label>
        @if ($section->title)<strong class="px-2 font-heading">{{ $section->title }}</strong>@endif
        <input id="{{ $section->anchor }}-q" type="search" name="q" maxlength="100" placeholder="{{ $section->content['placeholder'] ?: __('theme::theme.sections.search_programs') }}">
        @if ($links = collect($section->content['links'] ?? [])->filter(fn ($link) => filled($link['url'] ?? null) && filled($link['label'] ?? null)))
            <div class="finder__chips">
                @foreach ($links as $link)
                    <a href="{{ $link['url'] }}" class="chip">{{ $link['label'] }}</a>
                @endforeach
            </div>
        @endif
        <button type="submit" class="btn btn--primary">{{ __('theme::theme.search') }}</button>
    </form>
</div>
