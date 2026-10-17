{{-- A lab's equipment / scopes / projects / products (FR-LAB-04). Contract: $lab, $type, $groups (['term' => ?Term, 'items' => list]) --}}
<x-theme::layout :title="__('lab::lab.types_plural.'.$type).' — '.$lab->name">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <p><a href="{{ $lab->url() }}">← {{ $lab->name }}</a></p>
        <h1>{{ __('lab::lab.types_plural.'.$type) }}</h1>
        @if ($type === 'equipment' && filled($lab->equipment_summary))<div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $lab->equipment_summary) !!}</div>@endif
        @foreach ($groups as $group)
            <section style="margin-top:var(--space-8, 32px)">
                @if (count($groups) > 1)<h2>{{ $group['term']?->name ?? __('lab::lab.other') }}</h2>@endif
                <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                    @foreach ($group['items'] as $item)
                        @include('lab::partials.item', ['item' => $item])
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-theme::layout>
