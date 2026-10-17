{{-- Homepage section "Labs" (FR-LAB-08). Contract: $items (Lab list), $section, $settings; a theme may redraw it --}}
@if ($items->isNotEmpty())
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($items as $lab)
            <a href="{{ url((string) $lab->url()) }}" class="card block">
                @if ($image = $lab->firstMedia('cover') ?? $lab->firstMedia('logo'))
                    <img src="{{ $image->url('medium') ?? $image->url() }}" alt="" loading="lazy" class="w-full aspect-video object-cover rounded-md mb-3">
                @endif
                <strong class="block">{{ $lab->name }}</strong>
                @if (filled($lab->location))<span class="lead text-sm">{{ $lab->location }}</span>@endif
            </a>
        @endforeach
    </div>
@endif
