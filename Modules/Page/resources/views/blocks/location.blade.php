{{-- Location block. Contract: $campus (LocationBlock::campus: name, address, phones, emails, map, map_link) or null --}}
@if ($campus ?? null)
    <section class="block block--location" style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
        <div>
            <h2>{{ $campus['name'] }}</h2>
            @if ($campus['address'])<p>{{ $campus['address'] }}</p>@endif
            @foreach ($campus['phones'] as $phone)
                <p>@if ($phone['tel'])<a href="tel:{{ $phone['tel'] }}">{{ $phone['text'] }}</a>@else{{ $phone['text'] }}@endif</p>
            @endforeach
            @foreach ($campus['emails'] as $email)<p><a href="mailto:{{ $email }}">{{ $email }}</a></p>@endforeach
            @if ($campus['map_link'])<p><a href="{{ $campus['map_link'] }}" target="_blank" rel="noopener">{{ __('page::page.open_map') }}</a></p>@endif
        </div>
        @if ($campus['map'])
            <iframe src="{{ $campus['map'] }}" title="{{ $campus['name'] }}" width="100%" height="420" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        @endif
    </section>
@endif
