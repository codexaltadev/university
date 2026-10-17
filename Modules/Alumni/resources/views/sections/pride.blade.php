{{-- Homepage section "Our Pride Alumni" (FR-ALUM-04). Contract: $items (Alumnus list), $section, $settings (count, display: slider / grid) --}}
@if ($items->isNotEmpty())
    @php($slider = ($settings['display'] ?? 'slider') === 'slider')
    <div class="alumni-pride" @if ($slider) style="display:grid;grid-auto-flow:column;grid-auto-columns:minmax(240px,1fr);gap:var(--space-4, 16px);overflow-x:auto;scroll-snap-type:x mandatory" @else style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(240px,1fr))" @endif>
        @foreach ($items as $alumnus)
            <div @if ($slider) style="scroll-snap-align:start" @endif>
                @include('alumni::partials.card', ['alumnus' => $alumnus, 'layout' => 'cards'])
            </div>
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url('/alumni') }}" class="btn">{{ __('alumni::alumni.all_alumni') }}</a></p>
@endif
