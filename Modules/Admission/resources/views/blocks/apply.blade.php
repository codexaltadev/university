{{-- Apply / Request for Information block (FR-ADM-04). Contract: $data (heading, text, buttons), $locale --}}
@php($links = array_filter([
    'apply' => settings('admission.apply_url') ? [settings('admission.apply_url'), __('admission::admission.apply_now'), 'button button-primary', true] : null,
    'info' => settings('admission.info_request_url') ? [settings('admission.info_request_url'), __('admission::admission.request_info'), 'button', false] : null,
    'whatsapp' => settings('admission.whatsapp_group_url') ? [settings('admission.whatsapp_group_url'), __('admission::admission.join_whatsapp'), 'button', true] : null,
    'result' => [url('/admission/result'), __('admission::admission.result_page.title'), 'button', false],
], fn ($link, $key) => $link && in_array($key, (array) ($data['buttons'] ?? ['apply', 'info']), true), ARRAY_FILTER_USE_BOTH))
<div class="block block--admission-apply" style="padding:var(--space-8, 32px);border-radius:var(--radius-md, 8px);background:var(--color-surface);text-align:center">
    @if (filled($data['heading'] ?? null))
        <h2 style="margin-top:0">{{ $data['heading'] }}</h2>
    @endif
    @if (filled($data['text'] ?? null))
        <p>{{ $data['text'] }}</p>
    @endif
    <p style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);justify-content:center;margin-bottom:0">
        @foreach ($links as [$href, $label, $class, $external])
            <a href="{{ $href }}" class="{{ $class }}" @if ($external) target="_blank" rel="noopener" @endif>{{ $label }}</a>
        @endforeach
    </p>
</div>
