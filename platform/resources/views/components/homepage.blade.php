{{--
    <x-platform::homepage :context="$context" /> — the homepage builder's sections in order
    (C-10). Each section uses its own view (theme override first), else the theme's
    sections/{key}, else a plain list; <x-theme::section> draws the frame around it.
    No homepage of its own → the theme's partials/home-default (FR-HOME-08).
--}}
@props(['context'])
@php
    $sections = app(\Platform\Theme\SiteData::class)->get('homepage', $context);
@endphp
@if ($sections === null)
    @php
        // A microsite without its own homepage: its module's default (FR-FAC-06), else the theme's
        $entityHome = $context->isMicrosite()
            ? collect(app(\Platform\Registry\Registry::class)->all('micrositeEntity'))->firstWhere('model', $context->entity::class)['home'] ?? null
            : null;
    @endphp
    @if ($entityHome && view()->exists($entityHome))
        @include($entityHome, ['entity' => $context->entity, 'context' => $context])
    @else
        @includeIf('theme::partials.home-default')
    @endif
@else
    @foreach ($sections as $data)
        @php
            $section = new \Illuminate\Support\Fluent([
                ...$data,
                'title' => $data['heading'] ?? null,
                'anchor' => 'section-'.$loop->iteration,
                'style' => $data['settings']['background'] ?? null,
            ]);
            $view = collect([$data['view'], 'theme::sections.'.str_replace('.', '-', $data['key']), 'theme::sections.generic'])
                ->first(fn (string $v): bool => view()->exists($v));
        @endphp
        <x-theme::section :section="$section">
            @include($view, ['section' => $section, 'items' => $data['items'], 'settings' => $data['settings']])
        </x-theme::section>
    @endforeach
@endif
