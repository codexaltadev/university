{{-- Phones: a bar fixed at the bottom with the header button (theme options) and the phone number (Settings › Contact); nothing when both are empty --}}
@if ((filled(theme_option('header_cta_url')) && filled(theme_option('header_cta_label'))) || filled(settings('contact.phone')))
    <div class="mobile-actions no-print">
        @if ($phone = settings('contact.phone'))
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="btn btn--on-dark"><x-theme::icon name="phone" width="18" height="18" />{{ __('theme::theme.call') }}</a>
        @endif
        @if (filled($ctaUrl = theme_option('header_cta_url')) && filled($ctaLabel = theme_option('header_cta_label')))
            <a href="{{ $ctaUrl }}" class="btn btn--accent">{{ $ctaLabel }}</a>
        @endif
    </div>
@endif
