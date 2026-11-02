{{--
    A Program's page / microsite home until it builds its own (FR-PROG-13, UI/UX Part B §12.5): hero with Apply / brochure,
    key facts as a figure row, then the overview and the tabs other modules add, quick links beside.
    Contract: $entity (Program), $context, $page (ProgramPage). Drawn inside the microsite layout (no layout here)
--}}
    <x-theme::page-hero :title="$entity->name" :text="$entity->subtitle" :image="$page->image()?->url('large')" :eyebrow="$entity->level?->name">
        <div class="page-hero__actions">
            @if ($brochure = $page->brochure())
                <a href="{{ $brochure->url() }}" class="btn" target="_blank" rel="noopener"><x-theme::icon name="download" width="16" height="16" />{{ __('program::program.fields.brochure') }}</a>
            @endif
        </div>
    </x-theme::page-hero>

    <div class="container">
        @if ($facts = $page->facts())
            <dl class="stat-row m-0">
                @foreach ($facts as $key => $value)
                    <div class="flex flex-col-reverse">
                        <dt class="stat__label">{{ __('theme::theme.program_facts.'.$key) }}</dt>
                        <dd class="m-0 text-2xl font-bold tracking-tight">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        <div class="page-body with-aside">
            <article class="min-w-0">
                @foreach ($page->overview() as $text)
                    <div class="prose mb-6">{!! $text !!}</div>
                @endforeach
                @foreach ($page->tabs() as $tab)
                    <section id="{{ $tab['key'] }}" class="mt-12 scroll-mt-24">
                        <h2 class="mb-5 text-2xl">{{ $tab['label'] }}</h2>
                        {!! $tab['html'] !!}
                    </section>
                @endforeach
            </article>
            <aside class="aside-stack">
                @if ($page->tabs()->isNotEmpty())
                    <nav class="card aside-card side-menu">
                        <ul>@foreach ($page->tabs() as $tab)<li><a href="#{{ $tab['key'] }}">{{ $tab['label'] }}</a></li>@endforeach</ul>
                    </nav>
                @endif
                @if ($links = $page->quickLinks())
                    <div class="card aside-card side-menu">
                        <ul>@foreach ($links as $link)<li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>@endforeach</ul>
                    </div>
                @endif
            </aside>
        </div>
    </div>
