{{-- Homepage section "Recent Achievements" (FR-ACH-03). Contract: $items (Achievement list), $section, $settings (level, display: cards / slider); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    @php($slider = ($settings['display'] ?? 'cards') === 'slider')
    <div style="{{ $slider ? 'display:grid;grid-auto-flow:column;grid-auto-columns:minmax(280px,1fr);gap:var(--space-4, 16px);overflow-x:auto;scroll-snap-type:x mandatory' : 'display:grid;gap:var(--space-5, 20px);grid-template-columns:repeat(auto-fill,minmax(260px,1fr))' }}">
        @foreach ($items as $achievement)
            <div @if ($slider) style="scroll-snap-align:start" @endif>
                @include('achievements::partials.card', ['achievement' => $achievement, 'base' => $base, 'layout' => 'cards'])
            </div>
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($base.'/'.\Modules\Achievements\Models\Achievement::PATH.(($settings['level'] ?? null) ? '?level='.$settings['level'] : '')) }}" class="btn">{{ __('achievements::achievements.all_achievements') }}</a></p>
@endif
