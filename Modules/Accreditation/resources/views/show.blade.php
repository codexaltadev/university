{{-- /accreditations/{slug} (FR-ACR-05). Contract: $accreditation, $base --}}
<x-theme::layout :title="$accreditation->title">
    <article class="container" style="padding-block:var(--space-10, 40px);max-width:960px">
        <p><a href="{{ url($base.'/'.\Modules\Accreditation\Models\Accreditation::PATH) }}">← {{ __('accreditation::accreditation.plural') }}</a></p>
        @include('accreditation::partials.accreditation', ['accreditation' => $accreditation, 'base' => $base])
        <div class="prose" style="margin-top:var(--space-6, 24px)">{!! $accreditation->body !!}</div>
        @if ($accreditation->website_url)
            <p style="margin-top:var(--space-6, 24px)"><a class="button" href="{{ $accreditation->website_url }}" target="_blank" rel="noopener">{{ __('accreditation::accreditation.visit') }}</a></p>
        @endif
    </article>
</x-theme::layout>
