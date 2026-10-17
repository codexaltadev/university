{{-- Homepage section "Rankings & Recognition" (FR-ACR-06). Contract: $items (Ranking / Accreditation list), $section, $settings (display); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    @php($cards = ($settings['display'] ?? 'strip') === 'cards')
    <div style="display:{{ $cards ? 'grid' : 'flex' }};gap:var(--space-4, 16px);{{ $cards ? 'grid-template-columns:repeat(auto-fill,minmax(220px,1fr))' : 'flex-wrap:wrap;justify-content:center;align-items:center' }}">
        @foreach ($items as $item)
            @if ($item instanceof \Modules\Accreditation\Models\Ranking)
                @if ($cards)
                    @include('accreditation::partials.ranking', ['ranking' => $item])
                @else
                    @php($logo = $item->firstMedia('badge') ?? $item->firstMedia('logo'))
                    <a href="{{ url($base.'/'.\Modules\Accreditation\Models\Ranking::PATH) }}" title="{{ $item->label() }} {{ $item->year }}: {{ $item->rank_label }}" style="display:flex;flex-direction:column;align-items:center;gap:4px;color:inherit;text-decoration:none;min-width:120px">
                        @if ($logo)<img src="{{ $logo->url('medium') ?? $logo->url() }}" alt="{{ $item->label() }}" loading="lazy" style="height:64px;max-width:160px;object-fit:contain">@else<strong>{{ $item->agency_name }}</strong>@endif
                        <span style="font-weight:700">{{ $item->rank_label }}</span>
                    </a>
                @endif
            @else
                @if ($cards)
                    @include('accreditation::partials.accreditation', ['accreditation' => $item, 'base' => $base])
                @else
                    @php($logo = $item->firstMedia('logo'))
                    <a href="{{ url($base.'/'.\Modules\Accreditation\Models\Accreditation::PATH) }}" title="{{ $item->title }}" style="display:flex;flex-direction:column;align-items:center;gap:4px;color:inherit;text-decoration:none;min-width:120px">
                        @if ($logo)<img src="{{ $logo->url('medium') ?? $logo->url() }}" alt="{{ $item->title }}" loading="lazy" style="height:64px;max-width:160px;object-fit:contain">@else<strong>{{ $item->title }}</strong>@endif
                    </a>
                @endif
            @endif
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px);text-align:center"><a class="button" href="{{ url($base.'/'.\Modules\Accreditation\Models\Ranking::PATH) }}">{{ __('accreditation::accreditation.all') }}</a></p>
@endif
