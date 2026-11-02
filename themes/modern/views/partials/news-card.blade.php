{{-- A news card. Contract: $card [title, url, image, category, date, text], $large (bool) --}}
<article class="card card--media card--link">
    <div class="card__media">@if ($card['image'])<img src="{{ $card['image'] }}" alt="" loading="lazy">@endif</div>
    <div class="card__body">
        @if ($card['category'])<span><span class="badge">{{ $card['category'] }}</span></span>@endif
        <h3 @class(['card__title', 'text-2xl' => $large ?? false])><a href="{{ $card['url'] }}" class="stretched">{{ $card['title'] }}</a></h3>
        @if ($card['text'])<p class="muted line-clamp-3">{{ $card['text'] }}</p>@endif
        <p class="card__meta">{{ $card['date'] }}</p>
    </div>
</article>
