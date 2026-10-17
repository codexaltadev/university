{{-- Homepage section "What People Say" (FR-TEST-03). Contract: $items (Testimonial list, in order), $section, $settings (types, display, count); $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    @include('testimonials::partials.list', ['items' => $items, 'layout' => ($settings['display'] ?? 'slider') === 'grid' ? 'grid' : 'slider'])
    <p style="margin-top:var(--space-4, 16px)"><a href="{{ url($base.'/'.\Modules\Testimonials\Models\Testimonial::PATH) }}" class="btn">{{ __('testimonials::testimonials.all_testimonials') }}</a></p>
@endif
